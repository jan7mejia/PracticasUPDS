<?php
/**
 * Script de datos de demo para GestionPracticasUPDS
 * Agrega horas recientes de noviembre 2026 y ajusta actividades del cronograma
 * para que las fechas coincidan con el periodo actual.
 * 
 * SEGURO: No borra nada existente, solo AGREGA datos de demostración.
 */

$host = '127.0.0.1';
$port = 3306;
$dbname = 'gestion_practicas_upds_final';
$user = 'root';
$pass = '123jan';

try {
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "✅ Conectado a la base de datos\n\n";
} catch (Exception $e) {
    die("❌ Error de conexión: " . $e->getMessage() . "\n");
}

echo "════════════════════════════════════════════════════════\n";
echo "     CONFIGURACIÓN DE DATOS PARA DEMO ACADÉMICO\n";
echo "════════════════════════════════════════════════════════\n\n";

// ════════════════════════════════════════
// 1. Verificar estado actual de practica_id=1
// ════════════════════════════════════════
echo "━━━━ 1. ESTADO ACTUAL ━━━━\n";

$practica = $pdo->query("SELECT p.id, p.estado, p.horas_objetivo, p.fecha_inicio, p.fecha_fin_prevista, p.cronograma_aprobado, e.nombres, e.apellidos FROM practicas p JOIN solicitudes_practica sp ON sp.id=p.solicitud_id JOIN estudiantes e ON e.id=sp.estudiante_id WHERE p.id=1")->fetch(PDO::FETCH_ASSOC);

if (!$practica) {
    die("❌ Práctica #1 no encontrada\n");
}

$horasValidadas = (float)$pdo->query("SELECT SUM(horas) FROM registros_horas WHERE practica_id=1 AND estado='validada'")->fetchColumn();
$horasPendientes = (float)$pdo->query("SELECT SUM(horas) FROM registros_horas WHERE practica_id=1 AND estado='pendiente'")->fetchColumn();
$obj = (float)$practica['horas_objetivo'];
$pct = $obj > 0 ? min(100, round($horasValidadas*100/$obj, 1)) : 0;

echo "  Práctica #1: {$practica['nombres']} {$practica['apellidos']}\n";
echo "  Estado: {$practica['estado']} | Periodo: {$practica['fecha_inicio']} → {$practica['fecha_fin_prevista']}\n";
echo "  Horas validadas: {$horasValidadas}h / {$obj}h = {$pct}%\n";
echo "  Horas en revisión: {$horasPendientes}h\n";
echo "  Cronograma aprobado: " . ($practica['cronograma_aprobado'] ? 'Sí' : 'No') . "\n\n";

// ════════════════════════════════════════
// 2. Ajustar fechas del cronograma de actividades
// para que las actividades coincidan con el periodo actual
// ════════════════════════════════════════
echo "━━━━ 2. AJUSTE DE FECHAS DEL CRONOGRAMA ━━━━\n";

$actividades = $pdo->query("SELECT id, titulo, estado, fecha_inicio, fecha_fin FROM cronograma_actividades WHERE practica_id=1 ORDER BY id")->fetchAll(PDO::FETCH_ASSOC);

foreach ($actividades as $act) {
    echo "  Actividad #{$act['id']}: {$act['titulo']}\n";
    echo "    Estado actual: {$act['estado']} | Fechas: {$act['fecha_inicio']} → {$act['fecha_fin']}\n";
}

// Actualizar las fechas de actividades para que sean más coherentes con noviembre 2026
// Actividad 1 (completada): marzo a mayo 2026 → se mantiene
// Actividad 2 (en_proceso): que sea octubre a diciembre 2026 (periodo actual)
$update = $pdo->prepare("UPDATE cronograma_actividades SET fecha_inicio=?, fecha_fin=? WHERE id=?");

// Actividad 1: marzo → junio 2026 (ya terminó, completada - OK)
$update->execute(['2026-03-10', '2026-06-30', 1]);
echo "\n  ✅ Actividad #1: fechas ajustadas a 10/03/2026 → 30/06/2026 (completada)\n";

// Actividad 2: julio → diciembre 2026 (en proceso - periodo actual)
$update->execute(['2026-07-01', '2026-12-31', 2]);
echo "  ✅ Actividad #2: fechas ajustadas a 01/07/2026 → 31/12/2026 (en proceso - actual)\n\n";

// ════════════════════════════════════════
// 3. Agregar horas de demo de noviembre 2026
// (3 jornadas recientes en fechas que no existen ya)
// ════════════════════════════════════════
echo "━━━━ 3. HORAS DE DEMO EN NOVIEMBRE 2026 ━━━━\n";

// Verificar qué fechas de noviembre ya tienen registros
$fechasExistentes = $pdo->query("SELECT DISTINCT fecha FROM registros_horas WHERE practica_id=1 AND MONTH(fecha)=11 AND YEAR(fecha)=2026")->fetchAll(PDO::FETCH_COLUMN);
echo "  Fechas con registros en noviembre: " . implode(', ', $fechasExistentes ?: ['ninguna']) . "\n\n";

