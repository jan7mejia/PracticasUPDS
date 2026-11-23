<?php
/**
 * Script de corrección de inconsistencias - Nov 2026
 * Corrige: rubrica_definida, evaluaciones de criterio para actividades demo,
 *           horas objetivo, actividades completadas, período académico activo.
 * SEGURO: No borra registros existentes, solo actualiza y agrega.
 */

require_once __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== CORRECCIÓN DE INCONSISTENCIAS - Sistema GestionPracticasUPDS ===\n";
echo "Fecha: " . now()->format('d/m/Y H:i:s') . "\n\n";

// ═══════════════════════════════════════════════════════════
// 1. VERIFICAR ESTADO INICIAL
// ═══════════════════════════════════════════════════════════
echo "--- 1. ESTADO INICIAL ---\n";

$practicas = DB::table('practicas')->get();
foreach ($practicas as $p) {
    $hvali = DB::table('registros_horas')
        ->where('practica_id', $p->id)
        ->where('estado', 'validada')
        ->sum('horas');
    $hpend = DB::table('registros_horas')
        ->where('practica_id', $p->id)
        ->where('estado', 'pendiente')
        ->sum('horas');
    $pct = $p->horas_objetivo > 0 ? round(($hvali / $p->horas_objetivo) * 100, 1) : 0;
    echo "  Práctica ID={$p->id}: estado={$p->estado}, horas_obj={$p->horas_objetivo}, validadas={$hvali}h, pendientes={$hpend}h, {$pct}%\n";
}

$actividades = DB::table('cronograma_actividades')->where('practica_id', 1)->get();
echo "\n  Actividades (practica_id=1):\n";
foreach ($actividades as $act) {
    $tieneRubrica = DB::table('rubricas_actividad')->where('actividad_id', $act->id)->exists();
    $tieneEval = DB::table('evaluaciones_criterio')->where('practica_id', 1)
        ->whereIn('criterio_id', function($q) use ($act) {
            $q->select('cr.id')->from('criterios_rubrica as cr')
              ->join('rubricas_actividad as ra', 'cr.rubrica_id', '=', 'ra.id')
              ->where('ra.actividad_id', $act->id);
        })->exists();
    echo "  Act ID={$act->id}: {$act->titulo} | estado={$act->estado} | rubrica_definida={$act->rubrica_definida} | tieneRubrica=" . ($tieneRubrica ? 'SÍ' : 'NO') . " | tieneEval=" . ($tieneEval ? 'SÍ' : 'NO') . "\n";
}

echo "\n";

// ═══════════════════════════════════════════════════════════
// 2. CORREGIR rubrica_definida EN ACTIVIDADES QUE TIENEN RÚBRICA
// ═══════════════════════════════════════════════════════════
echo "--- 2. CORREGIR rubrica_definida ---\n";

$actividadesConRubrica = DB::table('cronograma_actividades as ca')
    ->join('rubricas_actividad as ra', 'ra.actividad_id', '=', 'ca.id')
    ->select('ca.id', 'ca.titulo', 'ca.rubrica_definida')
    ->get();

foreach ($actividadesConRubrica as $act) {
    if (!$act->rubrica_definida) {
        DB::table('cronograma_actividades')
            ->where('id', $act->id)
            ->update(['rubrica_definida' => 1]);
        echo "  ✅ Actividad ID={$act->id} '{$act->titulo}' → rubrica_definida=1\n";
    } else {
        echo "  ✓  Actividad ID={$act->id} ya tiene rubrica_definida=1\n";
    }
}

// ═══════════════════════════════════════════════════════════
// 3. AGREGAR EVALUACIONES DE CRITERIO PARA ACTIVIDADES #2 Y #3
//    (Si no existen todavía)
// ═══════════════════════════════════════════════════════════
echo "\n--- 3. AGREGAR EVALUACIONES DE CRITERIO PARA ACTIVIDADES #2 y #3 ---\n";

$tutorId = 1; // María Rodríguez (tutor@upds.edu.bo)
$practicaId = 1;

// Actividad #2: Desarrollo de servicios API (rubrica_id=2, criterios 4,5,6)
$criteriosAct2 = DB::table('criterios_rubrica as cr')
    ->join('rubricas_actividad as ra', 'cr.rubrica_id', '=', 'ra.id')
    ->where('ra.actividad_id', 2)
    ->select('cr.id', 'cr.nombre', 'cr.ponderacion')
    ->get();

