<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EstudianteController;
use App\Http\Controllers\OrganizacionController;
use App\Http\Controllers\SolicitudController;
use App\Http\Controllers\DocumentoController;
use App\Http\Controllers\SeguimientoController;
use App\Http\Controllers\ReporteController;
use App\Http\Controllers\PerfilController;
use App\Http\Controllers\AdminController;

/*
|--------------------------------------------------------------------------
| Web Routes - UPDSonline Sistema de Prácticas Profesionales
|--------------------------------------------------------------------------
*/

// Redirección inicial
Route::get('/', function () {
    return auth()->check() ? redirect()->route('dashboard') : redirect()->route('login');
});

// Autenticación
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
// Rutas protegidas
Route::middleware(['auth'])->group(function () {
    // 1. Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // 2. Gestión de Estudiantes
    Route::post('/estudiantes/{estudiante}/requisitos/{requisito}', [EstudianteController::class, 'verificarRequisito'])->name('estudiantes.requisitos.verificar');
    Route::post('/estudiantes/{estudiante}/requisitos-validar-todos', [EstudianteController::class, 'validarTodosRequisitos'])->name('estudiantes.requisitos.validar-todos');
    Route::resource('estudiantes', EstudianteController::class);

    // 3. Organizaciones Receptoras
    Route::resource('organizaciones', OrganizacionController::class)->except(['destroy']);
    Route::post('/organizaciones/{organizacion}/responsables', [OrganizacionController::class, 'storeResponsable'])->name('organizaciones.responsables.store');
    Route::put('/organizaciones/responsables/{responsable}', [OrganizacionController::class, 'updateResponsable'])->name('organizaciones.responsables.update');

    // 4. Solicitudes y Documentos
    Route::get('/solicitudes', [SolicitudController::class, 'index'])->name('solicitudes.index');
    Route::get('/solicitudes/create', [SolicitudController::class, 'create'])->name('solicitudes.create');
    Route::post('/solicitudes', [SolicitudController::class, 'store'])->name('solicitudes.store');
    Route::get('/solicitudes/{solicitude}', [SolicitudController::class, 'show'])->name('solicitudes.show');
    Route::post('/solicitudes/{solicitude}/cambiar-estado', [SolicitudController::class, 'cambiarEstado'])->name('solicitudes.cambiar-estado');

    // Gestión de Archivos / Documentos
    Route::post('/documentos', [DocumentoController::class, 'store'])->name('documentos.store');
    Route::get('/documentos/{documento}/descargar', [DocumentoController::class, 'download'])->name('documentos.download');
    Route::post('/documentos/{documento}/validar', [DocumentoController::class, 'validar'])->name('documentos.validar');

    // 5. Seguimiento y Evaluación
    Route::get('/seguimiento', [SeguimientoController::class, 'index'])->name('seguimiento.index');
    Route::get('/seguimiento/{practica}', [SeguimientoController::class, 'show'])->name('seguimiento.show');
    Route::post('/seguimiento/{practica}/asignar-tutor', [SeguimientoController::class, 'asignarTutor'])->name('seguimiento.asignar-tutor');
    Route::post('/seguimiento/{practica}/configurar', [SeguimientoController::class, 'configurarPractica'])->name('seguimiento.configurar');
    Route::post('/seguimiento/{practica}/aprobar-cronograma', [SeguimientoController::class, 'aprobarCronograma'])->name('seguimiento.aprobar-cronograma');
    Route::post('/seguimiento/{practica}/actividades', [SeguimientoController::class, 'storeActividad'])->name('seguimiento.actividades.store');
    Route::put('/seguimiento/actividades/{actividad}', [SeguimientoController::class, 'updateActividad'])->name('seguimiento.actividades.update');
    Route::post('/seguimiento/actividades/{actividad}/rubrica', [SeguimientoController::class, 'storeRubrica'])->name('seguimiento.actividades.rubrica.store');
    Route::post('/seguimiento/rubricas/{rubrica}/evaluar', [SeguimientoController::class, 'evaluarRubrica'])->name('seguimiento.rubricas.evaluar');
    Route::post('/seguimiento/{practica}/horas', [SeguimientoController::class, 'storeHora'])->name('seguimiento.horas.store');
    Route::post('/seguimiento/horas/{hora}/validar', [SeguimientoController::class, 'validarHora'])->name('seguimiento.horas.validar');
    Route::post('/seguimiento/{practica}/justificaciones', [SeguimientoController::class, 'storeJustificacion'])->name('seguimiento.justificaciones.store');
    Route::post('/seguimiento/justificaciones/{justificacion}/resolver', [SeguimientoController::class, 'resolverJustificacion'])->name('seguimiento.justificaciones.resolver');
    Route::post('/seguimiento/{practica}/incidencias', [SeguimientoController::class, 'storeIncidencia'])->name('seguimiento.incidencias.store');
    Route::post('/seguimiento/incidencias/{incidencia}/resolver', [SeguimientoController::class, 'resolverIncidencia'])->name('seguimiento.incidencias.resolver');
    Route::post('/seguimiento/{practica}/informes', [SeguimientoController::class, 'storeInforme'])->name('seguimiento.informes.store');
    Route::get('/seguimiento/informes/{informe}/descargar', [SeguimientoController::class, 'descargarInforme'])->name('seguimiento.informes.descargar');
    Route::post('/seguimiento/informes/{informe}/revisar', [SeguimientoController::class, 'revisarInforme'])->name('seguimiento.informes.revisar');
    Route::post('/seguimiento/{practica}/observaciones', [SeguimientoController::class, 'storeObservacion'])->name('seguimiento.observaciones.store');
    Route::post('/seguimiento/{practica}/evaluacion-parcial', [SeguimientoController::class, 'storeEvaluacionParcial'])->name('seguimiento.evaluacion-parcial.store');
    Route::post('/seguimiento/{practica}/evaluacion-final', [SeguimientoController::class, 'storeEvaluacionFinal'])->name('seguimiento.evaluacion.store');
    Route::post('/seguimiento/{practica}/evaluacion-organizacion', [SeguimientoController::class, 'storeEvaluacionOrganizacion'])->name('seguimiento.evaluacion-organizacion.store');
    Route::post('/seguimiento/{practica}/cierre', [SeguimientoController::class, 'cerrarPractica'])->name('seguimiento.cierre');

    // 6. Reportes y Consultas
    Route::get('/reportes', [ReporteController::class, 'index'])->name('reportes.index');
    Route::get('/reportes/exportar', [ReporteController::class, 'exportar'])->name('reportes.exportar');

    // 7. Perfil de Usuario
    Route::get('/perfil', [PerfilController::class, 'edit'])->name('perfil.edit');
    Route::put('/perfil', [PerfilController::class, 'update'])->name('perfil.update');
    Route::put('/perfil/password', [PerfilController::class, 'updatePassword'])->name('perfil.password');

    // 8. Panel Admin (solo administradores)
    Route::middleware(['role:administrador'])->prefix('admin')->name('admin.')->group(function () {
        // Gestión de Usuarios
        Route::get('/usuarios', [AdminController::class, 'usuariosIndex'])->name('usuarios.index');
        Route::get('/usuarios/create', [AdminController::class, 'usuariosCreate'])->name('usuarios.create');
        Route::post('/usuarios', [AdminController::class, 'usuariosStore'])->name('usuarios.store');
        Route::get('/usuarios/{usuario}', [AdminController::class, 'usuariosShow'])->name('usuarios.show');
        Route::get('/usuarios/{usuario}/edit', [AdminController::class, 'usuariosEdit'])->name('usuarios.edit');
        Route::put('/usuarios/{usuario}', [AdminController::class, 'usuariosUpdate'])->name('usuarios.update');
        Route::post('/usuarios/{usuario}/toggle', [AdminController::class, 'usuariosToggle'])->name('usuarios.toggle');
        Route::post('/usuarios/{usuario}/reset-password', [AdminController::class, 'usuariosResetPassword'])->name('usuarios.reset-password');

        // Roles y Permisos
        Route::get('/roles', [AdminController::class, 'rolesIndex'])->name('roles.index');

        // Gestión de Períodos Académicos
        Route::get('/periodos', [AdminController::class, 'periodosIndex'])->name('periodos.index');
        Route::get('/periodos/create', [AdminController::class, 'periodosCreate'])->name('periodos.create');
        Route::post('/periodos', [AdminController::class, 'periodosStore'])->name('periodos.store');
        Route::get('/periodos/{periodo}/edit', [AdminController::class, 'periodosEdit'])->name('periodos.edit');
        Route::put('/periodos/{periodo}', [AdminController::class, 'periodosUpdate'])->name('periodos.update');
        Route::delete('/periodos/{periodo}', [AdminController::class, 'periodosDestroy'])->name('periodos.destroy');

        // Catálogos (Carreras)
        Route::get('/carreras', [AdminController::class, 'carrerasIndex'])->name('carreras.index');
        Route::post('/carreras', [AdminController::class, 'carrerasStore'])->name('carreras.store');
        Route::put('/carreras/{carrera}', [AdminController::class, 'carrerasUpdate'])->name('carreras.update');

        // Registro de Auditoría Administrativa
        Route::get('/auditoria', [AdminController::class, 'auditoriaIndex'])->name('auditoria.index');
    });
});

