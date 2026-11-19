<?php
require_once __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Models\Practica;
use App\Models\RegistroHora;
use App\Models\EvaluacionSeguimiento;
use App\Models\EvaluacionFinal;
use App\Models\HistorialEstadoPractica;
use App\Http\Controllers\SeguimientoController;
use Illuminate\Http\Request;

echo "\n════════════════════════════════════════════════════════════════════════\n";
echo "  TEST ESPECÍFICO DE MEJORAS: RF-11 (4h), RF-13 (Notas desde 0) y RF-14\n";
echo "════════════════════════════════════════════════════════════════════════\n\n";

$pass = 0;
$fail = 0;

function report($cond, $titulo, $detalles = '') {
    global $pass, $fail;
    if ($cond) {
        $pass++;
        echo "  [PASS] $titulo" . ($detalles ? " -> $detalles" : "") . "\n";
    } else {
        $fail++;
        echo "  [FAIL] $titulo" . ($detalles ? " -> $detalles" : "") . "\n";
    }
}

$controller = new SeguimientoController();
$practica = Practica::find(1);
$userEstudiante = User::where('email', 'estudiante@upds.edu.bo')->first();
$userTutor = User::where('email', 'tutor@upds.edu.bo')->first();
$userCoord = User::where('email', 'coordinacion@upds.edu.bo')->first();

// ─────────────────────────────────────────────────────────────────────────────
// 1. RF-11: LÍMITE DE 4 HORAS DIARIAS Y CONCURRENCIA
// ─────────────────────────────────────────────────────────────────────────────
echo "--- 1. RF-11: MÁXIMO 4 HORAS DIARIAS ---\n";

// Configuración max_horas_diarias
$configMax = config('practicas.max_horas_diarias');
report($configMax === 4.0, "Configuración 'max_horas_diarias' es 4.0", "Valor configurado: {$configMax}");

// Autenticar como estudiante
Auth::login($userEstudiante);

// Fecha de prueba para hoy
$fechaTest1 = '2026-04-22'; // Día laboral válido dentro del periodo de prácticas
// Limpiar registros previos en esa fecha de prueba
RegistroHora::where('practica_id', $practica->id)->whereDate('fecha', $fechaTest1)->delete();

// Test A: Registrar 1 hora -> PERMITIDO
$req1h = Request::create("/seguimiento/{$practica->id}/horas", 'POST', [
    'fecha' => $fechaTest1,
    'horas' => 1.0,
    'descripcion' => 'Prueba jornada de 1 hora permitida',
]);
$resp1 = $controller->storeHora($req1h, $practica);
$reg1 = RegistroHora::where('practica_id', $practica->id)->whereDate('fecha', $fechaTest1)->first();
report($reg1 && $reg1->horas == 1.0 && $reg1->estado === 'pendiente', "Registrar 1 hora en fecha libre: PERMITIDO", "Estado: {$reg1->estado}");

// Limpiar para test 4h
RegistroHora::where('practica_id', $practica->id)->whereDate('fecha', $fechaTest1)->delete();

// Test B: Registrar 4 horas directas -> PERMITIDO
$req4h = Request::create("/seguimiento/{$practica->id}/horas", 'POST', [
    'fecha' => $fechaTest1,
    'horas' => 4.0,
    'descripcion' => 'Prueba jornada de 4 horas completas permitidas',
]);
$controller->storeHora($req4h, $practica);
$reg4 = RegistroHora::where('practica_id', $practica->id)->whereDate('fecha', $fechaTest1)->first();
report($reg4 && $reg4->horas == 4.0, "Registrar 4 horas completas (máximo diario): PERMITIDO");

// Test C: Intentar registrar 1 hora más cuando ya hay 4 horas -> RECHAZADO
$reqExceso = Request::create("/seguimiento/{$practica->id}/horas", 'POST', [
    'fecha' => $fechaTest1,
    'horas' => 1.0,
    'descripcion' => 'Intento de 1 hora extra sobre las 4h existentes',
]);
$respExceso = $controller->storeHora($reqExceso, $practica);
$errorsExceso = session('errors');
$tieneErrorHoras = $errorsExceso && $errorsExceso->has('horas');
report($tieneErrorHoras, "Registrar 4h + 1h el mismo día: RECHAZADO", "Mensaje: " . ($tieneErrorHoras ? $errorsExceso->first('horas') : ''));

