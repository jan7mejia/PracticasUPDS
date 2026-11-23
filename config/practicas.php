<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Parámetros Institucionales de Prácticas Profesionales - UPDS
    |--------------------------------------------------------------------------
    |
    | Define los valores estándar y configuraciones autorizadas para el ciclo
    | de prácticas profesionales, eliminando valores fijos en el código fuente.
    |
    */

    // Horas académicas requeridas por defecto según reglamento
    'horas_objetivo' => (float) env('PRACTICAS_HORAS_OBJETIVO', 240.0),

    // Duración estimada de la práctica en meses
    'duracion_meses' => (int) env('PRACTICAS_DURACION_MESES', 3),

    // Límite máximo de horas por jornada diaria (para evitar registros anómalos)
    'max_horas_diarias' => (float) env('PRACTICAS_MAX_HORAS_DIARIAS', 4.0),

    // Horas diarias estándar por día hábil (L-V)
    'horas_por_dia_habil' => (float) env('PRACTICAS_HORAS_POR_DIA', 4.0),

    // Mínimo de horas registrables por sesión
    'min_horas_registro' => (float) env('PRACTICAS_MIN_HORAS_REGISTRO', 0.5),

    // Límite de excepciones permitidas durante la práctica
    'max_faltas_justificadas' => (int) env('PRACTICAS_MAX_FALTAS', 2),
    'max_incompletas_justificadas' => (int) env('PRACTICAS_MAX_INCOMPLETAS', 2),

    // Porcentaje mínimo de cumplimiento de horas para cierre institucional
    'porcentaje_minimo_aprobacion_horas' => (float) env('PRACTICAS_MIN_PCT_HORAS', 90.0),

    // Calificación mínima de aprobación
    'nota_minima_aprobacion' => (float) env('PRACTICAS_NOTA_MINIMA', 51.0),

    // Tipos de documentos admitidos para expedientes académicos (Solo PDF y Word)
    'extensiones_permitidas' => ['pdf', 'doc', 'docx'],

    // Tamaño máximo de archivo en kilobytes (10 MB)
    'max_archivo_kb' => 10240,
];

