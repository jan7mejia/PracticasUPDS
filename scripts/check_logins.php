<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Auth;

$cuentas = [
    'admin@upds.edu.bo',
    'coordinacion@upds.edu.bo',
    'tutor@upds.edu.bo',
    'estudiante@upds.edu.bo',
];

echo "=== VERIFICACIÓN DE CREDENCIALES (12345678) ===\n";
foreach ($cuentas as $email) {
    $ok = Auth::attempt(['email' => $email, 'password' => '12345678']);
    echo sprintf("%-30s => %s\n", $email, $ok ? 'CORRECTO (OK)' : 'FALLÓ (ERROR)');
}

