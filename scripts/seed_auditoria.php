<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\AuditoriaAdministrativa;

$admin = User::where('email', 'admin@upds.edu.bo')->first();
$coord = User::where('email', 'coordinacion@upds.edu.bo')->first();
$tutor = User::where('email', 'tutor@upds.edu.bo')->first();
$est = User::where('email', 'estudiante@upds.edu.bo')->first();

if ($admin && AuditoriaAdministrativa::count() === 0) {
    AuditoriaAdministrativa::create([
        'admin_id' => $admin->id,
        'accion' => 'ACTIVACION_CUENTA',
        'usuario_afectado_id' => $est?->id,
        'detalles' => json_encode(['email' => $est?->email, 'rol' => 'Estudiante']),
        'motivo' => 'Activación de matrícula para Prácticas Profesionales 2026-I',
        'ip_address' => '127.0.0.1',
        'created_at' => now()->subDays(5),
    ]);
    AuditoriaAdministrativa::create([
        'admin_id' => $admin->id,
        'accion' => 'CAMBIO_ROL',
        'usuario_afectado_id' => $tutor?->id,
        'detalles' => json_encode(['rol_anterior' => 'Docente', 'nuevo_rol' => 'Docente Tutor']),
        'motivo' => 'Asignación de rol Docente Tutor para seguimiento de prácticas',
        'ip_address' => '127.0.0.1',
        'created_at' => now()->subDays(10),
    ]);
    AuditoriaAdministrativa::create([
        'admin_id' => $admin->id,
        'accion' => 'CREACION_USUARIO',
        'usuario_afectado_id' => $coord?->id,
        'detalles' => json_encode(['nombre' => $coord?->name, 'rol' => 'Coordinador']),
        'motivo' => 'Alta de usuario de Coordinación Académica',
        'ip_address' => '127.0.0.1',
        'created_at' => now()->subDays(20),
    ]);
    echo "Auditorias sembradas: " . AuditoriaAdministrativa::count() . PHP_EOL;
} else {
    echo "Auditorias existentes: " . AuditoriaAdministrativa::count() . PHP_EOL;
}

