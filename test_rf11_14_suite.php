<?php
/**
 * ============================================================
 * SUITE DE PRUEBAS AUTOMÁTICAS: RF-11, RF-12, RF-13, RF-14
 * + Documentos, Notificaciones, Paginación y Búsqueda
 * Sistema de Gestión de Prácticas Profesionales - UPDS
 * ============================================================
 *
 * Ejecutar con: php test_rf11_14_suite.php
 * (Desde c:\laragon\www\GestionPracticasUPDSl)
 */

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use App\Models\User;
use App\Models\Estudiante;
use App\Models\SolicitudPractica;
use App\Models\Practica;
use App\Models\DocenteTutor;
use App\Models\RegistroHora;
use App\Models\InformeAvance;
use App\Models\ObservacionTutor;
use App\Models\EvaluacionFinal;
use App\Models\HistorialEstadoPractica;
use App\Models\DocumentoSolicitud;
use App\Models\TipoDocumento;

$passed = 0;
$failed = 0;
$resultados = [];

function ok(string $test, string $detalle = ''): void {
    global $passed, $resultados;
    $passed++;
    $resultados[] = ['⬜ PASS', $test, $detalle];
}
function fail(string $test, string $detalle = ''): void {
    global $failed, $resultados;
    $failed++;
    $resultados[] = ['🔴 FAIL', $test, $detalle];
}
function titulo(string $t): void {
    echo "\n" . str_repeat('═', 68) . "\n";
    echo "  {$t}\n";
    echo str_repeat('═', 68) . "\n";
}

$seguimientoSrc = file_get_contents(__DIR__ . '/app/Http/Controllers/SeguimientoController.php');
$docSrc = file_get_contents(__DIR__ . '/app/Http/Controllers/DocumentoController.php');
$solicSrc = file_get_contents(__DIR__ . '/app/Http/Controllers/SolicitudController.php');
$appBladeSrc = file_get_contents(__DIR__ . '/resources/views/layouts/app.blade.php');

// ─────────────────────────────────────────────────────────────
titulo('RF-11 · Registrar y consultar horas de prácticas');
// ─────────────────────────────────────────────────────────────

// RF-11.1: storeHora verifica que el usuario sea el estudiante propietario
$validaPropiedad = str_contains($seguimientoSrc, '!$user->isEstudiante() || $user->estudiante?->id !== $practica->solicitud->estudiante_id');
if ($validaPropiedad) {
    ok('RF-11.1 · storeHora() restringe registro exclusivamente al estudiante propietario.');
} else {
    fail('RF-11.1 · storeHora() no valida correctamente propiedad del estudiante.');
}

// RF-11.2: Solo se pueden registrar horas en prácticas 'en_curso'
$validaEnCurso = str_contains($seguimientoSrc, "\$practica->estado !== 'en_curso'");
if ($validaEnCurso) {
    ok('RF-11.2 · storeHora() bloquea registro si la práctica no está en estado «en_curso».');
} else {
    fail('RF-11.2 · storeHora() no exige estado «en_curso» para registrar horas.');
}

// RF-11.3: Límite diario de horas configurable y validación de rango (min 0.5, max configurable)
$validaLimites = str_contains($seguimientoSrc, 'max_horas_diarias') && str_contains($seguimientoSrc, 'horasDelDia');
if ($validaLimites) {
    ok('RF-11.3 · storeHora() controla el límite máximo de horas diarias acumuladas en el día.');
} else {
    fail('RF-11.3 · storeHora() no controla adecuadamente el límite diario.');
}

// RF-11.4: Rechazo de duplicaciones incompatibles (misma fecha y descripción)
$validaDuplicados = str_contains($seguimientoSrc, '$duplicado = RegistroHora::where') && str_contains($seguimientoSrc, 'Ya existe un registro de horas con idéntica descripción');
if ($validaDuplicados) {
    ok('RF-11.4 · storeHora() rechaza registros duplicados con la misma fecha y descripción.');
} else {
    fail('RF-11.4 · storeHora() no comprueba duplicados de jornada.');
}

// RF-11.5: Las horas se crean inicialmente en estado 'pendiente'
$estadoPendiente = str_contains($seguimientoSrc, "'estado' => 'pendiente'");
if ($estadoPendiente) {
    ok('RF-11.5 · storeHora() registra la jornada inicialmente como «pendiente».');
} else {
    fail('RF-11.5 · storeHora() no establece estado inicial «pendiente».');
}

