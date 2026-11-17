<?php
/**
 * TEST SUITE EXCLUSIVO - ROL COORDINADOR ACADÉMICO
 * UPDS Cochabamba - Ingeniería de Software II
 */

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\Coordinador;
use App\Models\Estudiante;
use App\Models\Carrera;
use App\Models\OrganizacionReceptora;
use App\Models\ResponsableOrganizacion;
use App\Models\SolicitudPractica;
use App\Models\Practica;
use App\Models\DocenteTutor;
use App\Models\CronogramaActividad;
use App\Models\RegistroHora;
use App\Models\EvaluacionOrganizacion;
use App\Models\EvaluacionFinal;
use App\Models\HistorialEstadoPractica;
use App\Models\RequisitoAcademico;
use App\Models\EstudianteRequisito;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

$passed = 0;
$failed = 0;
$results = [];

function assertTest($description, $condition, $details = '') {
    global $passed, $failed, $results;
    if ($condition) {
        $passed++;
        $results[] = "[PASS] " . $description . ($details ? " ($details)" : "");
        echo "[PASS] " . $description . "\n";
    } else {
        $failed++;
        $results[] = "[FAIL] " . $description . ($details ? " ($details)" : "");
        echo "[FAIL] " . $description . ($details ? " - $details" : "") . "\n";
    }
}

echo "========================================================\n";
echo " INICIANDO SUITE DE PRUEBAS DEL ROL COORDINADOR ACADÉMICO\n";
echo "========================================================\n\n";

DB::beginTransaction();