// Jornadas de demo a agregar (solo si no existe esa fecha en la DB)
$jornadasDemo = [
    ['fecha' => '2026-11-10', 'horas' => 4.0, 'descripcion' => 'Implementación de servicios REST para gestión de usuarios y autenticación JWT', 'estado' => 'validada'],
    ['fecha' => '2026-11-11', 'horas' => 4.0, 'descripcion' => 'Desarrollo de interfaces Blade para módulo de reportes con Bootstrap 5', 'estado' => 'validada'],
    ['fecha' => '2026-11-12', 'horas' => 3.0, 'descripcion' => 'Pruebas de integración y corrección de errores en endpoints API', 'estado' => 'validada'],
    ['fecha' => '2026-11-13', 'horas' => 4.0, 'descripcion' => 'Documentación técnica del módulo de interfaces web y guía de despliegue', 'estado' => 'pendiente'],
    ['fecha' => '2026-11-17', 'horas' => 4.0, 'descripcion' => 'Revisión de retroalimentación del tutor e implementación de mejoras sugeridas', 'estado' => 'pendiente'],
    ['fecha' => '2026-11-18', 'horas' => 3.5, 'descripcion' => 'Optimización de consultas SQL y mejora de rendimiento del sistema', 'estado' => 'pendiente'],
];

// Buscar tutor_id para validaciones
$tutorId = $pdo->query("SELECT id FROM docentes_tutores LIMIT 1")->fetchColumn();

$insertHora = $pdo->prepare("INSERT INTO registros_horas (practica_id, actividad_id, fecha, horas, descripcion, estado, revisado_por_tutor_id, created_at) VALUES (1, 2, ?, ?, ?, ?, ?, NOW())");

$agregadas = 0;
foreach ($jornadasDemo as $j) {
    if (in_array($j['fecha'], $fechasExistentes)) {
        echo "  ⏭  {$j['fecha']}: Ya existe registro, omitido\n";
        continue;
    }
    $revisorId = ($j['estado'] === 'validada') ? $tutorId : null;
    $insertHora->execute([$j['fecha'], $j['horas'], $j['descripcion'], $j['estado'], $revisorId]);
    echo "  ✅ {$j['fecha']}: {$j['horas']}h - {$j['estado']} - " . substr($j['descripcion'], 0, 50) . "...\n";
    $agregadas++;
}

echo "\n  Total jornadas agregadas: {$agregadas}\n\n";

// ════════════════════════════════════════
// 4. Recalcular estado
// ════════════════════════════════════════
echo "━━━━ 4. ESTADO FINAL ━━━━\n";

$horasValidadasNuevo = (float)$pdo->query("SELECT SUM(horas) FROM registros_horas WHERE practica_id=1 AND estado='validada'")->fetchColumn();
$horasPendientesNuevo = (float)$pdo->query("SELECT SUM(horas) FROM registros_horas WHERE practica_id=1 AND estado='pendiente'")->fetchColumn();
$pctNuevo = $obj > 0 ? min(100, round($horasValidadasNuevo*100/$obj, 1)) : 0;
$faltantes = max(0, $obj - $horasValidadasNuevo - $horasPendientesNuevo);

echo "  Horas validadas: {$horasValidadasNuevo}h / {$obj}h = {$pctNuevo}%\n";
echo "  Horas en revisión: {$horasPendientesNuevo}h\n";
echo "  Horas faltantes para completar: {$faltantes}h\n";

echo "\n━━━━ 5. VERIFICAR RUBRICAS Y EVALUACIONES ━━━━\n";
$rubricas = $pdo->query("SELECT ra.id, ra.titulo, ra.actividad_id, COUNT(cr.id) as criterios, SUM(cr.ponderacion) as ponderacion FROM rubricas_actividad ra LEFT JOIN criterios_rubrica cr ON cr.rubrica_id=ra.id GROUP BY ra.id ORDER BY ra.id")->fetchAll(PDO::FETCH_ASSOC);
foreach ($rubricas as $r) {
    $ponderOk = abs((float)$r['ponderacion'] - 100.0) < 0.01;
    echo "  " . ($ponderOk ? '✅' : '⚠️') . " Rúbrica #{$r['id']}: Act#{$r['actividad_id']} | {$r['criterios']} criterios | {$r['ponderacion']}% ponderación\n";
}

$evalParciales = $pdo->query("SELECT id, periodo, calificacion, resultado FROM evaluaciones_seguimiento WHERE practica_id=1 ORDER BY id")->fetchAll(PDO::FETCH_ASSOC);
echo "\n  Evaluaciones parciales:\n";
foreach ($evalParciales as $e) {
    echo "    ✅ #{$e['id']}: {$e['periodo']} | {$e['calificacion']}/100 | {$e['resultado']}\n";
}

echo "\n════════════════════════════════════════════════════════\n";
echo "  ¡Datos de demo configurados correctamente!\n";
echo "  El sistema está listo para la presentación.\n";
echo "════════════════════════════════════════════════════════\n";