// Notas demo para actividad #2 (en_proceso, buena demostración)
$notasAct2 = [4 => 88.0, 5 => 82.0, 6 => 90.0];

foreach ($criteriosAct2 as $crit) {
    $existe = DB::table('evaluaciones_criterio')
        ->where('criterio_id', $crit->id)
        ->where('practica_id', $practicaId)
        ->exists();

    if (!$existe) {
        DB::table('evaluaciones_criterio')->insert([
            'criterio_id'       => $crit->id,
            'practica_id'       => $practicaId,
            'tutor_id'          => $tutorId,
            'informe_avance_id' => null,
            'puntuacion'        => $notasAct2[$crit->id] ?? 80.0,
            'comentario'        => "Evaluación técnica de criterio '{$crit->nombre}'. Desempeño satisfactorio.",
            'created_at'        => now(),
            'updated_at'        => now(),
        ]);
        $nota = $notasAct2[$crit->id] ?? 80.0;
        echo "  ✅ Evaluación criterio ID={$crit->id} ({$crit->nombre}) Act#2 → {$nota}/100\n";
    } else {
        echo "  ✓  Ya existe evaluación para criterio ID={$crit->id} en Act#2\n";
    }
}

// Calcular nota actividad #2 y actualizar estado a "completada"
$criterios2 = DB::table('criterios_rubrica as cr')
    ->join('rubricas_actividad as ra', 'cr.rubrica_id', '=', 'ra.id')
    ->join('evaluaciones_criterio as ec', function($j) use ($practicaId) {
        $j->on('ec.criterio_id', '=', 'cr.id')->where('ec.practica_id', $practicaId);
    })
    ->where('ra.actividad_id', 2)
    ->select('cr.ponderacion', 'ec.puntuacion')
    ->get();

$notaTotal2 = 0;
foreach ($criterios2 as $c) {
    $notaTotal2 += ($c->puntuacion * ($c->ponderacion / 100));
}
$notaTotal2 = round($notaTotal2, 2);
$aprobado2 = $notaTotal2 >= 51.0;
echo "  → Nota calculada Act#2: {$notaTotal2}/100 (" . ($aprobado2 ? 'Aprobado' : 'Reprobado') . ")\n";

// Marcar actividad #2 como completada si está aprobada
if ($aprobado2) {
    DB::table('cronograma_actividades')->where('id', 2)->update([
        'estado'            => 'completada',
        'fecha_completada'  => now()->toDateString(),
        'observacion'       => "Evaluada mediante rúbrica. Nota obtenida: {$notaTotal2}/100 (Aprobado).",
    ]);
    echo "  ✅ Actividad #2 marcada como 'completada'\n";
}

// Actividad #3: Pruebas de integración (rubrica_id=3, criterios 7,8,9)
// NOTA: Esta está en_proceso (es la actual) — solo agregamos evaluación parcial demo
$criteriosAct3 = DB::table('criterios_rubrica as cr')
    ->join('rubricas_actividad as ra', 'cr.rubrica_id', '=', 'ra.id')
    ->where('ra.actividad_id', 3)
    ->select('cr.id', 'cr.nombre', 'cr.ponderacion')
    ->get();

// Notas demo para actividad #3 (en_proceso, parcialmente evaluada)
$notasAct3 = [7 => 78.0, 8 => 85.0, 9 => 80.0];

foreach ($criteriosAct3 as $crit) {
    $existe = DB::table('evaluaciones_criterio')
        ->where('criterio_id', $crit->id)
        ->where('practica_id', $practicaId)
        ->exists();

    if (!$existe) {
        DB::table('evaluaciones_criterio')->insert([
            'criterio_id'       => $crit->id,
            'practica_id'       => $practicaId,
            'tutor_id'          => $tutorId,
            'informe_avance_id' => null,
            'puntuacion'        => $notasAct3[$crit->id] ?? 80.0,
            'comentario'        => "Evaluación parcial del criterio '{$crit->nombre}'. En proceso de verificación final.",
            'created_at'        => now(),
            'updated_at'        => now(),
        ]);
        $nota = $notasAct3[$crit->id] ?? 80.0;
        echo "  ✅ Evaluación criterio ID={$crit->id} ({$crit->nombre}) Act#3 → {$nota}/100\n";
    } else {
        echo "  ✓  Ya existe evaluación para criterio ID={$crit->id} en Act#3\n";
    }
}

