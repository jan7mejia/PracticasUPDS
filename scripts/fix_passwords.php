<?php
/**
 * Script para resetear contraseñas de las 4 cuentas demo a '12345678'
 * SOLO modifica password - no toca otros campos
 */

$host = '127.0.0.1';
$port = 3306;
$dbname = 'gestion_practicas_upds_final';
$user = 'root';
$pass = '123jan';

try {
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "✅ Conectado a la base de datos\n\n";
} catch (Exception $e) {
    die("❌ Error de conexión: " . $e->getMessage() . "\n");
}

// Verificar contraseñas actuales
$cuentas = [
    'admin@upds.edu.bo',
    'coordinacion@upds.edu.bo',
    'tutor@upds.edu.bo',
    'estudiante@upds.edu.bo',
];

echo "=== ESTADO ACTUAL DE CONTRASEÑAS ===\n";
$stmt = $pdo->prepare("SELECT id, email, password FROM users WHERE email = ?");
foreach ($cuentas as $email) {
    $stmt->execute([$email]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($row) {
        $ok = password_verify('12345678', $row['password']);
        echo ($ok ? "✅" : "❌") . " ID={$row['id']} {$email}: " . ($ok ? "contraseña OK" : "contraseña INCORRECTA") . "\n";
    } else {
        echo "⚠️  NO ENCONTRADO: $email\n";
    }
}

echo "\n=== RESETEANDO CONTRASEÑAS A '12345678' ===\n";
$nuevoHash = password_hash('12345678', PASSWORD_BCRYPT);
echo "Nuevo hash generado: $nuevoHash\n\n";

$update = $pdo->prepare("UPDATE users SET password = ? WHERE email = ?");
foreach ($cuentas as $email) {
    $update->execute([$nuevoHash, $email]);
    $filas = $update->rowCount();
    echo ($filas > 0 ? "✅" : "⚠️") . " $email: " . ($filas > 0 ? "actualizado ($filas fila)" : "sin cambios (email no existe?)") . "\n";
}

echo "\n=== VERIFICACIÓN FINAL ===\n";
foreach ($cuentas as $email) {
    $stmt->execute([$email]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($row) {
        $ok = password_verify('12345678', $row['password']);
        echo ($ok ? "✅" : "❌") . " {$email}: " . ($ok ? "LOGIN OK con 12345678" : "TODAVÍA FALLA") . "\n";
    } else {
        echo "⚠️  NO ENCONTRADO: $email\n";
    }
}

echo "\n¡Listo!\n";

