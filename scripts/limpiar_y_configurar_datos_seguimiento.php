<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use App\Models\Practica;
use App\Models\CronogramaActividad;
use App\Models\RubricaActividad;
use App\Models\CriterioRubrica;
use App\Models\InformeAvance;
use App\Models\ObservacionTutor;
use App\Models\EvaluacionSeguimiento;
use App\Models\EvaluacionCriterio;

echo "Iniciando saneamiento y coherencia de datos para seguimiento...\n";

DB::transaction(function () {
    // 1. Limpiar registros de prueba basura de informes_avance de la práctica 1
    // Eliminar informes informales: prueba, prueba 1.0, prueba 1.1, prueba 1.2, prueba 1.3
    $informesAEliminar = InformeAvance::where('practica_id', 1)
        ->where(function ($q) {
            $q->where('titulo', 'like', 'prueba%')
              ->orWhere('titulo', 'like', 'Reporte N°%');
        })->get();

    foreach ($informesAEliminar as $inf) {
        EvaluacionCriterio::where('informe_avance_id', $inf->id)->delete();
        ObservacionTutor::where('informe_id', $inf->id)->delete();
        $inf->delete();
    }

    // Limpiar observaciones basura de la práctica 1
    ObservacionTutor::where('practica_id', 1)
        ->where(function ($q) {
            $q->where('detalle', 'like', '%prueba%')
              ->orWhere('detalle', 'like', '%gfhdgshdf%')
              ->orWhere('detalle', 'like', '%tretrtrew%')
              ->orWhere('detalle', 'like', '%feawfew%')
              ->orWhere('detalle', 'like', '%7777777777%');
        })->delete();

    // Limpiar evaluaciones parciales manuales basura de la práctica 1
    EvaluacionSeguimiento::where('practica_id', 1)->delete();

    // Asegurar que las actividades de la práctica 1 existan correctamente
    $act1 = CronogramaActividad::firstOrCreate(
        ['practica_id' => 1, 'titulo' => 'Análisis de requerimientos y diseño de base de datos'],
        [
            'objetivo' => 'Modelar y documentar la arquitectura de datos del nuevo módulo institucional',
            'entregable_esperado' => 'Documento SRS y Script DDL de Base de Datos relacional normalizada',
            'fecha_inicio' => '2026-03-10',
            'fecha_fin' => '2026-03-31',
            'horas_planificadas' => 60.00,
            'estado' => 'completada',
            'fecha_completada' => '2026-03-31',
            'aprobada_coordinacion' => true,
            'rubrica_definida' => true,
            'observacion' => 'Evaluada mediante rúbrica técnica. Nota obtenida: 88.50/100 (Aprobado).'
        ]
    );

    $act2 = CronogramaActividad::firstOrCreate(
        ['practica_id' => 1, 'titulo' => 'Desarrollo de servicios API e interfaces web'],
        [
            'objetivo' => 'Implementar endpoints REST y vistas Blade con Bootstrap responsivo',
            'entregable_esperado' => 'Módulos CRUD funcionales con validaciones en servidor y cliente',
            'fecha_inicio' => '2026-04-01',
            'fecha_fin' => '2026-05-15',
            'horas_planificadas' => 80.00,
            'estado' => 'en_proceso',
            'aprobada_coordinacion' => true,
            'rubrica_definida' => true,
            'observacion' => 'En desarrollo. Informe técnico N° 2 presentado para revisión y evaluación.'
        ]
    );

    // Asegurar aprobación del cronograma en la práctica 1
    Practica::where('id', 1)->update([
        'cronograma_aprobado' => true,
        'cronograma_aprobado_at' => '2026-03-05 10:00:00',
        'cronograma_aprobado_por' => 1,
    ]);

    // Rúbrica para Actividad 1
    $rubrica1 = RubricaActividad::updateOrCreate(
        ['actividad_id' => $act1->id],
        [
            'titulo' => 'Rúbrica Técnica: Análisis y Diseño de Base de Datos',
            'descripcion' => 'Evaluación de arquitectura de datos, normalización y especificación de requerimientos de software.',
        ]
    );

    $rubrica1->criterios()->delete();
    $crit1_1 = CriterioRubrica::create([
        'rubrica_id' => $rubrica1->id,
        'nombre' => 'Levantamiento de Requerimientos y Casos de Uso',
        'descripcion' => 'Claridad, alcance y trazabilidad de los requerimientos funcionales y no funcionales.',
        'ponderacion' => 35.00,
        'puntuacion_maxima' => 100.00,
    ]);
    $crit1_2 = CriterioRubrica::create([
        'rubrica_id' => $rubrica1->id,
        'nombre' => 'Modelo Entidad-Relación y Normalización (3FN)',
        'descripcion' => 'Estructura relacional correcta, integridad referencial y claves foráneas bien tipadas.',
        'ponderacion' => 40.00,
        'puntuacion_maxima' => 100.00,
    ]);
    $crit1_3 = CriterioRubrica::create([
        'rubrica_id' => $rubrica1->id,
        'nombre' => 'Diccionario de Datos y Reglas de Integridad',
        'descripcion' => 'Definición de restricciones CHECK, NOT NULL, valores por defecto y documentación de campos.',
        'ponderacion' => 25.00,
        'puntuacion_maxima' => 100.00,
    ]);

    // Rúbrica para Actividad 2
    $rubrica2 = RubricaActividad::updateOrCreate(
        ['actividad_id' => $act2->id],
        [
            'titulo' => 'Rúbrica Técnica: Desarrollo de API e Interfaces Web',
            'descripcion' => 'Evaluación de arquitectura de controladores, vistas responsivas y validaciones de seguridad.',
        ]
    );

    $rubrica2->criterios()->delete();
    $crit2_1 = CriterioRubrica::create([
        'rubrica_id' => $rubrica2->id,
        'nombre' => 'Arquitectura de Controladores y Rutas API',
        'descripcion' => 'Organización modular de rutas, inyección de dependencias y respuestas JSON estándar.',
        'ponderacion' => 40.00,
        'puntuacion_maxima' => 100.00,
    ]);
    $crit2_2 = CriterioRubrica::create([
        'rubrica_id' => $rubrica2->id,
        'nombre' => 'Diseño Responsivo con Blade y Bootstrap 5',
        'descripcion' => 'Interfaces amigables, accesibles y adaptadas a pantallas móviles y de escritorio.',
        'ponderacion' => 35.00,
        'puntuacion_maxima' => 100.00,
    ]);
    $crit2_3 = CriterioRubrica::create([
        'rubrica_id' => $rubrica2->id,
        'nombre' => 'Validación de Formularios y Manejo de Errores',
        'descripcion' => 'Mensajes claros de error en español y protección contra entradas erróneas.',
        'ponderacion' => 25.00,
        'puntuacion_maxima' => 100.00,
    ]);

    // Crear Informe 1 vinculado a Actividad 1 (Aprobado y Calificado)
    $inf1 = InformeAvance::create([
        'practica_id' => 1,
        'actividad_id' => $act1->id,
        'version' => 1,
        'titulo' => 'Informe Técnico N° 1 - Análisis de Requerimientos y Diseño BD',
        'periodo_inicio' => '2026-03-10',
        'periodo_fin' => '2026-03-31',
        'resumen' => 'Se completó el modelado entidad-relación en 3FN, el diccionario de datos con restricciones de integridad y la especificación de casos de uso del módulo institucional.',
        'estado' => 'revisado',
        'fecha_envio' => '2026-03-31 17:00:00',
        'revisado_por_tutor_id' => 1,
        'fecha_revision' => '2026-04-02 11:30:00',
        'calificacion' => 88.50,
        'observaciones_tutor' => 'Excelente rigor técnico en la arquitectura de datos. Aprobado conforme a rúbrica.',
        'ruta_privada' => 'practicas/1/informes/informe_tecnico_1.pdf',
    ]);

    // Evaluaciones por Criterio para Informe 1
    EvaluacionCriterio::create([
        'criterio_id' => $crit1_1->id,
        'informe_avance_id' => $inf1->id,
        'practica_id' => 1,
        'tutor_id' => 1,
        'puntuacion' => 90.00,
        'comentario' => 'Casos de uso detallados y con flujo alternativo completo.',
    ]);
    EvaluacionCriterio::create([
        'criterio_id' => $crit1_2->id,
        'informe_avance_id' => $inf1->id,
        'practica_id' => 1,
        'tutor_id' => 1,
        'puntuacion' => 85.00,
        'comentario' => 'Modelo relacional normalizado y claves foráneas bien tipadas.',
    ]);
    EvaluacionCriterio::create([
        'criterio_id' => $crit1_3->id,
        'informe_avance_id' => $inf1->id,
        'practica_id' => 1,
        'tutor_id' => 1,
        'puntuacion' => 92.00,
        'comentario' => 'Diccionario exhaustivo con restricciones NOT NULL y reglas de negocio.',
    ]);

    // Evaluación Parcial consolidada en evaluaciones_seguimiento
    EvaluacionSeguimiento::create([
        'practica_id' => 1,
        'tutor_id' => 1,
        'fecha' => '2026-04-02',
        'periodo' => '1er Parcial - Análisis y Modelado BD',
        'calificacion' => 88.50,
        'resultado' => 'Aprobado',
        'comentario' => 'Desempeño sobresaliente en la fase de análisis y modelado de datos según rúbrica.',
    ]);

    // Crear Informe 2 vinculado a Actividad 2 (Enviado para revisión y calificación)
    $inf2 = InformeAvance::create([
        'practica_id' => 1,
        'actividad_id' => $act2->id,
        'version' => 1,
        'titulo' => 'Informe Técnico N° 2 - Servicios API e Interfaces Web',
        'periodo_inicio' => '2026-04-01',
        'periodo_fin' => '2026-05-15',
        'resumen' => 'Implementación de rutas RESTful, controladores con validación FormRequest y vistas Blade estilizadas con Bootstrap 5 para el módulo de vinculación institucional.',
        'estado' => 'enviado',
        'fecha_envio' => '2026-05-15 16:45:00',
        'ruta_privada' => 'practicas/1/informes/informe_tecnico_2.pdf',
    ]);

    // Observaciones del tutor profesionales
    ObservacionTutor::create([
        'practica_id' => 1,
        'tutor_id' => 1,
        'informe_id' => $inf1->id,
        'detalle' => 'Informe N° 1 revisado y aprobado formalmente con 88.50/100 puntos.',
        'fecha' => '2026-04-02 11:35:00',
    ]);
    ObservacionTutor::create([
        'practica_id' => 1,
        'tutor_id' => 1,
        'detalle' => 'El estudiante mantiene excelente constancia en sus bitácoras de horas y cumplimiento de actividades.',
        'fecha' => '2026-04-20 09:15:00',
    ]);

    // Asegurar archivo físico simulado en storage si no existe
    $dir = storage_path('app/practicas/1/informes');
    if (!file_exists($dir)) {
        mkdir($dir, 0777, true);
    }
    file_put_contents($dir . '/informe_tecnico_1.pdf', "%PDF-1.4\n1 0 obj<</Type/Catalog/Pages 2 0 R>>endobj\n2 0 obj<</Type/Pages/Count 1/Kids[3 0 R]>>endobj\n3 0 obj<</Type/Page/MediaBox[0 0 612 792]/Parent 2 0 R/Resources<<>>>>endobj\nxref\n0 4\n0000000000 65535 f\n0000000009 00000 n\n0000000052 00000 n\n0000000101 00000 n\ntrailer<</Size 4/Root 1 0 R>>\nstartxref\n185\n%%EOF");
    file_put_contents($dir . '/informe_tecnico_2.pdf', "%PDF-1.4\n1 0 obj<</Type/Catalog/Pages 2 0 R>>endobj\n2 0 obj<</Type/Pages/Count 1/Kids[3 0 R]>>endobj\n3 0 obj<</Type/Page/MediaBox[0 0 612 792]/Parent 2 0 R/Resources<<>>>>endobj\nxref\n0 4\n0000000000 65535 f\n0000000009 00000 n\n0000000052 00000 n\n0000000101 00000 n\ntrailer<</Size 4/Root 1 0 R>>\nstartxref\n185\n%%EOF");
});

echo "Saneamiento completado con éxito. Informes y rúbricas configurados formalmente.\n";

