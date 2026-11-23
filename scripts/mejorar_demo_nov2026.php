<?php
/**
 * Script de mejora del demo - Noviembre 2026
 * Agrega actividad #3, corrige informe observado, ajusta horas
 * NO modifica ni elimina datos existentes irreemplazables
 */

$pdo = new PDO('mysql:host=127.0.0.1;port=3306;dbname=gestion_practicas_upds_final;charset=utf8mb4', 'root', '123jan');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

echo "=== MEJORA DEMO NOV 2026 ===\n\n";

// 1. Agregar Actividad #3 al cronograma de Juan (practica_id=1) si no existe
$stmt = $pdo->query("SELECT COUNT(*) FROM cronograma_actividades WHERE practica_id=1");
$total = $stmt->fetchColumn();
echo "Actividades actuales: $total\n";

if ($total < 3) {
    $pdo->exec("
        INSERT INTO cronograma_actividades 
            (practica_id, titulo, objetivo, entregable_esperado, fecha_inicio, fecha_fin, 
             horas_planificadas, estado, aprobada_coordinacion, rubrica_definida)
        VALUES 
            (1, 'Pruebas de integración y documentación final', 
             'Verificar el funcionamiento completo del sistema y elaborar la documentación técnica final',
             'Manual de usuario, reporte de pruebas y documentación técnica del sistema',
             '2026-11-01', '2026-12-31', 100.00, 'en_proceso', 1, 0)
    ");
    $actId3 = $pdo->lastInsertId();
    echo "✅ Actividad #3 creada (ID: $actId3)\n";
    
    // Agregar rúbrica para la actividad #3
    $pdo->exec("
        INSERT INTO rubricas_actividad (actividad_id, titulo, descripcion, created_at, updated_at)
        VALUES ($actId3, 'Rúbrica: Pruebas y Documentación', 
                'Evalúa la calidad de las pruebas realizadas y la documentación entregada', NOW(), NOW())
    ");
    $rubricaId3 = $pdo->lastInsertId();
    echo "✅ Rúbrica para Actividad #3 creada (ID: $rubricaId3)\n";
    
    // Criterios de la rúbrica
    $criterios3 = [
        ['Cobertura y calidad de las pruebas realizadas', 40],
        ['Completitud y claridad de la documentación técnica', 35],
        ['Presentación y estructura del informe final', 25],
    ];
    foreach ($criterios3 as $c) {
        $stmt = $pdo->prepare("
            INSERT INTO criterios_rubrica (rubrica_id, nombre, ponderacion, created_at, updated_at) 
            VALUES (?, ?, ?, NOW(), NOW())
        ");
        $stmt->execute([$rubricaId3, $c[0], $c[1]]);
    }
    echo "✅ 3 criterios de rúbrica para Actividad #3 creados\n";
} else {
    echo "ℹ️  Ya hay $total actividades, no se agrega la #3\n";
    $actId3 = $pdo->query("SELECT id FROM cronograma_actividades WHERE practica_id=1 ORDER BY id DESC LIMIT 1")->fetchColumn();
}

// 2. Agregar un registro de horas de hoy vinculado a Actividad #3
$hoy = date('Y-m-d');
$stmt = $pdo->prepare("SELECT SUM(horas) FROM registros_horas WHERE practica_id=1 AND fecha=? AND estado IN ('pendiente','validada')");
$stmt->execute([$hoy]);
$horasHoy = (float)$stmt->fetchColumn();
echo "\nHoras registradas hoy ($hoy): {$horasHoy}h\n";

if ($horasHoy < 2) {
    $pdo->prepare("
        INSERT INTO registros_horas (practica_id, actividad_id, fecha, horas, descripcion, estado, created_at)
        VALUES (1, ?, ?, 2.0, 'Desarrollo de casos de prueba unitarios y de integración para los módulos principales del sistema', 'pendiente', NOW())
    ")->execute([$actId3, $hoy]);
    echo "✅ Registro de 2h de hoy agregado (vinculado a Actividad #3, estado pendiente)\n";
} else {
    echo "ℹ️  Ya hay {$horasHoy}h registradas hoy, no se agrega registro extra\n";
}

// 3. Verificar/crear informe observado con texto de observación para demo
$stmt = $pdo->query("SELECT COUNT(*) FROM informes_avance WHERE practica_id=1 AND estado='observado'");
$informesObservados = $stmt->fetchColumn();
echo "\nInformes observados actuales: $informesObservados\n";

if ($informesObservados == 0) {
    // Verificar si hay informes en la practica
    $stmt = $pdo->query("SELECT id, titulo, estado FROM informes_avance WHERE practica_id=1 ORDER BY id DESC LIMIT 1");
    $ultimoInforme = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($ultimoInforme && $ultimoInforme['estado'] === 'enviado') {
        // Marcar el último informe enviado como observado para el demo
        $tutorId = $pdo->query("SELECT id FROM docentes_tutores WHERE usuario_id=4 LIMIT 1")->fetchColumn();
        if (!$tutorId) {
            $tutorId = $pdo->query("SELECT id FROM docentes_tutores LIMIT 1")->fetchColumn();
        }
        
        $pdo->prepare("
            UPDATE informes_avance 
            SET estado='observado',
                observaciones_tutor='El informe presenta las bases correctas pero requiere mayor detalle en la sección de pruebas realizadas. Por favor incluir los casos de prueba con resultados específicos y adjuntar capturas de pantalla de los resultados obtenidos. La documentación de la API también necesita completarse con los endpoints faltantes.',
                revisado_por_tutor_id=?,
                fecha_revision=NOW()
            WHERE id=?
        ")->execute([$tutorId, $ultimoInforme['id']]);
        echo "✅ Informe ID {$ultimoInforme['id']} marcado como OBSERVADO con texto de observación\n";
    } else {
        echo "ℹ️  No hay informes en estado 'enviado' para convertir a observado\n";
    }
} else {
    // Asegurarse que el informe observado tiene texto
    $pdo->exec("
        UPDATE informes_avance 
        SET observaciones_tutor=COALESCE(NULLIF(TRIM(observaciones_tutor),''), 
            'El informe requiere correcciones: incluir mayor detalle en los resultados de pruebas y completar la documentación técnica de los endpoints de la API.')
        WHERE practica_id=1 AND estado='observado'
    ");
    echo "✅ Texto de observación verificado/completado en informes observados existentes\n";
}

// 4. Verificar estado final
echo "\n=== ESTADO FINAL ===\n";
$stmt = $pdo->query("
    SELECT 
        SUM(CASE WHEN estado='validada' THEN horas ELSE 0 END) as validadas,
        SUM(CASE WHEN estado='pendiente' THEN horas ELSE 0 END) as pendientes,
        COUNT(*) as total_registros
    FROM registros_horas WHERE practica_id=1
");
$res = $stmt->fetch(PDO::FETCH_ASSOC);
$pct = round(($res['validadas'] / 240.0) * 100, 1);
echo "Horas validadas: {$res['validadas']}h / 240h = {$pct}%\n";
echo "Horas en revisión: {$res['pendientes']}h\n";
echo "Total registros: {$res['total_registros']}\n";

$stmt = $pdo->query("SELECT id, titulo, estado FROM cronograma_actividades WHERE practica_id=1 ORDER BY id");
echo "\nActividades del cronograma:\n";
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo "  ID {$row['id']}: {$row['titulo']} [{$row['estado']}]\n";
}

$stmt = $pdo->query("SELECT id, titulo, estado, LEFT(observaciones_tutor,50) as obs FROM informes_avance WHERE practica_id=1 ORDER BY id");
echo "\nInformes de avance:\n";
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo "  ID {$row['id']}: {$row['titulo']} [{$row['estado']}]" . ($row['obs'] ? " - Obs: {$row['obs']}..." : '') . "\n";
}

echo "\n✅ Script completado exitosamente.\n";

