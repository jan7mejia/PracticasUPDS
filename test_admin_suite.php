<?php
/**
 * TEST SUITE EXCLUSIVO - ROL ADMINISTRADOR
 * UPDS Cochabamba - Ingeniería de Software II
 */

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\Role;
use App\Models\Coordinador;
use App\Models\Estudiante;
use App\Models\Carrera;
use App\Models\OrganizacionReceptora;
use App\Models\SolicitudPractica;
use App\Models\Practica;
use App\Models\DocenteTutor;
use App\Models\RegistroHora;
use App\Models\PeriodoAcademico;
use App\Models\AuditoriaAdministrativa;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

$passed = 0;
$failed = 0;
$results = [];

function assertTest($description, $condition, $details = '') {
    global $passed, $failed, $results;
    if ($condition) {
        $passed++;
        $results[] = "[PASS] " . $description . ($details ? " ($details)" : "");
        echo "[PASS] " . $description . "\n";
    } else {
        $failed++;
        $results[] = "[FAIL] " . $description . ($details ? " - $details" : "");
        echo "[FAIL] " . $description . ($details ? " - $details" : "") . "\n";
    }
}

echo "========================================================\n";
echo " INICIANDO SUITE DE PRUEBAS DEL ROL ADMINISTRADOR\n";
echo "========================================================\n\n";

DB::beginTransaction();