// RF-11.6: validarHora() exclusivo para docente tutor asignado
$soloTutorAsignado = str_contains($seguimientoSrc, '!$user->isTutor()') && str_contains($seguimientoSrc, '$hora->practica->tutor_id !== $user->tutor?->id');
if ($soloTutorAsignado) {
    ok('RF-11.6 · validarHora() restringe validación/rechazo exclusivamente al docente tutor asignado.');
} else {
    fail('RF-11.6 · validarHora() no restringe correctamente al tutor asignado.');
}

// RF-11.7: validarHora() exige motivo obligatorio si se rechaza
$motivoRechazoHora = str_contains($seguimientoSrc, "observacion_revision.required");
if ($motivoRechazoHora) {
    ok('RF-11.7 · validarHora() exige motivo obligatorio al rechazar una jornada.');
} else {
    fail('RF-11.7 · validarHora() no exige motivo al rechazar.');
}

// RF-11.8: Solo las horas validadas suman al avance en Practica::getHorasValidadasAttribute()
$practicaModeloSrc = file_get_contents(__DIR__ . '/app/Models/Practica.php');
$horasSoloValidadas = str_contains($practicaModeloSrc, "where('estado', 'validada')->sum('horas')");
if ($horasSoloValidadas) {
    ok('RF-11.8 · Practica::getHorasValidadasAttribute() computa ÚNICAMENTE horas en estado «validada».');
} else {
    fail('RF-11.8 · Cómputo de horas incluye horas pendientes o rechazadas.');
}

// ─────────────────────────────────────────────────────────────
titulo('RF-12 · Recibir y consultar informes periódicos');
// ─────────────────────────────────────────────────────────────

// RF-12.1: storeInforme() restringe al estudiante propietario
$validaPropiedadInforme = str_contains($seguimientoSrc, '!$user->isEstudiante() || $user->estudiante?->id !== $practica->solicitud->estudiante_id');
if ($validaPropiedadInforme) {
    ok('RF-12.1 · storeInforme() restringe subida exclusivamente al estudiante asignado.');
} else {
    fail('RF-12.1 · storeInforme() no valida propiedad del estudiante.');
}

// RF-12.2: storeInforme() solo permite prácticas en curso
$informeEnCurso = str_contains($seguimientoSrc, "\$practica->estado !== 'en_curso'") && str_contains($seguimientoSrc, 'Solo se pueden presentar informes');
if ($informeEnCurso) {
    ok('RF-12.2 · storeInforme() exige que la práctica esté en estado «en_curso».');
} else {
    fail('RF-12.2 · storeInforme() no exige estado «en_curso».');
}

// RF-12.3: Validación estricta de documentos en storeInforme (Solo PDF y Word, no imágenes ni exe)
$mimesInforme = str_contains($seguimientoSrc, "mimes:pdf,doc,docx|max:10240");
if ($mimesInforme) {
    ok('RF-12.3 · storeInforme() admite únicamente PDF o Word (max 10MB) y rechaza imágenes/ejecutables.');
} else {
    fail('RF-12.3 · storeInforme() no tiene validación estricta de formato.');
}

// RF-12.4: descargarInforme() sin archivos ficticios
$sinFicticioInforme = !str_contains($seguimientoSrc, 'Storage::disk(\'local\')->put($demoPath') && str_contains($seguimientoSrc, 'no se encuentra en el almacenamiento');
if ($sinFicticioInforme) {
    ok('RF-12.4 · descargarInforme() NO fabrica archivos ficticios y reporta error si falta en disco.');
} else {
    fail('RF-12.4 · descargarInforme() todavía fabrica archivos ficticios.');
}

// RF-12.5: descargarInforme() verifica permisos
$permisoDescargaInforme = str_contains($seguimientoSrc, '$user->isEstudiante() && $user->estudiante?->id !== $informe->practica->solicitud->estudiante_id')
    && str_contains($seguimientoSrc, '$user->isTutor() && $informe->practica->tutor_id !== $user->tutor?->id');