// Test D: Intentar registrar 5 horas directas (> 4 max_horas_diarias) -> RECHAZADO por validación
$fechaTest2 = '2026-04-23';
RegistroHora::where('practica_id', $practica->id)->whereDate('fecha', $fechaTest2)->delete();
$error5h = false;
try {
    $req5h = Request::create("/seguimiento/{$practica->id}/horas", 'POST', [
        'fecha' => $fechaTest2,
        'horas' => 5.0,
        'descripcion' => 'Jornada inválida de 5 horas',
    ]);
    $controller->storeHora($req5h, $practica);
} catch (\Illuminate\Validation\ValidationException $e) {
    $error5h = true;
}
report($error5h, "Registrar 5 horas en una sola jornada: RECHAZADO por regla max:4");

// Test E: Registrar 2h + 2h el mismo día -> PERMITIDO
RegistroHora::where('practica_id', $practica->id)->whereDate('fecha', $fechaTest2)->delete();
$req2hA = Request::create("/seguimiento/{$practica->id}/horas", 'POST', [
    'fecha' => $fechaTest2,
    'horas' => 2.0,
    'descripcion' => 'Primera jornada de 2 horas',
]);
$controller->storeHora($req2hA, $practica);

$req2hB = Request::create("/seguimiento/{$practica->id}/horas", 'POST', [
    'fecha' => $fechaTest2,
    'horas' => 2.0,
    'descripcion' => 'Segunda jornada de 2 horas en la misma fecha',
]);
$controller->storeHora($req2hB, $practica);
$totalDia2 = RegistroHora::where('practica_id', $practica->id)->whereDate('fecha', $fechaTest2)->sum('horas');
report($totalDia2 == 4.0, "Registrar 2h + 2h en la misma fecha: PERMITIDO", "Total acumulado en el día: {$totalDia2}h");

// Test F: Intentar registrar 3h + 2h -> RECHAZADO el segundo registro
$fechaTest3 = '2026-04-24';
RegistroHora::where('practica_id', $practica->id)->whereDate('fecha', $fechaTest3)->delete();
$req3h = Request::create("/seguimiento/{$practica->id}/horas", 'POST', [
    'fecha' => $fechaTest3,
    'horas' => 3.0,
    'descripcion' => 'Jornada matutina de 3 horas',
]);
$controller->storeHora($req3h, $practica);

$req2hSobre3 = Request::create("/seguimiento/{$practica->id}/horas", 'POST', [
    'fecha' => $fechaTest3,
    'horas' => 2.0,
    'descripcion' => 'Intento de 2 horas adicionales',
]);
$controller->storeHora($req2hSobre3, $practica);
$errors3plus2 = session('errors');
$rechazado3plus2 = $errors3plus2 && $errors3plus2->has('horas');
report($rechazado3plus2, "Registrar 3h + 2h en la misma fecha: RECHAZADO", "Mensaje: " . ($rechazado3plus2 ? $errors3plus2->first('horas') : ''));

// Limpieza de datos de prueba temporales
RegistroHora::where('practica_id', $practica->id)->whereIn('fecha', [$fechaTest1, $fechaTest2, $fechaTest3])->delete();

// ─────────────────────────────────────────────────────────────────────────────
// 2. RF-13: CALIFICACIONES PREDETERMINADAS DESDE 0
// ─────────────────────────────────────────────────────────────────────────────
echo "\n--- 2. RF-13: CALIFICACIONES PREDETERMINADAS Y RANGOS (0 a 100) ---\n";
Auth::login($userTutor);

// Test G: Rechazar calificaciones negativas (< 0)
$negativoRechazado = false;
try {
    $reqNeg = Request::create("/seguimiento/{$practica->id}/evaluacion-parcial", 'POST', [
        'periodo' => 'Parcial Test Negativo',
        'fecha' => date('Y-m-d'),
        'calificacion' => -5,
        'resultado' => 'Reprobado',
    ]);
    $controller->storeEvaluacionParcial($reqNeg, $practica);
} catch (\Illuminate\Validation\ValidationException $e) {
    $negativoRechazado = true;
}
report($negativoRechazado, "Calificación de -5 puntos: RECHAZADA (mínimo 0)");

