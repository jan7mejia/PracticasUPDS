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
    'max_horas_diarias' => (float) env('PRACTICAS_MAX_HORAS_DIARIAS', 8.0),

    // Mínimo de horas registrables por sesión
    'min_horas_registro' => (float) env('PRACTICAS_MIN_HORAS_REGISTRO', 0.5),

    // Tipos de documentos admitidos para expedientes académicos (Solo PDF y Word)
    'extensiones_permitidas' => ['pdf', 'doc', 'docx'],

    // Tamaño máximo de archivo en kilobytes (10 MB)
    'max_archivo_kb' => 10240,
];