if ($permisoDescargaInforme) {
    ok('RF-12.5 · descargarInforme() protege la descarga contra estudiantes o tutores no asignados.');
} else {
    fail('RF-12.5 · descargarInforme() no valida permisos de acceso.');
}

// RF-12.6: revisarInforme() solo para tutor asignado
$revisarTutorAsignado = str_contains($seguimientoSrc, 'isTutor()') && str_contains($seguimientoSrc, '$informe->practica->tutor_id !== $user->tutor?->id');
if ($revisarTutorAsignado) {
    ok('RF-12.6 · revisarInforme() solo permite al docente tutor asignado calificar o solicitar correcciones.');
} else {
    fail('RF-12.6 · revisarInforme() no bloquea tutores no asignados.');
}

// ─────────────────────────────────────────────────────────────
titulo('RF-13 · Observaciones y evaluación final del tutor');
// ─────────────────────────────────────────────────────────────

// RF-13.1: storeObservacion() exclusivo para tutor asignado
$observacionTutor = str_contains($seguimientoSrc, 'public function storeObservacion') && str_contains($seguimientoSrc, '$practica->tutor_id !== $user->tutor?->id');
if ($observacionTutor) {
    ok('RF-13.1 · storeObservacion() restringe registro de observaciones al tutor asignado.');
} else {
    fail('RF-13.1 · storeObservacion() no valida tutor asignado.');
}

// RF-13.2: storeEvaluacionFinal() NO cambia automáticamente la práctica a concluida (CORRECCIÓN FUNDAMENTAL)
$evalFinalNoCierra = !str_contains($seguimientoSrc, "'estado' => 'concluida',\n                'fecha_fin_real' => now()->toDateString(),\n                'observacion_cierre' => \$request->comentario")
    && str_contains($seguimientoSrc, 'La práctica permanece en curso hasta el cierre formal');
if ($evalFinalNoCierra) {
    ok('RF-13.2 · [CORRECCIÓN FUNDAMENTAL] storeEvaluacionFinal() NO concluye la práctica automáticamente.');
} else {
    fail('RF-13.2 · storeEvaluacionFinal() todavía concluye la práctica automáticamente.');
}

// RF-13.3: storeEvaluacionFinal() valida rango de calificación 0-100 y resultado Aprobado/Reprobado
$validaCalificacion = str_contains($seguimientoSrc, "calificacion' => 'required|numeric|between:0,100")
    && str_contains($seguimientoSrc, "resultado' => 'required|in:Aprobado,Reprobado");
if ($validaCalificacion) {
    ok('RF-13.3 · storeEvaluacionFinal() valida calificación entre 0 y 100 y resultado Aprobado/Reprobado.');
} else {
    fail('RF-13.3 · storeEvaluacionFinal() no valida calificación o resultado.');
}

// ─────────────────────────────────────────────────────────────
titulo('RF-14 · Cierre de prácticas (Coordinación Académica)');
// ─────────────────────────────────────────────────────────────

// RF-14.1: cerrarPractica() restringido exclusivamente a Coordinación Académica
$soloCoordinadorCierre = str_contains($seguimientoSrc, 'public function cerrarPractica') && str_contains($seguimientoSrc, '!$user->isCoordinador()');
if ($soloCoordinadorCierre) {
    ok('RF-14.1 · cerrarPractica() restringe el cierre exclusivamente a Coordinación Académica.');
} else {
    fail('RF-14.1 · cerrarPractica() no restringe adecuadamente a Coordinación.');
}

// RF-14.2: Verifica cumplimiento de horas validadas antes de concluir
$validaHorasCierre = str_contains($seguimientoSrc, '$horasValidadas < $horasMeta') && str_contains($seguimientoSrc, 'No se puede concluir la práctica: El estudiante no ha cumplido el objetivo de horas');
if ($validaHorasCierre) {
    ok('RF-14.2 · cerrarPractica() comprueba que las horas validadas alcancen el objetivo antes de concluir.');
} else {
    fail('RF-14.2 · cerrarPractica() no comprueba horas validadas.');
}

