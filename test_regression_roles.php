<?php
/**
 * TEST DE NO REGRESIÓN DE ROLES: Estudiante, Tutor, Coordinador, Administrador
 */

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use Illuminate\Http\Request;

view()->share('errors', new \Illuminate\Support\ViewErrorBag());

$roles = [
    'Estudiante' => 'estudiante@upds.edu.bo',
    'Docente Tutor' => 'tutor@upds.edu.bo',
    'Coordinador' => 'coordinacion@upds.edu.bo',
    'Administrador' => 'admin@upds.edu.bo',
];

echo "========================================================\n";
echo " VERIFICACIÓN DE NO REGRESIÓN POR ROLES\n";
echo "========================================================\n";

$dashController = app(App\Http\Controllers\DashboardController::class);

foreach ($roles as $rolName => $email) {
    $user = User::where('email', $email)->first();
    if (!$user) {
        echo "[FAIL] Usuario {$rolName} ({$email}) no encontrado\n";
        continue;
    }
    auth()->login($user);
    try {
        $view = $dashController->index();
        $html = $view->render();
        echo "[PASS] Dashboard para {$rolName} ({$user->name}) OK (" . strlen($html) . " bytes)\n";
    } catch (\Throwable $e) {
        echo "[FAIL] Dashboard para {$rolName} ERROR: " . $e->getMessage() . "\n";
    }
}

echo "========================================================\n";

