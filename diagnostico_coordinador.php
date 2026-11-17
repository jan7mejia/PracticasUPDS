<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$user = App\Models\User::where('email', 'coordinacion@upds.edu.bo')->first();
Auth::login($user);

try {
    $dash = app(App\Http\Controllers\DashboardController::class)->index();
    $html = $dash->render();
    echo "Dashboard Coordinator rendered OK! Length: " . strlen($html) . "\n";
} catch (\Throwable $e) {
    echo "Dashboard Error: " . $e->getMessage() . "\n";
}