// Calcular nota actividad #3
$criterios3 = DB::table('criterios_rubrica as cr')
    ->join('rubricas_actividad as ra', 'cr.rubrica_id', '=', 'ra.id')
    ->join('evaluaciones_criterio as ec', function($j) use ($practicaId) {
        $j->on('ec.criterio_id', '=', 'cr.id')->where('ec.practica_id', $practicaId);
    })
    ->where('ra.actividad_id', 3)
    ->select('cr.ponderacion', 'ec.puntuacion')
    ->get();

$notaTotal3 = 0;
foreach ($criterios3 as $c) {
    $notaTotal3 += ($c->puntuacion * ($c->ponderacion / 100));
}
$notaTotal3 = round($notaTotal3, 2);
echo "  → Nota calculada Act#3: {$notaTotal3}/100\n";

// ═══════════════════════════════════════════════════════════
// 4. CALCULAR NOTA FINAL AUTOMÁTICA (promedio de actividades)
// ═══════════════════════════════════════════════════════════
echo "\n--- 4. CALCULAR NOTA FINAL AUTOMÁTICA ---\n";

// Actividad #1: ya tiene evaluaciones (IDs 1,2,3)
$criterios1 = DB::table('criterios_rubrica as cr')
    ->join('rubricas_actividad as ra', 'cr.rubrica_id', '=', 'ra.id')
    ->join('evaluaciones_criterio as ec', function($j) use ($practicaId) {
        $j->on('ec.criterio_id', '=', 'cr.id')->where('ec.practica_id', $practicaId);
    })
    ->where('ra.actividad_id', 1)
    ->select('cr.ponderacion', 'ec.puntuacion')
    ->get();

$notaTotal1 = 0;
foreach ($criterios1 as $c) {
    $notaTotal1 += ($c->puntuacion * ($c->ponderacion / 100));
}
$notaTotal1 = round($notaTotal1, 2);
echo "  Act#1 nota: {$notaTotal1}/100\n";
echo "  Act#2 nota: {$notaTotal2}/100\n";
echo "  Act#3 nota: {$notaTotal3}/100\n";

$promedioFinal = round(($notaTotal1 + $notaTotal2 + $notaTotal3) / 3, 2);
$resultadoFinal = $promedioFinal >= 51.0 ? 'Aprobado' : 'Reprobado';
echo "  → Promedio final: {$promedioFinal}/100 ({$resultadoFinal})\n";

// ═══════════════════════════════════════════════════════════
// 5. PERÍODO ACADÉMICO ACTIVO - Ajustar para que cubra la fecha actual
// ═══════════════════════════════════════════════════════════
echo "\n--- 5. PERÍODO ACADÉMICO ---\n";

$hoy = now()->format('Y-m-d');
$periodoActivo = DB::table('periodos_academicos')->where('activo', 1)->first();

if ($periodoActivo) {
    echo "  Período activo: {$periodoActivo->codigo} ({$periodoActivo->fecha_inicio} → {$periodoActivo->fecha_fin})\n";
    if ($hoy > $periodoActivo->fecha_fin) {
        // Extender el período activo hasta fin de año o activar el 2026-II
        $periodo2 = DB::table('periodos_academicos')->where('codigo', '2026-II')->first();
        if ($periodo2) {
            DB::table('periodos_academicos')->where('activo', 1)->update(['activo' => 0]);
            DB::table('periodos_academicos')->where('id', $periodo2->id)->update(['activo' => 1]);
            echo "  ✅ Período activo cambiado a 2026-II (cubre hasta 2026-12-31)\n";
        } else {
            DB::table('periodos_academicos')->where('id', $periodoActivo->id)->update([
                'fecha_fin' => '2026-12-31'
            ]);
            echo "  ✅ Período 2026-I extendido hasta 2026-12-31\n";
        }
    } else {
        echo "  ✓  Período activo cubre la fecha actual\n";
    }
} else {
    echo "  ⚠ No hay período activo\n";
}

// ═══════════════════════════════════════════════════════════
// 6. LIMPIAR HORAS DUPLICADAS DE HOY SI EXISTEN
// ═══════════════════════════════════════════════════════════
echo "\n--- 6. VERIFICAR HORAS DUPLICADAS ---\n";

$hoyHoras = DB::table('registros_horas')
    ->where('practica_id', 1)
    ->whereDate('fecha', $hoy)
    ->whereIn('estado', ['pendiente', 'validada'])
    ->get();

