<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\Estudiante;
use Illuminate\Support\Facades\Auth;

echo "=== TEST CREACIÓN ESTUDIANTE CON CUENTA INSTITUCIONAL ===\n";

// Verificar si existe un usuario de prueba anterior
$userTest = User::where('email', 'carlos.mendoza@upds.edu.bo')->first();
if ($userTest) {
    Estudiante::where('usuario_id', $userTest->id)->delete();
    $userTest->delete();
}

$response = app()->handle(
    Illuminate\Http\Request::create('/estudiantes', 'POST', [
        '_token' => csrf_token(),
        'nombres' => 'Carlos',
        'apellidos' => 'Mendoza Vaca',
        'ru' => '3200999',
        'ci' => '7845123 CB',
        'carrera_id' => 1,
        'semestre' => 8,
        'telefono' => '78945612',
        'tipo_cuenta' => 'nueva',
        'email_institucional' => 'carlos.mendoza@upds.edu.bo',
        'password_inicial' => '12345678',
    ])
);

$creado = User::where('email', 'carlos.mendoza@upds.edu.bo')->first();
$estCreado = Estudiante::where('ru', '3200999')->first();

if ($creado && $estCreado && $estCreado->usuario_id === $creado->id) {
    echo "  [PASS] Usuario y Estudiante creados exitosamente por Coordinación con rol {$creado->rol_id}\n";
    echo "  [PASS] Login de la nueva cuenta: " . (Auth::attempt(['email' => 'carlos.mendoza@upds.edu.bo', 'password' => '12345678']) ? 'OK' : 'FAIL') . "\n";
    // Limpiar prueba
    $estCreado->delete();
    $creado->delete();
} else {
    echo "  [FAIL] No se creó el usuario correctamente.\n";
}