// Test H: Rechazar calificaciones mayores a 100 (> 100)
$mayor100Rechazado = false;
try {
    $reqMayor = Request::create("/seguimiento/{$practica->id}/evaluacion-parcial", 'POST', [
        'periodo' => 'Parcial Test > 100',
        'fecha' => date('Y-m-d'),
        'calificacion' => 105,
        'resultado' => 'Excelente',
    ]);
    $controller->storeEvaluacionParcial($reqMayor, $practica);
} catch (\Illuminate\Validation\ValidationException $e) {
    $mayor100Rechazado = true;
}
report($mayor100Rechazado, "Calificación de 105 puntos: RECHAZADA (máximo 100)");

// Test I: Aceptar calificación válida con decimales (ej. 88.5)
$reqValida = Request::create("/seguimiento/{$practica->id}/evaluacion-parcial", 'POST', [
    'periodo' => 'Control de Competencias Quincenal Test',
    'fecha' => date('Y-m-d'),
    'calificacion' => 88.5,
    'resultado' => 'Aprobado',
    'comentario' => 'Prueba de calificación válida',
]);
$respValida = $controller->storeEvaluacionParcial($reqValida, $practica);
$evCreada = EvaluacionSeguimiento::where('practica_id', $practica->id)
    ->where('periodo', 'Control de Competencias Quincenal Test')
    ->first();
report($evCreada && $evCreada->calificacion == 88.5, "Calificación de 88.5 con decimales: REGISTRADA CORRECTAMENTE");
if ($evCreada) $evCreada->delete();

// ─────────────────────────────────────────────────────────────────────────────
// 3. RF-14: VALIDACIÓN ESTRICTA DEL CIERRE DE PRÁCTICAS
// ─────────────────────────────────────────────────────────────────────────────
echo "\n--- 3. RF-14: CIERRE Y CONTEXTO DE EVALUACIÓN FINAL ---\n";
Auth::login($userCoord);

$solId = null;
$pracTestCierre = null;
try {
    // Crear una solicitud de prueba aprobada para la prueba de cierre
    $solId = DB::table('solicitudes_practica')->insertGetId([
        'estudiante_id' => $practica->solicitud->estudiante_id,
        'organizacion_id' => $practica->solicitud->organizacion_id,
        'fecha_solicitud' => now(),
        'estado' => 'aprobada',
    ]);

    // Crear una práctica de prueba en curso con horas cumplidas pero sin evaluación final
    $pracTestCierre = Practica::create([
        'solicitud_id' => $solId,
        'coordinador_id' => $userCoord->coordinador?->id ?? 1,
        'tutor_id' => $userTutor->tutor->id,
        'estado' => 'en_curso',
        'horas_objetivo' => 20, // Meta de 20 horas
        'fecha_inicio' => '2026-03-01',
        'fecha_fin_prevista' => '2026-12-31',
    ]);

    // Registrar y validar 20 horas para que cumpla el criterio 1 de horas y pase al criterio 2 de evaluación final
    for ($d = 1; $d <= 5; $d++) {
        DB::table('registros_horas')->insert([
            'practica_id' => $pracTestCierre->id,
            'fecha' => "2026-03-0{$d}",
            'horas' => 4.0,
            'descripcion' => "Jornada test {$d}",
            'estado' => 'validada',
            'created_at' => now(),
        ]);
    }

    // Test J: Intentar concluir sin evaluación final del tutor -> RECHAZADO
    $reqCierreSinEval = Request::create("/seguimiento/{$pracTestCierre->id}/cierre", 'POST', [
        'nuevo_estado' => 'concluida',
        'motivo' => 'Intento de cierre sin evaluación final',
    ]);
    $controller->cerrarPractica($reqCierreSinEval, $pracTestCierre);
    $errorsCierre = session('errors');
    $rechazoSinEval = $errorsCierre && $errorsCierre->has('nuevo_estado');
    $msgError = $rechazoSinEval ? $errorsCierre->first('nuevo_estado') : '';
    report($rechazoSinEval && str_contains($msgError, 'docente tutor'), "Cierre sin evaluación final del tutor: RECHAZADO", $msgError);
} finally {
    // Limpieza garantizada para nunca dejar registros huérfanos
    if ($pracTestCierre) {
        DB::table('registros_horas')->where('practica_id', $pracTestCierre->id)->delete();
        $pracTestCierre->delete();
    }
    if ($solId) {
        DB::table('solicitudes_practica')->where('id', $solId)->delete();
    }
}

echo "\n════════════════════════════════════════════════════════════════════════\n";
echo "  RESULTADO: {$pass} pasaron | {$fail} fallaron\n";
echo "════════════════════════════════════════════════════════════════════════\n\n";