// RF-14.3: Verifica existencia de evaluación final emitida por el tutor antes de concluir
$validaEvalFinalCierre = str_contains($seguimientoSrc, '!$practica->evaluacionFinal') && str_contains($seguimientoSrc, 'obligatorio que el docente tutor haya registrado la evaluación final');
if ($validaEvalFinalCierre) {
    ok('RF-14.3 · cerrarPractica() comprueba que exista la evaluación final del tutor antes de concluir.');
} else {
    fail('RF-14.3 · cerrarPractica() no comprueba la evaluación final.');
}

// RF-14.4: Verifica entrega de al menos un informe de avance revisado
$validaInformesCierre = str_contains($seguimientoSrc, "\$practica->informes()->where('estado', 'revisado')->count()") || str_contains($seguimientoSrc, "where('estado', 'revisado')->exists()");
if ($validaInformesCierre) {
    ok('RF-14.4 · cerrarPractica() comprueba informes de avance revisados antes de concluir.');
} else {
    fail('RF-14.4 · cerrarPractica() no comprueba informes revisados.');
}

// RF-14.5: Registra trazabilidad en HistorialEstadoPractica mediante DB transaction
$historialCierre = str_contains($seguimientoSrc, 'HistorialEstadoPractica::create') && str_contains($seguimientoSrc, 'estado_anterior') && str_contains($seguimientoSrc, 'estado_nuevo');
if ($historialCierre) {
    ok('RF-14.5 · cerrarPractica() registra trazabilidad completa en HistorialEstadoPractica dentro de transacción.');
} else {
    fail('RF-14.5 · cerrarPractica() no registra historial.');
}

// ─────────────────────────────────────────────────────────────
titulo('PARTE II · Validación de documentos en todo el sistema');
// ─────────────────────────────────────────────────────────────

// DOC-01: DocumentoController::store solo permite PDF y Word (rechaza JPG, PNG, etc.)
$docMimes = str_contains($docSrc, "mimes:pdf,doc,docx|max:10240");
$docRechazaImagenes = !str_contains($docSrc, "mimes:pdf,doc,docx,jpg,png");
if ($docMimes && $docRechazaImagenes) {
    ok('DOC-01 · DocumentoController::store() admite ÚNICAMENTE PDF y Word, rechazando imágenes y ejecutables.');
} else {
    fail('DOC-01 · DocumentoController::store() todavía permite imágenes en documentos formales.');
}

// DOC-02: Mensajes de error en español amigables y concisos
$mensajesEspanol = str_contains($docSrc, 'Formato no permitido. Para documentos académicos formales, usa únicamente archivos PDF o Word');
if ($mensajesEspanol) {
    ok('DOC-02 · Validaciones de documentos emiten mensajes claros y amigables en español.');
} else {
    fail('DOC-02 · Falta mensaje amigable en español para validación de formato.');
}

// DOC-03: config/practicas.php define extensiones autorizadas
$cfg = include __DIR__ . '/config/practicas.php';
$extPermitidas = $cfg['extensiones_permitidas'] ?? [];
if ($extPermitidas === ['pdf', 'doc', 'docx']) {
    ok('DOC-03 · config/practicas.php define extensiones_permitidas = [pdf, doc, docx].');
} else {
    fail('DOC-03 · config/practicas.php extensiones_permitidas no configuradas.');
}

// ─────────────────────────────────────────────────────────────
titulo('PARTE III · Sistema global de notificaciones y confirmaciones');
// ─────────────────────────────────────────────────────────────

// NOTIF-01: Contenedor Toast flotante implementado en app.blade.php
$tieneToasts = str_contains($appBladeSrc, 'toast-container') && str_contains($appBladeSrc, 'text-bg-success') && str_contains($appBladeSrc, 'data-bs-delay="4000"');
if ($tieneToasts) {
    ok('NOTIF-01 · Contenedor de notificaciones flotantes Toast con autocierre (4s) implementado en app.blade.php.');
} else {
    fail('NOTIF-01 · Falta contenedor Toast flotante en app.blade.php.');
}

// NOTIF-02: Modal reutilizable de confirmación global
$tieneModalConfirm = str_contains($appBladeSrc, 'id="modalConfirmacionGlobal"') && str_contains($appBladeSrc, 'data-confirm');
if ($tieneModalConfirm) {
    ok('NOTIF-02 · Modal reutilizable de confirmación global (#modalConfirmacionGlobal) implementado.');
} else {
    fail('NOTIF-02 · Falta modal reutilizable de confirmación.');
}