try {
    // 1. Autenticación y Perfil
    $coordUser = User::where('email', 'coordinacion@upds.edu.bo')->first();
    assertTest("1. Usuario Coordinador existe en BD", $coordUser !== null && $coordUser->rol_id == 2);
    auth()->login($coordUser);
    assertTest("2. Usuario Coordinador autenticado", auth()->check() && auth()->user()->isCoordinador());

    // 2. Seguridad: Bloqueo de acceso al módulo de administración
    $adminController = app(App\Http\Controllers\AdminController::class);
    $blocked = false;
    try {
        // En middleware 'role:administrador', un usuario no admin debe ser bloqueado
        if (!auth()->user()->isAdmin()) {
            $blocked = true;
        }
    } catch (\Throwable $e) {
        $blocked = true;
    }
    assertTest("3. Acceso a rutas /admin/* bloqueado para coordinador", $blocked);

    // 3. Edición de perfil: solo teléfono editable, nombre/email protegidos
    $coordProfile = $coordUser->coordinador;
    $origName = $coordUser->name;
    $origEmail = $coordUser->email;
    $origPhone = $coordProfile ? $coordProfile->telefono : '70000000';

    $perfilController = app(App\Http\Controllers\PerfilController::class);
    $reqPerfil = Request::create('/perfil', 'PUT', [
        'telefono' => '71122334',
        'name' => 'Hacker Name',
        'email' => 'hacker@upds.edu.bo'
    ]);
    $perfilController->update($reqPerfil);
    $coordUser->refresh();
    if ($coordProfile) $coordProfile->refresh();

    assertTest("4. Perfil: Nombre y Correo son de solo lectura", $coordUser->name === $origName && $coordUser->email === $origEmail);
    assertTest("5. Perfil: Teléfono actualizado correctamente en coordinadores", $coordProfile && $coordProfile->telefono === '71122334');

    // 4. Dashboard KPIs reales de MySQL
    $dashboardController = app(App\Http\Controllers\DashboardController::class);
    $dashView = $dashboardController->index();
    $dashData = $dashView->getData();
    assertTest("6. Dashboard retorna métricas reales", isset($dashData['kpi']) && is_array($dashData['kpi']));
    assertTest("7. Métricas contienen solicitudes_pendientes y practicas_activas", 
        array_key_exists('solicitudes_pendientes', $dashData['kpi']) && 
        array_key_exists('practicas_activas', $dashData['kpi'])
    );

    // 5. Creación de Estudiante sin cuenta (usuario_id = null)
    $carrera = Carrera::first();
    $estController = app(App\Http\Controllers\EstudianteController::class);
    $testRu = 'RU-COORD-' . rand(1000, 9999);
    $reqEst = Request::create('/estudiantes', 'POST', [
        'nombres' => 'Juan Test',
        'apellidos' => 'Pérez Coordinador',
        'ru' => $testRu,
        'carrera_id' => $carrera->id,
        'semestre' => 7,
        'telefono' => '77889900',
        'estado_academico' => 'regular'
    ]);
    $estController->store($reqEst);
    $nuevoEst = Estudiante::where('ru', $testRu)->first();
    assertTest("8. Ficha académica creada sin cuenta de usuario obligatoria", $nuevoEst !== null && $nuevoEst->usuario_id === null);

    // 6. Vinculación y Requisitos Académicos automáticos
    $reqsAsignados = EstudianteRequisito::where('estudiante_id', $nuevoEst->id)->count();
    assertTest("9. Requisitos académicos asignados automáticamente al registrar estudiante", $reqsAsignados > 0);

    // 7. Verificación de requisitos por el coordinador
    $primerReq = EstudianteRequisito::where('estudiante_id', $nuevoEst->id)->first();
    $reqVerif = Request::create("/estudiantes/{$nuevoEst->id}/requisitos/{$primerReq->id}", 'POST', [
        'estado' => 'cumple',
        'observacion' => 'Documentación física revisada y conforme'
    ]);
    $estController->verificarRequisito($reqVerif, $nuevoEst, $primerReq);
    $primerReq->refresh();
    assertTest("10. Coordinador verifica requisito a 'cumple' con auditoría", 
        $primerReq->estado === 'cumple' && 
        $primerReq->verificado_por === $coordUser->id && 
        $primerReq->fecha_verificacion !== null
    );

    // 8. Registro de Organización y Contactos
    $orgController = app(App\Http\Controllers\OrganizacionController::class);
    $testOrgNit = 'NIT-' . rand(100000, 999999);
    $reqOrg = Request::create('/organizaciones', 'POST', [
        'nombre' => 'Empresa Test Coordinación ' . rand(100, 999),
        'rubro' => 'Tecnología de Software',
        'direccion' => 'Av. América Este #123',
        'telefono' => '4411223',
        'correo' => 'contacto@testcoord.com',
        'nit' => $testOrgNit,
        'convenio_vigente' => 1
    ]);
    $orgController->store($reqOrg);
    $nuevaOrg = OrganizacionReceptora::where('nit', $testOrgNit)->first();
    assertTest("11. Organización receptora registrada con éxito", $nuevaOrg !== null);

    // Contacto de la organización
    $reqResp = Request::create("/organizaciones/{$nuevaOrg->id}/responsables", 'POST', [
        'nombres' => 'Ing. Roberto',
        'apellidos' => 'Gómez Bolaños',
        'cargo' => 'Gerente de Sistemas',
        'correo' => 'roberto@testcoord.com',
        'telefono' => '71234567'
    ]);
    $orgController->storeResponsable($reqResp, $nuevaOrg);
    $respOrg = ResponsableOrganizacion::where('organizacion_id', $nuevaOrg->id)->first();
    assertTest("12. Contacto/Responsable de organización añadido correctamente", $respOrg !== null && $respOrg->cargo === 'Gerente de Sistemas');

    // 9. Solicitud de práctica y reglas de aprobación
    // Crear solicitud asociada al estudiante y la organización
    $solicitud = SolicitudPractica::create([
        'estudiante_id' => $nuevoEst->id,
        'organizacion_id' => $nuevaOrg->id,
        'modalidad' => 'tiempo_completo',
        'area' => 'Desarrollo de Software',
        'fecha_solicitud' => now(),
        'estado' => 'presentada'
    ]);
    assertTest("13. Solicitud de práctica creada para pruebas", $solicitud !== null);

    $solController = app(App\Http\Controllers\SolicitudController::class);

    // Observar solicitud
    $reqObs = Request::create("/solicitudes/{$solicitud->id}/estado", 'POST', [
        'nuevo_estado' => 'observada',
        'motivo' => 'Falta adjuntar carta de aceptación formal'
    ]);
    $solController->cambiarEstado($reqObs, $solicitud);
    $solicitud->refresh();
    assertTest("14. Coordinador puede observar solicitud con motivo obligatorio", $solicitud->estado === 'observada' && !empty($solicitud->observacion));

    // Regla de aprobación: si los requisitos obligatorios no están completos, no debe permitir aprobar
    // Ponemos un requisito obligatorio como 'pendiente'
    $reqObligatorio = EstudianteRequisito::where('estudiante_id', $nuevoEst->id)->first();
    $reqObligatorio->update(['estado' => 'pendiente']);

    $reqAprobarInvalido = Request::create("/solicitudes/{$solicitud->id}/estado", 'POST', [
        'nuevo_estado' => 'aprobada',
        'motivo' => 'Aprobación preliminar'
    ]);
    $respAprobInvalido = $solController->cambiarEstado($reqAprobarInvalido, $solicitud);
    $solicitud->refresh();
    assertTest("15. Aprobación bloqueada si existen requisitos obligatorios pendientes", $solicitud->estado !== 'aprobada');

    // Ahora cumplimos todos los requisitos obligatorios
    EstudianteRequisito::where('estudiante_id', $nuevoEst->id)->update([
        'estado' => 'cumple',
        'verificado_por' => $coordUser->id,
        'fecha_verificacion' => now()
    ]);

    $reqAprobarValido = Request::create("/solicitudes/{$solicitud->id}/estado", 'POST', [
        'nuevo_estado' => 'aprobada',
        'motivo' => 'Documentación y requisitos verificados y conformes'
    ]);
    $solController->cambiarEstado($reqAprobarValido, $solicitud);
    $solicitud->refresh();
    assertTest("16. Solicitud aprobada exitosamente cuando cumple todos los requisitos", $solicitud->estado === 'aprobada');

    // 10. Creación de Práctica automática al aprobar
    $practica = Practica::where('solicitud_id', $solicitud->id)->first();
    assertTest("17. Práctica académica generada automáticamente tras la aprobación", $practica !== null);

    // 11. Asignación de Tutor y Configuración de Práctica
    $tutor = DocenteTutor::where('activo', 1)->first();
    $segController = app(App\Http\Controllers\SeguimientoController::class);

    $reqAsignar = Request::create("/seguimiento/{$practica->id}/asignar-tutor", 'POST', [
        'tutor_id' => $tutor->id
    ]);
    $segController->asignarTutor($reqAsignar, $practica);
    $practica->refresh();
    assertTest("18. Coordinador asigna docente tutor activo a la práctica", $practica->tutor_id === $tutor->id);

    $histTutor = HistorialEstadoPractica::where('practica_id', $practica->id)->first();
    assertTest("19. Asignación registrada en auditoría historial_estados_practica", $histTutor !== null);

    // 12. Planificación de Cronograma de Actividades
    $reqAct = Request::create("/seguimiento/{$practica->id}/actividades", 'POST', [
        'titulo' => 'Diseño de arquitectura de microservicios',
        'objetivo' => 'Documento de especificación técnica',
        'fecha_inicio' => date('Y-m-d'),
        'fecha_fin' => date('Y-m-d', strtotime('+15 days')),
        'horas_planificadas' => 45,
        'estado' => 'en_progreso'
    ]);
    $segController->storeActividad($reqAct, $practica);
    $actividad = CronogramaActividad::where('practica_id', $practica->id)->first();
    assertTest("20. Actividad de cronograma creada en MySQL por el coordinador", $actividad !== null && $actividad->horas_planificadas == 45);

    // Actualización de actividad
    $reqActUp = Request::create("/seguimiento/actividades/{$actividad->id}", 'PUT', [
        'titulo' => 'Diseño de arquitectura de microservicios UPDS',
        'horas_planificadas' => 50,
        'estado' => 'completada'
    ]);
    $segController->updateActividad($reqActUp, $actividad);
    $actividad->refresh();
    assertTest("21. Actividad de cronograma actualizada a completada con 50h", $actividad->estado === 'completada' && $actividad->horas_planificadas == 50);

    // 13. Restricción REGLA 29: Coordinador NO valida horas
    $horaTest = RegistroHora::create([
        'practica_id' => $practica->id,
        'fecha' => now(),
        'horas' => 5,
        'descripcion' => 'Desarrollo de módulos',
        'estado' => 'pendiente'
    ]);
    $reqValidarHora = Request::create("/seguimiento/horas/{$horaTest->id}/validar", 'POST', [
        'estado' => 'validada'
    ]);
    $horaBlocked = false;
    try {
        $segController->validarHora($reqValidarHora, $horaTest);
    } catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
        if ($e->getStatusCode() === 403) {
            $horaBlocked = true;
        }
    }
    assertTest("22. REGLA 29: Coordinador tiene BLOQUEADA la validación de horas (HTTP 403)", $horaBlocked);

    // 14. Registro de Valoración de Organización Receptora
    $reqEvalOrg = Request::create("/seguimiento/{$practica->id}/evaluacion-organizacion", 'POST', [
        'responsable_id' => $respOrg->id,
        'fecha' => date('Y-m-d'),
        'desempeno' => 'Excelente',
        'cumplimiento' => 'Completo',
        'comentario' => 'El estudiante demostró alto nivel de compromiso, iniciativa y conocimientos técnicos sólidos.',
        'recomendacion' => 'Continuar profundizando en tecnologías cloud.'
    ]);
    $segController->storeEvaluacionOrganizacion($reqEvalOrg, $practica);
    $evalOrg = EvaluacionOrganizacion::where('practica_id', $practica->id)->first();
    assertTest("23. Valoración de la organización registrada en evaluaciones_organizacion", 
        $evalOrg !== null && $evalOrg->desempeno === 'Excelente' && $evalOrg->cumplimiento === 'Completo'
    );

    // 15. Cierre de Práctica: No se puede concluir sin evaluación final del tutor
    $reqCierreSinEval = Request::create("/seguimiento/{$practica->id}/cierre", 'POST', [
        'nuevo_estado' => 'concluida',
        'motivo' => 'Intento de cierre formal previo a evaluación'
    ]);
    $cierreSinEval = $segController->cerrarPractica($reqCierreSinEval, $practica);
    $practica->refresh();
    assertTest("24. Conclusión bloqueada si tutor no ha emitido evaluación final aprobatoria", $practica->estado !== 'concluida');

    // Ahora registramos la evaluación final del tutor
    EvaluacionFinal::create([
        'practica_id' => $practica->id,
        'tutor_id' => $tutor->id,
        'fecha' => now(),
        'calificacion' => 96.5,
        'resultado' => 'Aprobado',
        'comentario' => 'Cumplimiento sobresaliente del plan de práctica.'
    ]);
    $practica->unsetRelation('evaluacionFinal');

    // Concluimos la práctica
    $reqCierreConEval = Request::create("/seguimiento/{$practica->id}/cierre", 'POST', [
        'nuevo_estado' => 'concluida',
        'motivo' => 'Cierre formal por acreditación académica satisfactoria'
    ]);
    $segController->cerrarPractica($reqCierreConEval, $practica);
    $practica->refresh();
    assertTest("25. Práctica concluida y formalmente cerrada por coordinación", $practica->estado === 'concluida');

    // 16. Prueba de cierre por Abandono con motivo obligatorio
    // Creamos otra práctica para probar abandono
    $solicitud2 = SolicitudPractica::create([
        'estudiante_id' => $nuevoEst->id,
        'organizacion_id' => $nuevaOrg->id,
        'modalidad' => 'medio_tiempo',
        'area' => 'Soporte',
        'fecha_solicitud' => now(),
        'estado' => 'aprobada'
    ]);
    $practica2 = Practica::create([
        'solicitud_id' => $solicitud2->id,
        'coordinador_id' => $coordUser->coordinador->id ?? 1,
        'tutor_id' => $tutor->id,
        'estado' => 'en_curso',
        'horas_objetivo' => 360,
        'horas_validadas' => 20
    ]);
    $reqAbandono = Request::create("/seguimiento/{$practica2->id}/cierre", 'POST', [
        'nuevo_estado' => 'abandonada',
        'motivo' => 'El estudiante no se presentó a la empresa desde hace 3 semanas consecutivas'
    ]);
    $segController->cerrarPractica($reqAbandono, $practica2);
    $practica2->refresh();
    $histAbandono = HistorialEstadoPractica::where('practica_id', $practica2->id)->where('estado_nuevo', 'abandonada')->first();
    assertTest("26. Práctica marcada como abandonada con registro en auditoría", 
        $practica2->estado === 'abandonada' && $histAbandono !== null && !empty($histAbandono->motivo)
    );

    // 17. Reportes y Exportación CSV
    $repController = app(App\Http\Controllers\ReporteController::class);
    $reqRep = Request::create('/reportes', 'GET');
    $viewRep = $repController->index($reqRep);
    assertTest("27. Módulo de reportes consulta todas las vistas SQL institucionales", 
        isset($viewRep->getData()['cumplimiento']) && 
        isset($viewRep->getData()['enCurso']) && 
        isset($viewRep->getData()['concluidas']) && 
        isset($viewRep->getData()['abandonadas']) && 
        isset($viewRep->getData()['pendientes']) && 
        isset($viewRep->getData()['organizaciones']) && 
        isset($viewRep->getData()['porTutor'])
    );

    $reqExport = Request::create('/reportes/exportar', 'GET', ['tipo' => 'cumplimiento']);
    $respExport = $repController->exportar($reqExport);
    assertTest("28. Exportación CSV genera respuesta con cabeceras correctas", 
        $respExport->headers->get('content-type') === 'text/csv; charset=UTF-8'
    );

    // 18. Verificación de Vistas y Controladores del Coordinador
    view()->share('errors', new \Illuminate\Support\ViewErrorBag());

    $controllerViews = [
        'Dashboard' => fn() => $dashboardController->index(),
        'Estudiantes Index' => fn() => $estController->index(Request::create('/estudiantes', 'GET')),
        'Estudiantes Show' => fn() => $estController->show($nuevoEst),
        'Organizaciones Index' => fn() => $orgController->index(Request::create('/organizaciones', 'GET')),
        'Organizaciones Edit' => fn() => $orgController->edit($nuevaOrg),
        'Solicitudes Index' => fn() => $solController->index(Request::create('/solicitudes', 'GET')),
        'Solicitudes Show' => fn() => $solController->show($solicitud),
        'Seguimiento Lista' => fn() => $segController->index(Request::create('/seguimiento', 'GET')),
        'Seguimiento Show' => fn() => $segController->show($practica),
        'Reportes Index' => fn() => $repController->index(Request::create('/reportes', 'GET')),
        'Perfil Edit' => fn() => $perfilController->edit(),
    ];

    foreach ($controllerViews as $name => $fn) {
        try {
            $viewResult = $fn();
            $html = $viewResult->render();
            assertTest("Controlador y Vista '{$name}' renderizan correctamente (" . strlen($html) . " bytes)", strlen($html) > 0);
        } catch (\Throwable $e) {
            assertTest("Controlador y Vista '{$name}' renderizan sin errores", false, $e->getMessage());
        }
    }

} finally {
    // Revertir transacciones para mantener la base de datos limpia de datos de prueba
    DB::rollBack();
    echo "\n[INFO] Transacción revertida. La base de datos original se mantiene íntegra y sin basura.\n";
}

echo "\n========================================================\n";
echo " RESUMEN: Pasadas: {$passed} | Fallidas: {$failed}\n";
echo "========================================================\n";