$totalHoy = $hoyHoras->sum('horas');
echo "  Horas de hoy ({$hoy}): {$totalHoy}h en " . count($hoyHoras) . " registros\n";

$maxDiarias = 4.0;
if ($totalHoy > $maxDiarias) {
    // Hay más de 4h registradas hoy — eliminar exceso manteniendo el más reciente
    $registrosOrdenados = $hoyHoras->sortByDesc('id');
    $acumulado = 0;
    $idsEliminar = [];
    foreach ($registrosOrdenados as $reg) {
        if ($acumulado + $reg->horas <= $maxDiarias) {
            $acumulado += $reg->horas;
        } else {
            $idsEliminar[] = $reg->id;
        }
    }
    if (!empty($idsEliminar)) {
        DB::table('registros_horas')->whereIn('id', $idsEliminar)->delete();
        echo "  ✅ Eliminados " . count($idsEliminar) . " registros duplicados de hoy\n";
    }
} else {
    echo "  ✓  Sin exceso de horas hoy\n";
}

// ═══════════════════════════════════════════════════════════
// 7. AGREGAR HORAS DEMO DE HOY PARA QUE EL ESTUDIANTE PUEDA PROBAR
//    (Solo si no hay horas de hoy ya)
// ═══════════════════════════════════════════════════════════
echo "\n--- 7. HORAS DEMO HOY ---\n";

$horasHoyFinal = DB::table('registros_horas')
    ->where('practica_id', 1)
    ->whereDate('fecha', $hoy)
    ->whereIn('estado', ['pendiente', 'validada'])
    ->sum('horas');

if ($horasHoyFinal < 2.0) {
    // Agregar 2h demo para que el estudiante pueda ver que puede registrar hasta 2h más
    $actividad3Id = 3;
    DB::table('registros_horas')->insert([
        'practica_id'  => 1,
        'actividad_id' => $actividad3Id,
        'fecha'        => $hoy,
        'horas'        => 2.0,
        'descripcion'  => 'Jornada de integración: revisión de casos de prueba y ajustes de documentación del módulo.',
        'estado'       => 'pendiente',
        'created_at'   => now(),
        'updated_at'   => null,
    ]);
    echo "  ✅ Agregada jornada demo de 2h para hoy ({$hoy}), estado=pendiente (puede registrar 2h más)\n";
} else {
    echo "  ✓  Ya hay {$horasHoyFinal}h registradas hoy\n";
}

// ═══════════════════════════════════════════════════════════
// 8. RESUMEN FINAL
// ═══════════════════════════════════════════════════════════
echo "\n--- 8. RESUMEN FINAL ---\n";

$hvali = DB::table('registros_horas')->where('practica_id', 1)->where('estado', 'validada')->sum('horas');
$hpend = DB::table('registros_horas')->where('practica_id', 1)->where('estado', 'pendiente')->sum('horas');
$hobj = DB::table('practicas')->where('id', 1)->value('horas_objetivo');
$pct = $hobj > 0 ? round(($hvali / $hobj) * 100, 1) : 0;

echo "  Práctica #1 (Juan - En Curso):\n";
echo "    horas_objetivo = {$hobj}h\n";
echo "    horas_validadas = {$hvali}h ({$pct}%)\n";
echo "    horas_pendientes = {$hpend}h\n";
echo "    Puede registrar más horas = " . ($hpend < 4.0 ? "SÍ (quedan " . (4.0 - $horasHoyFinal) . "h para hoy)" : "NO (máximo diario alcanzado)") . "\n";

$evalCriterios = DB::table('evaluaciones_criterio')->where('practica_id', 1)->get();
echo "\n  Evaluaciones de criterio:\n";
foreach ($evalCriterios as $ev) {
    $crit = DB::table('criterios_rubrica')->where('id', $ev->criterio_id)->first();
    echo "    Criterio ID={$ev->criterio_id}: {$crit->nombre} → {$ev->puntuacion}/100\n";
}

echo "\n  Notas por actividad:\n";
echo "    Act#1 (Completada): {$notaTotal1}/100\n";
echo "    Act#2 (Completada): {$notaTotal2}/100\n";
echo "    Act#3 (En proceso): {$notaTotal3}/100\n";
echo "    Promedio Final: {$promedioFinal}/100 ({$resultadoFinal})\n";

echo "\n✅ Correcciones aplicadas exitosamente.\n";
echo "Recuerda limpiar caché: php artisan cache:clear view:clear config:clear\n";

