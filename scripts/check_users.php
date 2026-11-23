<?php
require_once __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;
use App\Models\Practica;
use Illuminate\Support\Facades\Hash;

echo "VERIFICACIÓN DE USUARIOS:\n";
$emails = [
    'admin@upds.edu.bo',
    'coordinacion@upds.edu.bo',
    'tutor@upds.edu.bo',
    'estudiante@upds.edu.bo'
];

foreach ($emails as $email) {
    $u = User::where('email', $email)->first();
    if (!$u) {
        echo "  [ERROR] $email NO EXISTE\n";
    } else {
        $check = Hash::check('12345678', $u->password);
        echo "  " . ($check ? "[OK]" : "[FAIL]") . " $email (ID: {$u->id}, Rol: {$u->rol_id}) - Password '12345678': " . ($check ? 'CORRECTO' : 'INCORRECTO') . "\n";
    }
}

echo "\nPRÁCTICAS ACTIVAS:\n";
foreach (Practica::with('solicitud.estudiante.user')->get() as $p) {
    echo "  Practica #{$p->id}: Estudiante: " . ($p->solicitud->estudiante->user->name ?? 'N/A') . " | Estado: {$p->estado} | Req: {$p->horas_objetivo}h | Horas validadas: " . $p->horas_validadas . "h ({$p->porcentaje_horas}%)\n";
}