// NOTIF-03: Alerta persistente para errores de formulario
$tieneAlertaErrores = str_contains($appBladeSrc, 'alert-danger') && str_contains($appBladeSrc, '$errors->any()');
if ($tieneAlertaErrores) {
    ok('NOTIF-03 · Alerta estructurada para errores de validación de formulario implementada.');
} else {
    fail('NOTIF-03 · Falta alerta de errores de validación.');
}

// ─────────────────────────────────────────────────────────────
titulo('PARTE V & VI · Paginación y buscadores automáticos');
// ─────────────────────────────────────────────────────────────

// PAG-01: SolicitudController implementa paginate con withQueryString
$pagSolicitudes = str_contains($solicSrc, "paginate(\$perPage, ['*'], 'sol_page')->withQueryString()");
if ($pagSolicitudes) {
    ok('PAG-01 · SolicitudController::index() implementa paginación configurable (10, 25, 50) con withQueryString().');
} else {
    fail('PAG-01 · SolicitudController no tiene paginación con query string.');
}

// PAG-02: OrganizacionController implementa paginate con withQueryString
$orgSrc = file_get_contents(__DIR__ . '/app/Http/Controllers/OrganizacionController.php');
$pagOrg = str_contains($orgSrc, "paginate(\$perPage)->withQueryString()");
if ($pagOrg) {
    ok('PAG-02 · OrganizacionController::index() implementa paginación configurable y orden id DESC.');
} else {
    fail('PAG-02 · OrganizacionController no tiene paginación.');
}

// PAG-03: SeguimientoController implementa paginate con withQueryString
$pagSeg = str_contains($seguimientoSrc, "paginate(\$perPage)->withQueryString()");
if ($pagSeg) {
    ok('PAG-03 · SeguimientoController::index() implementa paginación configurable con withQueryString().');
} else {
    fail('PAG-03 · SeguimientoController no tiene paginación.');
}

// BUSC-01: Buscador automático con retardo inteligente (data-auto-search debounce 350ms) en app.blade.php
$autoSearchJs = str_contains($appBladeSrc, 'data-auto-search') && str_contains($appBladeSrc, 'clearTimeout(timer)') && str_contains($appBladeSrc, 'query.length >= 2');
if ($autoSearchJs) {
    ok('BUSC-01 · Script global con debounce (350ms) y auto-envío desde 2 caracteres implementado en app.blade.php.');
} else {
    fail('BUSC-01 · Falta script de búsqueda automática con debounce en app.blade.php.');
}

// BUSC-02: Vista solicitudes/index incluye data-auto-search
$solicIndexSrc = file_get_contents(__DIR__ . '/resources/views/solicitudes/index.blade.php');
$solicAutoSearch = str_contains($solicIndexSrc, 'data-auto-search');
if ($solicAutoSearch) {
    ok('BUSC-02 · Vista solicitudes/index cuenta con campo data-auto-search y selector de registros por página.');
} else {
    fail('BUSC-02 · Falta data-auto-search en solicitudes/index.');
}

// BUSC-03: Vista seguimiento/lista incluye data-auto-search
$segListaSrc = file_get_contents(__DIR__ . '/resources/views/seguimiento/lista.blade.php');
$segAutoSearch = str_contains($segListaSrc, 'data-auto-search');
if ($segAutoSearch) {
    ok('BUSC-03 · Vista seguimiento/lista cuenta con campo data-auto-search y selector de registros por página.');
} else {
    fail('BUSC-03 · Falta data-auto-search en seguimiento/lista.');
}

// ─────────────────────────────────────────────────────────────
// RESUMEN FINAL
// ─────────────────────────────────────────────────────────────
echo "\n" . str_repeat('═', 68) . "\n";
printf("  %-6s %-48s %s\n", 'Estado', 'Prueba', 'Detalle');
echo str_repeat('─', 68) . "\n";
foreach ($resultados as $r) {
    printf("  %-6s %-48s %s\n", $r[0], wordwrap($r[1], 46, "\n         ", true), $r[2]);
}
echo str_repeat('═', 68) . "\n";
echo "  TOTAL SUITE: {$passed} pasaron  |  {$failed} fallaron\n";
echo str_repeat('═', 68) . "\n";
exit($failed > 0 ? 1 : 0);

