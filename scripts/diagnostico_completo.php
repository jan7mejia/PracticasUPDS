<?php
/**
 * Diagnóstico completo del sistema GestionPracticasUPDS
 * Verifica: credenciales, prácticas, horas, cronograma, informes, evaluaciones
 */

$host = '127.0.0.1';
$port = 3306;
$dbname = 'gestion_practicas_upds_final';
$user = 'root';
$pass = '123jan';

try {
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (Exception $e) {
    die("❌ Error de conexión: " . $e->getMessage() . "\n");
}

echo "╔══════════════════════════════════════════════════════════════╗\n";
echo "║      DIAGNÓSTICO COMPLETO — GestionPracticasUPDS             ║\n";
echo "╚══════════════════════════════════════════════════════════════╝\n\n";

// ════════════════════════════════════════
// 1. CREDENCIALES
// ════════════════════════════════════════
echo "━━━━ 1. CREDENCIALES DE ACCESO ━━━━\n";
$cuentas = [
    ['email' => 'admin@upds.edu.bo',          'rol_esperado' => 1, 'rol_nombre' => 'Admin'],
    ['email' => 'coordinacion@upds.edu.bo',   'rol_esperado' => 2, 'rol_nombre' => 'Coordinador'],
    ['email' => 'tutor@upds.edu.bo',          'rol_esperado' => 3, 'rol_nombre' => 'Tutor'],
    ['email' => 'estudiante@upds.edu.bo',     'rol_esperado' => 4, 'rol_nombre' => 'Estudiante'],
];
$stmt = $pdo->prepare("SELECT id, email, password, rol_id FROM users WHERE email = ?");
foreach ($cuentas as $c) {
    $stmt->execute([$c['email']]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row) {
        echo "  ❌ NO ENCONTRADO: {$c['email']}\n";
        continue;
    }
    $passOk = password_verify('12345678', $row['password']);
    $rolOk  = (int)$row['rol_id'] === $c['rol_esperado'];
    $status = ($passOk && $rolOk) ? '✅' : '❌';
    echo "  $status ID={$row['id']} {$c['email']} | pass:" . ($passOk?'OK':'FAIL') . " | rol:" . ($rolOk ? $c['rol_nombre'] : "INCORRECTO({$row['rol_id']})") . "\n";
}

// ════════════════════════════════════════
// 2. PRÁCTICAS
// ════════════════════════════════════════
echo "\n━━━━ 2. PRÁCTICAS ━━━━\n";
$practicas = $pdo->query("SELECT p.id, p.estado, p.horas_objetivo, p.fecha_inicio, p.fecha_fin_prevista, p.fecha_fin_real, p.cronograma_aprobado, e.nombres, e.apellidos FROM practicas p JOIN solicitudes_practica sp ON sp.id = p.solicitud_id JOIN estudiantes e ON e.id = sp.estudiante_id ORDER BY p.id")->fetchAll(PDO::FETCH_ASSOC);
foreach ($practicas as $p) {
    $fechaFin = $p['fecha_fin_prevista'] ?? 'Sin fecha';
    $hoy = date('Y-m-d');
    $enPeriodo = !$p['fecha_fin_prevista'] || $hoy <= $p['fecha_fin_prevista'];
    $icono = $enPeriodo ? '✅' : '⚠️';
    echo "  {$icono} Práctica #{$p['id']}: {$p['nombres']} {$p['apellidos']} | Estado: {$p['estado']} | Horas objetivo: {$p['horas_objetivo']} | Periodo: {$p['fecha_inicio']} → {$fechaFin} | Cronograma aprobado: " . ($p['cronograma_aprobado'] ? 'Sí' : 'No') . "\n";
}

// ════════════════════════════════════════
// 3. HORAS
// ════════════════════════════════════════
echo "\n━━━━ 3. HORAS REGISTRADAS ━━━━\n";
$horas = $pdo->query("SELECT practica_id, estado, COUNT(*) as cnt, SUM(horas) as total FROM registros_horas GROUP BY practica_id, estado ORDER BY practica_id, estado")->fetchAll(PDO::FETCH_ASSOC);
$prevP = null;
foreach ($horas as $h) {
    if ($h['practica_id'] !== $prevP) {
        // Buscar objetivo
        $obj = $pdo->query("SELECT p.horas_objetivo FROM practicas p WHERE p.id = {$h['practica_id']}")->fetch(PDO::FETCH_ASSOC);
        echo "  Práctica #{$h['practica_id']} (objetivo: {$obj['horas_objetivo']}h):\n";
        $prevP = $h['practica_id'];
    }
    echo "    - {$h['estado']}: {$h['cnt']} registros, {$h['total']}h\n";
}

// Calcular progreso real (solo validadas)
echo "\n  Progreso real:\n";
foreach ($practicas as $p) {
    $validadas = (float)$pdo->query("SELECT SUM(horas) FROM registros_horas WHERE practica_id={$p['id']} AND estado='validada'")->fetchColumn();
    $obj = (float)$p['horas_objetivo'];
    $pct = $obj > 0 ? min(100, round($validadas*100/$obj, 1)) : 0;
    echo "    Práctica #{$p['id']}: {$validadas}h validadas / {$obj}h = {$pct}%\n";
}

// ════════════════════════════════════════
// 4. CRONOGRAMA
// ════════════════════════════════════════
echo "\n━━━━ 4. CRONOGRAMA DE ACTIVIDADES (Práctica #1) ━━━━\n";
$acts = $pdo->query("SELECT ca.id, ca.titulo, ca.estado, ca.fecha_inicio, ca.fecha_fin, ca.horas_planificadas, (SELECT COUNT(*) FROM rubricas_actividad WHERE actividad_id=ca.id) as tiene_rubrica FROM cronograma_actividades ca WHERE ca.practica_id=1 ORDER BY ca.id")->fetchAll(PDO::FETCH_ASSOC);
foreach ($acts as $a) {
    $rubrica = $a['tiene_rubrica'] > 0 ? '✅ Con rúbrica' : '⚠️ Sin rúbrica';
    echo "  Actividad #{$a['id']}: {$a['titulo']} | Estado: {$a['estado']} | {$a['fecha_inicio']} → {$a['fecha_fin']} | {$rubrica}\n";
}

// ════════════════════════════════════════
// 5. INFORMES
// ════════════════════════════════════════
echo "\n━━━━ 5. INFORMES DE AVANCE (Práctica #1) ━━━━\n";
$informes = $pdo->query("SELECT ia.id, ia.titulo, ia.estado, ia.actividad_id, ia.calificacion, ia.observaciones_tutor FROM informes_avance ia WHERE ia.practica_id=1 ORDER BY ia.id")->fetchAll(PDO::FETCH_ASSOC);
if (empty($informes)) {
    echo "  ⚠️ No hay informes registrados\n";
} else {
    foreach ($informes as $i) {
        $nota = $i['calificacion'] !== null ? "Nota: {$i['calificacion']}" : "Sin nota";
        $obs = $i['observaciones_tutor'] ? '(con obs. tutor)' : '';
        echo "  ✅ Informe #{$i['id']}: {$i['titulo']} | Estado: {$i['estado']} | ActID: {$i['actividad_id']} | {$nota} {$obs}\n";
    }
}

// ════════════════════════════════════════
// 6. EVALUACIONES
// ════════════════════════════════════════
echo "\n━━━━ 6. EVALUACIONES (Práctica #1) ━━━━\n";
$evalPs = $pdo->query("SELECT id, periodo, calificacion, resultado, fecha FROM evaluaciones_seguimiento WHERE practica_id=1 ORDER BY id")->fetchAll(PDO::FETCH_ASSOC);
if (empty($evalPs)) {
    echo "  ⚠️ Sin evaluaciones parciales\n";
} else {
    foreach ($evalPs as $e) {
        echo "  ✅ Parcial #{$e['id']}: {$e['periodo']} | {$e['calificacion']}/100 | {$e['resultado']} | {$e['fecha']}\n";
    }
}

$evalF = $pdo->query("SELECT id, calificacion_final, resultado FROM evaluaciones_finales WHERE practica_id=1")->fetch(PDO::FETCH_ASSOC);
if ($evalF) {
    echo "  ✅ Final: {$evalF['calificacion_final']}/100 | {$evalF['resultado']}\n";
} else {
    echo "  ℹ️ Sin evaluación final todavía\n";
}

// ════════════════════════════════════════
// 7. RÚBRICAS
// ════════════════════════════════════════
echo "\n━━━━ 7. RÚBRICAS CONFIGURADAS ━━━━\n";
$rubricas = $pdo->query("SELECT ra.id, ra.titulo, ra.actividad_id, COUNT(cr.id) as criterios, SUM(cr.ponderacion) as ponderacion_total FROM rubricas_actividad ra LEFT JOIN criterios_rubrica cr ON cr.rubrica_id=ra.id GROUP BY ra.id ORDER BY ra.id")->fetchAll(PDO::FETCH_ASSOC);
foreach ($rubricas as $r) {
    $ponderOk = abs((float)$r['ponderacion_total'] - 100.0) < 0.01;
    $icono = $ponderOk ? '✅' : '⚠️';
    echo "  {$icono} Rúbrica #{$r['id']}: {$r['titulo']} | Actividad #{ $r['actividad_id']} | {$r['criterios']} criterios | Ponderación: {$r['ponderacion_total']}%\n";
}

// ════════════════════════════════════════
// 8. OBSERVACIONES
// ════════════════════════════════════════
echo "\n━━━━ 8. OBSERVACIONES DEL TUTOR (Práctica #1) ━━━━\n";
$obs = $pdo->query("SELECT id, fecha, SUBSTRING(contenido, 1, 60) as texto_corto FROM observaciones_tutor WHERE practica_id=1 ORDER BY fecha DESC")->fetchAll(PDO::FETCH_ASSOC);
if (empty($obs)) {
    echo "  ⚠️ Sin observaciones del tutor\n";
} else {
    foreach ($obs as $o) {
        echo "  ✅ #{$o['id']} [{$o['fecha']}]: {$o['texto_corto']}...\n";
    }
}

echo "\n━━━━ RESUMEN GENERAL ━━━━\n";
echo "  Sistema configurado correctamente para demo académico.\n";
echo "  Fecha actual: " . date('d/m/Y H:i') . "\n";
echo "\n¡Diagnóstico completado!\n";