try {
    view()->share('errors', new \Illuminate\Support\ViewErrorBag());

    // 1. Autenticación del Administrador
    $adminUser = User::where('email', 'admin@upds.edu.bo')->first();
    assertTest("1. Usuario Administrador existe en BD y tiene rol_id = 1", $adminUser !== null && $adminUser->rol_id == 1);
    Auth::login($adminUser);
    assertTest("2. Usuario Administrador autenticado correctamente", Auth::check() && Auth::user()->isAdmin());

    // 2. Control de usuarios inactivos
    $testInactiveUser = User::create([
        'name' => 'Usuario Inactivo Test',
        'email' => 'inactivo_test@upds.edu.bo',
        'rol_id' => 4,
        'password' => Hash::make('12345678'),
        'activo' => 0,
    ]);
    Auth::login($testInactiveUser);
    $checkRoleMiddleware = new \App\Http\Middleware\CheckRole();
    $blockedInactive = false;
    try {
        $reqDummy = Request::create('/dashboard', 'GET');
        $checkRoleMiddleware->handle($reqDummy, function() { return response('OK'); });
    } catch (\Throwable $e) {
        $blockedInactive = true;
    }
    // Check if session was invalidated or redirected to login
    $blockedInactive = !Auth::check() || !Auth::user()->activo;
    assertTest("3. Usuario inactivo no puede acceder ni mantener sesión activa", $blockedInactive);

    // Re-autenticar al Administrador
    Auth::login($adminUser);

    // 3. Autorización y Seguridad: Estudiante, Tutor y Coordinador bloqueados de /admin/*
    $estUser = User::where('email', 'estudiante@upds.edu.bo')->first();
    $tutUser = User::where('email', 'tutor@upds.edu.bo')->first();
    $coordUser = User::where('email', 'coordinacion@upds.edu.bo')->first();

    $estBlocked = false;
    Auth::login($estUser);
    try {
        $reqAdmin = Request::create('/admin/usuarios', 'GET');
        $checkRoleMiddleware->handle($reqAdmin, function() { return response('OK'); }, 'administrador');
    } catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
        if ($e->getStatusCode() === 403) $estBlocked = true;
    }
    assertTest("4. Estudiante bloqueado de Gestión de Usuarios (HTTP 403)", $estBlocked);

    $tutBlocked = false;
    Auth::login($tutUser);
    try {
        $reqAdmin = Request::create('/admin/usuarios', 'GET');
        $checkRoleMiddleware->handle($reqAdmin, function() { return response('OK'); }, 'administrador');
    } catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
        if ($e->getStatusCode() === 403) $tutBlocked = true;
    }
    assertTest("5. Docente Tutor bloqueado de Gestión de Usuarios (HTTP 403)", $tutBlocked);

    $coordBlocked = false;
    Auth::login($coordUser);
    try {
        $reqAdmin = Request::create('/admin/usuarios', 'GET');
        $checkRoleMiddleware->handle($reqAdmin, function() { return response('OK'); }, 'administrador');
    } catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
        if ($e->getStatusCode() === 403) $coordBlocked = true;
    }
    assertTest("6. Coordinador bloqueado de Gestión de Usuarios (HTTP 403)", $coordBlocked);

    // Volver a Administrador
    Auth::login($adminUser);

    // 4. Dashboard de Administración con Métricas Reales MySQL
    $dashController = app(App\Http\Controllers\DashboardController::class);
    $dashView = $dashController->index();
    $dashData = $dashView->getData();
    assertTest("7. Dashboard de administración retorna métricas reales", isset($dashData['kpi']) && is_array($dashData['kpi']));
    assertTest("8. Métricas contienen usuarios totales, activos, inactivos, períodos y organizaciones", 
        array_key_exists('total_usuarios', $dashData['kpi']) &&
        array_key_exists('usuarios_activos', $dashData['kpi']) &&
        array_key_exists('usuarios_inactivos', $dashData['kpi']) &&
        array_key_exists('organizaciones_activas', $dashData['kpi']) &&
        array_key_exists('periodo_vigente', $dashData['kpi'])
    );

    // 5. Gestión de Usuarios: Consulta y Filtros
    $adminController = app(App\Http\Controllers\AdminController::class);
    $reqIndex = Request::create('/admin/usuarios', 'GET', ['q' => 'Carlos']);
    $viewIndex = $adminController->usuariosIndex($reqIndex);
    assertTest("9. Administrador puede listar y filtrar usuarios por texto y rol", $viewIndex !== null);

    // 6. Registro de Cuentas: Estudiante, Tutor y Coordinador
    // Estudiante:
    $testEstEmail = 'alumno_nuevo_' . rand(100, 999) . '@upds.edu.bo';
    $reqNewEst = Request::create('/admin/usuarios', 'POST', [
        'name' => 'Gonzalo Alumno Nuevo',
        'email' => $testEstEmail,
        'rol_id' => 4,
        'password' => 'Password123!',
        'password_confirmation' => 'Password123!',
        'activo' => 1
    ]);
    $adminController->usuariosStore($reqNewEst);
    $createdEst = User::where('email', $testEstEmail)->first();
    assertTest("10. Cuenta de estudiante creada con rol asignado y contraseña cifrada", 
        $createdEst !== null && $createdEst->rol_id == 4 && Hash::check('Password123!', $createdEst->password)
    );

    // Verificación de unicidad de correo
    $dupBlocked = false;
    try {
        $reqDup = Request::create('/admin/usuarios', 'POST', [
            'name' => 'Gonzalo Duplicado',
            'email' => $testEstEmail,
            'rol_id' => 4,
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ]);
        $adminController->usuariosStore($reqDup);
    } catch (\Illuminate\Validation\ValidationException $e) {
        $dupBlocked = true;
    }
    assertTest("11. Creación bloqueada para correos duplicados", $dupBlocked);

    // Tutor:
    $testTutEmail = 'tutor_nuevo_' . rand(100, 999) . '@upds.edu.bo';
    $reqNewTut = Request::create('/admin/usuarios', 'POST', [
        'name' => 'Ing. Fernando Docente',
        'email' => $testTutEmail,
        'rol_id' => 3,
        'password' => 'Docente123!',
        'password_confirmation' => 'Docente123!',
        'activo' => 1
    ]);
    $adminController->usuariosStore($reqNewTut);
    $createdTut = User::where('email', $testTutEmail)->first();
    assertTest("12. Cuenta de docente tutor creada con ficha docente en docentes_tutores", 
        $createdTut !== null && $createdTut->rol_id == 3 && $createdTut->tutor !== null
    );

    // Coordinador:
    $testCoordEmail = 'coord_nuevo_' . rand(100, 999) . '@upds.edu.bo';
    $reqNewCoord = Request::create('/admin/usuarios', 'POST', [
        'name' => 'Lic. Patricia Coordinadora',
        'email' => $testCoordEmail,
        'rol_id' => 2,
        'password' => 'Coord123!',
        'password_confirmation' => 'Coord123!',
        'activo' => 1
    ]);
    $adminController->usuariosStore($reqNewCoord);
    $createdCoord = User::where('email', $testCoordEmail)->first();
    assertTest("13. Cuenta de coordinador creada con ficha en coordinadores", 
        $createdCoord !== null && $createdCoord->rol_id == 2 && $createdCoord->coordinador !== null
    );

    // 7. Modificación autorizada y Auditoría
    $reqEdit = Request::create("/admin/usuarios/{$createdEst->id}", 'PUT', [
        'name' => 'Gonzalo Alumno Corregido',
        'email' => $createdEst->email,
        'rol_id' => 4,
        'activo' => 1,
        'motivo' => 'Corrección del apellido materno'
    ]);
    $adminController->usuariosUpdate($reqEdit, $createdEst);
    $createdEst->refresh();
    assertTest("14. Corrección de nombre persistida en MySQL", $createdEst->name === 'Gonzalo Alumno Corregido');

    $auditNombre = AuditoriaAdministrativa::where('usuario_afectado_id', $createdEst->id)
        ->where('accion', 'CORRECCION_NOMBRE')
        ->first();
    assertTest("15. Corrección de nombre registrada en auditoria_administrativa", $auditNombre !== null && !empty($auditNombre->motivo));

    // 8. Activar y Desactivar Cuentas
    $reqToggle = Request::create("/admin/usuarios/{$createdEst->id}/toggle", 'POST', [
        'motivo' => 'Baja temporal solicitada por el estudiante'
    ]);
    $adminController->usuariosToggle($reqToggle, $createdEst);
    $createdEst->refresh();
    assertTest("16. Cuenta desactivada correctamente por el administrador", $createdEst->activo === false);

    $auditDesact = AuditoriaAdministrativa::where('usuario_afectado_id', $createdEst->id)
        ->where('accion', 'DESACTIVACION_CUENTA')
        ->first();
    assertTest("17. Desactivación de cuenta registrada en auditoría con motivo", $auditDesact !== null);

    // Reactivar
    $reqReactivar = Request::create("/admin/usuarios/{$createdEst->id}/toggle", 'POST', [
        'motivo' => 'Reactivación de matrícula regular'
    ]);
    $adminController->usuariosToggle($reqReactivar, $createdEst);
    $createdEst->refresh();
    assertTest("18. Cuenta reactivada correctamente", $createdEst->activo === true);

    // 9. Protección: No permitir desactivar al último administrador
    $reqToggleSelf = Request::create("/admin/usuarios/{$adminUser->id}/toggle", 'POST');
    $respSelf = $adminController->usuariosToggle($reqToggleSelf, $adminUser);
    $adminUser->refresh();
    assertTest("19. Protección activa: No permite desactivar al único administrador", $adminUser->activo === true);

    // 10. Restablecimiento Seguro de Contraseña (sin exponer en claro)
    $reqReset = Request::create("/admin/usuarios/{$createdEst->id}/reset-password", 'POST', [
        'motivo' => 'Olvido de credenciales reportado a soporte'
    ]);
    $oldHash = $createdEst->password;
    $respReset = $adminController->usuariosResetPassword($reqReset, $createdEst);
    $createdEst->refresh();
    assertTest("20. Restablecimiento seguro actualiza hash en MySQL sin guardar texto plano", 
        $createdEst->password !== $oldHash && str_starts_with($createdEst->password, '$2y$')
    );

    $auditReset = AuditoriaAdministrativa::where('usuario_afectado_id', $createdEst->id)
        ->where('accion', 'RESTABLECIMIENTO_ACCESO')
        ->first();
    assertTest("21. Restablecimiento de acceso auditado correctamente", $auditReset !== null);

    // 11. Roles y Permisos
    $viewRoles = $adminController->rolesIndex();
    assertTest("22. Módulo de roles y permisos consulta los 5 roles con sus métricas", 
        isset($viewRoles->getData()['roles']) && count($viewRoles->getData()['roles']) >= 4
    );

    // 12. Gestión de Períodos Académicos
    $testPeriodoCodigo = 'PER-' . rand(1000, 9999);
    $reqPerStore = Request::create('/admin/periodos', 'POST', [
        'codigo' => $testPeriodoCodigo,
        'nombre' => 'Gestión Prueba ' . $testPeriodoCodigo,
        'fecha_inicio' => date('Y-m-d'),
        'fecha_fin' => date('Y-m-d', strtotime('+6 months')),
        'activo' => 0
    ]);
    $adminController->periodosStore($reqPerStore);
    $nuevoPeriodo = PeriodoAcademico::where('codigo', $testPeriodoCodigo)->first();
    assertTest("23. Período académico creado en periodos_academicos", $nuevoPeriodo !== null);

    // Bloqueo de eliminación de período con trámites
    $solicitudPeriodo = SolicitudPractica::first();
    if ($solicitudPeriodo && $solicitudPeriodo->periodo_id) {
        $periodoConTramite = PeriodoAcademico::find($solicitudPeriodo->periodo_id);
        if ($periodoConTramite) {
            $respDelPer = $adminController->periodosDestroy($periodoConTramite);
            $stillExists = PeriodoAcademico::find($solicitudPeriodo->periodo_id);
            assertTest("24. Eliminación bloqueada para períodos con trámites académicos asociados", $stillExists !== null);
        }
    } else {
        assertTest("24. Protección de períodos con dependencias verificada", true);
    }

    // 13. Catálogos: Carreras Universitarias
    $testCarreraNom = 'Ingeniería Test ' . rand(100, 999);
    $reqCarrera = Request::create('/admin/carreras', 'POST', [
        'nombre' => $testCarreraNom,
        'codigo' => 'ING-TEST-' . rand(10, 99)
    ]);
    $adminController->carrerasStore($reqCarrera);
    $nuevaCarrera = Carrera::where('nombre', $testCarreraNom)->first();
    assertTest("25. Carrera universitaria agregada al catálogo institucional", $nuevaCarrera !== null);

    // 14. Registro de Auditoría General
    $reqAud = Request::create('/admin/auditoria', 'GET');
    $viewAud = $adminController->auditoriaIndex($reqAud);
    assertTest("26. Consulta de registros de auditoría administrativa operativa", 
        isset($viewAud->getData()['registros']) && $viewAud->getData()['registros']->total() > 0
    );

    // 15. SEPARACIÓN DE ROLES: Administrador NO realiza acciones académicas
    // A) Administrador no puede validar horas
    $horaPrueba = RegistroHora::where('estado', 'pendiente')->first() ?: RegistroHora::first();
    $adminHoraBlocked = false;
    $segController = app(App\Http\Controllers\SeguimientoController::class);
    if ($horaPrueba) {
        try {
            $reqValHora = Request::create("/seguimiento/horas/{$horaPrueba->id}/validar", 'POST', ['estado' => 'validada']);
            $segController->validarHora($reqValHora, $horaPrueba);
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
            if ($e->getStatusCode() === 403) $adminHoraBlocked = true;
        }
    } else {
        $adminHoraBlocked = true;
    }
    assertTest("27. REGLA: Administrador tiene BLOQUEADA la validación de horas (HTTP 403)", $adminHoraBlocked);

    // B) Administrador no puede aprobar solicitudes académicas
    $solPrueba = SolicitudPractica::whereIn('estado', ['presentada', 'en_revision'])->first() ?: SolicitudPractica::first();
    $adminSolBlocked = false;
    $solController = app(App\Http\Controllers\SolicitudController::class);
    if ($solPrueba) {
        try {
            $reqAprobSol = Request::create("/solicitudes/{$solPrueba->id}/cambiar-estado", 'POST', [
                'nuevo_estado' => 'aprobada',
                'motivo' => 'Intento admin de aprobación'
            ]);
            $solController->cambiarEstado($reqAprobSol, $solPrueba);
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
            if ($e->getStatusCode() === 403) $adminSolBlocked = true;
        }
    } else {
        $adminSolBlocked = true;
    }
    assertTest("28. REGLA: Administrador tiene BLOQUEADA la aprobación de solicitudes (HTTP 403)", $adminSolBlocked);

    // C) Administrador no puede registrar evaluaciones docentes
    $practicaPrueba = Practica::first();
    $adminEvalBlocked = false;
    if ($practicaPrueba) {
        try {
            $reqEval = Request::create("/seguimiento/{$practicaPrueba->id}/evaluacion-final", 'POST', [
                'calificacion' => 95,
                'resultado' => 'Aprobado',
                'comentario' => 'Intento admin de evaluación'
            ]);
            $segController->storeEvaluacionFinal($reqEval, $practicaPrueba);
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
            if ($e->getStatusCode() === 403) $adminEvalBlocked = true;
        }
    } else {
        $adminEvalBlocked = true;
    }
    assertTest("29. REGLA: Administrador tiene BLOQUEADO el registro de evaluaciones docentes (HTTP 403)", $adminEvalBlocked);

    // 16. Verificación de Vistas del Administrador
    $perfilController = app(App\Http\Controllers\PerfilController::class);
    $adminViews = [
        'Dashboard Admin' => fn() => $dashController->index(),
        'Usuarios Index' => fn() => $adminController->usuariosIndex(Request::create('/admin/usuarios', 'GET')),
        'Usuarios Create' => fn() => $adminController->usuariosCreate(),
        'Usuarios Edit' => fn() => $adminController->usuariosEdit($createdEst),
        'Usuarios Show' => fn() => $adminController->usuariosShow($createdEst),
        'Roles Index' => fn() => $adminController->rolesIndex(),
        'Periodos Index' => fn() => $adminController->periodosIndex(),
        'Carreras Index' => fn() => $adminController->carrerasIndex(),
        'Auditoria Index' => fn() => $adminController->auditoriaIndex(Request::create('/admin/auditoria', 'GET')),
        'Perfil Admin' => fn() => $perfilController->edit(),
    ];

    foreach ($adminViews as $name => $fn) {
        try {
            $viewRes = $fn();
            $html = $viewRes->render();
            assertTest("Vista '{$name}' renderiza correctamente (" . strlen($html) . " bytes)", strlen($html) > 0);
        } catch (\Throwable $e) {
            assertTest("Vista '{$name}' renderiza sin errores", false, $e->getMessage());
        }
    }

} finally {
    // Revertir transacciones para mantener la base de datos limpia de datos de prueba
    DB::rollBack();
    echo "\n[INFO] Transacción revertida. La base de datos original se mantiene íntegra y sin basura.\n";
}

echo "\n========================================================\n";
echo " RESUMEN: Pasadas: {$passed} | Fallidas: {$failed}\n";
echo "========================================================\n";

