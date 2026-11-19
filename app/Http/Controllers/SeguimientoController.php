<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use App\Models\Practica;
use App\Models\DocenteTutor;
use App\Models\RegistroHora;
use App\Models\InformeAvance;
use App\Models\ObservacionTutor;
use App\Models\EvaluacionSeguimiento;
use App\Models\EvaluacionFinal;
use App\Models\EvaluacionOrganizacion;
use App\Models\CronogramaActividad;
use App\Models\ResponsableOrganizacion;
use App\Models\HistorialEstadoPractica;

class SeguimientoController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();

        if ($user->isEstudiante()) {
            $estudiante = $user->estudiante;
            $practica = Practica::whereHas('solicitud', function ($q) use ($estudiante) {
                $q->where('estudiante_id', $estudiante?->id);
            })->latest()->first();

            if ($practica) {
                return redirect()->route('seguimiento.show', $practica->id);
            }

            return view('seguimiento.vacio', ['mensaje' => 'Aún no tienes una práctica profesional en seguimiento. Inicia con una solicitud de práctica.']);
        }

        // Para Tutor, Coordinador y Administrador
        $perPage = in_array((int)$request->get('per_page'), [10, 25, 50]) ? (int)$request->get('per_page') : 10;
        $query = Practica::with(['solicitud.estudiante.carrera', 'solicitud.organizacion', 'tutor']);

        if ($user->isTutor()) {
            $tutor = $user->tutor;
            $query->where('tutor_id', $tutor?->id);
        }

        if ($request->filled('estado')) {
            $query->where('estado', $request->estado);
        }

        if ($request->filled('buscar')) {
            $search = trim($request->buscar);
            $cleanNum = ltrim(preg_replace('/[^0-9]/', '', $search), '0');
            $query->where(function($q) use ($search, $cleanNum, $user) {
                if (!empty($cleanNum)) {
                    $q->orWhere('id', (int)$cleanNum);
                }
                $q->orWhereHas('solicitud.estudiante', function($sq) use ($search) {
                    $sq->where('nombres', 'like', "%{$search}%")
                       ->orWhere('apellidos', 'like', "%{$search}%")
                       ->orWhere('ru', 'like', "%{$search}%");
                })->orWhereHas('solicitud.organizacion', function($oq) use ($search) {
                    $oq->where('nombre', 'like', "%{$search}%")
                       ->orWhere('rubro', 'like', "%{$search}%");
                })->orWhere('estado', 'like', "%{$search}%");

                if (!$user->isTutor()) {
                    $q->orWhereHas('tutor', function($tq) use ($search) {
                        $tq->where('nombres', 'like', "%{$search}%")
                           ->orWhere('apellidos', 'like', "%{$search}%")
                           ->orWhere('especialidad', 'like', "%{$search}%");
                    });
                }
            });
        }

        $practicas = $query->orderBy('created_at', 'desc')->orderBy('id', 'desc')->paginate($perPage)->withQueryString();
        return view('seguimiento.lista', compact('practicas', 'perPage'));
    }

    public function show(Practica $practica)
    {
        $user = Auth::user();

        // Seguridad: El estudiante solo puede ver el seguimiento de SU propia práctica
        if ($user->isEstudiante() && $user->estudiante?->id !== $practica->solicitud->estudiante_id) {
            abort(403, 'Acceso denegado. No tienes autorización para consultar el seguimiento de otros estudiantes.');
        }

        // Seguridad: El tutor solo puede consultar el seguimiento de sus prácticas asignadas
        if ($user->isTutor() && $practica->tutor_id !== $user->tutor?->id) {
            abort(403, 'Acceso denegado. No tienes autorización para consultar el seguimiento de una práctica que no supervisas.');
        }

        $practica->load([
            'solicitud.estudiante.carrera',
            'solicitud.organizacion.responsables',
            'tutor',
            'actividades',
            'registrosHoras' => fn($q) => $q->orderBy('fecha', 'desc'),
            'informes' => fn($q) => $q->orderBy('periodo_inicio', 'desc'),
            'observaciones.tutor',
            'evaluacionesSeguimiento.tutor',
            'evaluacionFinal',
            'evaluacionOrganizacion.responsable',
            'historial.usuario',
        ]);

        $tutores = DocenteTutor::where('activo', true)->get();

        return view('seguimiento.show', compact('practica', 'tutores'));
    }

    public function asignarTutor(Request $request, Practica $practica)
    {
        $user = Auth::user();

        // RF-10: La asignación de tutores es potestad EXCLUSIVA del Coordinador Académico.
        if (!$user->isCoordinador()) {
            abort(403, 'Acción no autorizada. La asignación de tutores es potestad de la Coordinación Académica.');
        }

        $request->validate([
            'tutor_id' => 'required|exists:docentes_tutores,id',
        ]);

        $tutor = DocenteTutor::where('id', $request->tutor_id)->where('activo', true)->first();
        if (!$tutor) {
            return back()->withErrors(['tutor_id' => 'El docente tutor seleccionado no se encuentra activo para asignaciones.']);
        }

        $tutorAnterior  = $practica->tutor ? $practica->tutor->nombre_completo : 'Sin tutor';
        // RF-10 fix historial: capturar estado ANTES de modificar la práctica
        $estadoAnterior = $practica->estado;
        $estadoNuevo    = ($practica->estado === 'planificada') ? 'en_curso' : $practica->estado;

        $practica->update([
            'tutor_id' => $tutor->id,
            'estado'   => $estadoNuevo,
        ]);

        // RF-10 fix historial: estado_anterior y estado_nuevo guardan los valores correctos.
        HistorialEstadoPractica::create([
            'practica_id'    => $practica->id,
            'estado_anterior'=> $estadoAnterior,
            'estado_nuevo'   => $estadoNuevo,
            'motivo'         => "Asignación de docente tutor: {$tutor->nombre_completo} (Anterior: {$tutorAnterior}).",
            'cambiado_por'   => $user->id,
            'fecha_cambio'   => now(),
        ]);

        return back()->with('success', "Docente tutor {$tutor->nombre_completo} asignado correctamente a la práctica.");
    }

    public function storeHora(Request $request, Practica $practica)
    {
        $user = Auth::user();

        // 1. RF-11: Validar que el usuario sea el estudiante propietario de la práctica
        if (!$user->isEstudiante() || $user->estudiante?->id !== $practica->solicitud->estudiante_id) {
            abort(403, 'Acceso denegado. Solo el estudiante asignado puede registrar horas en esta práctica.');
        }

        // 2. RF-11: Permitir registrar horas únicamente en prácticas en curso
        if ($practica->estado !== 'en_curso') {
            return back()->withErrors(['horas' => "Solo se pueden registrar horas cuando la práctica se encuentra en estado 'en curso' (Estado actual: {$practica->estado})."])->withInput();
        }

        // Compatibilidad: calcular horas a partir de horas y minutos si fueron enviados
        if ($request->has('duracion_horas') || $request->has('duracion_minutos')) {
            $h = (int) $request->input('duracion_horas', 0);
            $m = (int) $request->input('duracion_minutos', 0);
            $totalDecimal = round($h + ($m / 60.0), 2);
            $request->merge(['horas' => $totalDecimal]);
        }

        $maxDiarias = (float) config('practicas.max_horas_diarias', 8.0);

        $request->validate([
            'fecha' => 'required|date|before_or_equal:today',
            'horas' => 'required|numeric|min:0.5|max:' . $maxDiarias,
            'descripcion' => 'required|string|min:5|max:500',
            'actividad_id' => 'nullable|exists:cronograma_actividades,id',
        ], [
            'fecha.required' => 'La fecha de la jornada es obligatoria.',
            'fecha.before_or_equal' => 'No puedes registrar horas con fechas futuras.',
            'horas.required' => 'Ingresa la cantidad de horas realizadas.',
            'horas.min' => 'El tiempo mínimo por jornada debe ser de al menos 30 minutos (0.5 hrs).',
            'horas.max' => "El límite máximo por jornada diaria es de {$maxDiarias} horas.",
            'descripcion.required' => 'Describe las actividades realizadas durante la jornada.',
            'descripcion.min' => 'La descripción debe tener al menos 5 caracteres.',
        ]);

        // 3. Validar que la fecha esté dentro del periodo de la práctica (si tiene fecha de inicio)
        if ($practica->fecha_inicio && $request->fecha < $practica->fecha_inicio->format('Y-m-d')) {
            return back()->withErrors(['fecha' => "La fecha de la jornada no puede ser anterior al inicio de la práctica ({$practica->fecha_inicio->format('d/m/Y')})."])->withInput();
        }

        // 4. RF-11: Controlar suma diaria (máximo horas diarias según configuración institucional)
        $horasDelDia = RegistroHora::where('practica_id', $practica->id)
            ->whereDate('fecha', $request->fecha)
            ->whereIn('estado', ['pendiente', 'validada'])
            ->sum('horas');

        if (($horasDelDia + (float)$request->horas) > $maxDiarias) {
            $disponibles = max(0, $maxDiarias - $horasDelDia);
            return back()->withErrors(['horas' => "El total de horas para el día {$request->fecha} superaría el límite de {$maxDiarias} horas diarias. Horas acumuladas: {$horasDelDia}h (máximo disponible: {$disponibles}h)."])->withInput();
        }

        // 5. RF-11: Rechazar duplicaciones incompatibles (misma fecha y descripción idéntica)
        $duplicado = RegistroHora::where('practica_id', $practica->id)
            ->whereDate('fecha', $request->fecha)
            ->where('descripcion', trim($request->descripcion))
            ->exists();

        if ($duplicado) {
            return back()->withErrors(['descripcion' => 'Ya existe un registro de horas con idéntica descripción y fecha en esta práctica.'])->withInput();
        }

        // 6. Guardar inicialmente como pendiente
        RegistroHora::create([
            'practica_id' => $practica->id,
            'actividad_id' => $request->actividad_id,
            'fecha' => $request->fecha,
            'horas' => $request->horas,
            'descripcion' => trim($request->descripcion),
            'estado' => 'pendiente',
            'created_at' => now(),
        ]);

        return back()->with('success', 'Jornada de horas registrada correctamente en estado pendiente.');
    }

    public function validarHora(Request $request, RegistroHora $hora)
    {
        $user = Auth::user();

        // Seguridad: Exclusivo Docente Tutor (Regla 29 y requisitos de separación de roles)
        if (!$user->isTutor()) {
            abort(403, 'Acción no autorizada. La validación o rechazo de horas es competencia exclusiva del docente tutor asignado.');
        }

        // Seguridad: Si es tutor, debe ser el tutor asignado a la práctica
        if ($hora->practica->tutor_id !== $user->tutor?->id) {
            abort(403, 'Acceso denegado. No tienes autorización para validar horas de una práctica no asignada a tu supervisión.');
        }

        $rules = [
            'estado' => 'required|in:validada,rechazada',
            'observacion_revision' => $request->estado === 'rechazada' ? 'required|string|min:3|max:300' : 'nullable|string|max:300',
        ];
        $messages = [
            'observacion_revision.required' => 'Es obligatorio ingresar el motivo u observación al rechazar una jornada de horas.',
        ];
        $request->validate($rules, $messages);

        $tutor = $user->tutor ?? $hora->practica->tutor ?? DocenteTutor::first();

        $hora->update([
            'estado' => $request->estado,
            'observacion_revision' => $request->observacion_revision,
            'revisado_por_tutor_id' => $tutor?->id,
            'fecha_revision' => now(),
        ]);

        return back()->with('success', "Registro de horas marcado como: {$request->estado}.");
    }

    public function configurarPractica(Request $request, Practica $practica)
    {
        $user = Auth::user();
        if (!$user->isCoordinador()) {
            abort(403, 'Acción no autorizada.');
        }

        $request->validate([
            'fecha_inicio' => 'required|date',
            'fecha_fin_prevista' => 'required|date|after_or_equal:fecha_inicio',
            'horas_objetivo' => 'required|numeric|min:20|max:1000',
            'tutor_id' => 'nullable|exists:docentes_tutores,id',
        ]);

        $practica->update([
            'fecha_inicio' => $request->fecha_inicio,
            'fecha_fin_prevista' => $request->fecha_fin_prevista,
            'horas_objetivo' => $request->horas_objetivo,
            'tutor_id' => $request->tutor_id,
        ]);

        return back()->with('success', 'Parámetros institucionales de la práctica actualizados.');
    }

    public function storeActividad(Request $request, Practica $practica)
    {
        $user = Auth::user();
        if (!$user->isCoordinador()) {
            abort(403, 'Acción no autorizada.');
        }

        $request->validate([
            'titulo' => 'required|string|max:180',
            'objetivo' => 'nullable|string|max:300',
            'descripcion' => 'nullable|string|max:1000',
            'fecha_inicio' => 'nullable|date',
            'fecha_fin' => 'nullable|date|after_or_equal:fecha_inicio',
            'horas_planificadas' => 'nullable|numeric|min:1|max:240',
        ]);

        CronogramaActividad::create([
            'practica_id' => $practica->id,
            'titulo' => $request->titulo,
            'objetivo' => $request->objetivo,
            'descripcion' => $request->descripcion,
            'fecha_inicio' => $request->fecha_inicio,
            'fecha_fin' => $request->fecha_fin,
            'horas_planificadas' => $request->horas_planificadas ?? 0,
            'estado' => 'pendiente',
        ]);

        return back()->with('success', 'Actividad planificada agregada al cronograma de la práctica.');
    }

    public function updateActividad(Request $request, CronogramaActividad $actividad)
    {
        $user = Auth::user();
        if (!$user->isCoordinador()) {
            abort(403, 'Acción no autorizada.');
        }

        $request->validate([
            'titulo' => 'required|string|max:180',
            'objetivo' => 'nullable|string|max:300',
            'descripcion' => 'nullable|string|max:1000',
            'fecha_inicio' => 'nullable|date',
            'fecha_fin' => 'nullable|date|after_or_equal:fecha_inicio',
            'horas_planificadas' => 'nullable|numeric|min:1|max:240',
            'estado' => 'required|in:pendiente,en_proceso,completada,cancelada',
        ]);

        $actividad->update([
            'titulo' => $request->titulo,
            'objetivo' => $request->objetivo,
            'descripcion' => $request->descripcion,
            'fecha_inicio' => $request->fecha_inicio,
            'fecha_fin' => $request->fecha_fin,
            'horas_planificadas' => $request->horas_planificadas ?? $actividad->horas_planificadas,
            'estado' => $request->estado,
            'fecha_completada' => $request->estado === 'completada' ? now()->toDateString() : null,
        ]);

        return back()->with('success', 'Actividad del cronograma actualizada correctamente.');
    }

    public function storeEvaluacionOrganizacion(Request $request, Practica $practica)
    {
        $user = Auth::user();
        if (!$user->isCoordinador()) {
            abort(403, 'Acción no autorizada. Solo coordinación académica puede registrar la valoración empresarial.');
        }

        $request->validate([
            'responsable_id' => 'required|exists:responsables_organizacion,id',
            'fecha' => 'required|date',
            'desempeno' => 'required|string|max:50',
            'cumplimiento' => 'required|string|max:50',
            'comentario' => 'required|string|max:1000',
            'recomendacion' => 'nullable|string|max:1000',
            'archivo_evidencia' => 'nullable|file|mimes:pdf,doc,docx,jpg,png|max:10240',
        ]);

        // Verificar que el contacto pertenezca a la organización receptora de la práctica
        $responsable = ResponsableOrganizacion::findOrFail($request->responsable_id);
        if ($responsable->organizacion_id !== $practica->solicitud->organizacion_id) {
            return back()->withErrors(['responsable_id' => 'El contacto seleccionado no pertenece a la organización receptora de la práctica.'])->withInput();
        }

        $path = null;
        if ($request->hasFile('archivo_evidencia')) {
            $path = $request->file('archivo_evidencia')->store("practicas/{$practica->id}/empresa", 'local');
        }

        EvaluacionOrganizacion::updateOrCreate(
            ['practica_id' => $practica->id],
            [
                'responsable_id' => $request->responsable_id,
                'registrada_por' => $user->id,
                'fecha' => $request->fecha,
                'desempeno' => $request->desempeno,
                'cumplimiento' => $request->cumplimiento,
                'comentario' => $request->comentario,
                'recomendacion' => $request->recomendacion,
                'ruta_evidencia' => $path,
            ]
        );

        return back()->with('success', 'Valoración de la organización receptora registrada exitosamente en el expediente.');
    }

    public function cerrarPractica(Request $request, Practica $practica)
    {
        $user = Auth::user();

        // RF-14: Exclusivo de Coordinación Académica
        if (!$user->isCoordinador()) {
            abort(403, 'Acceso denegado. El cierre y resolución de prácticas corresponde exclusivamente a la Coordinación Académica.');
        }

        // RF-14: La práctica debe encontrarse en curso para proceder al cierre formal
        if ($practica->estado !== 'en_curso') {
            return back()->withErrors(['nuevo_estado' => "Solo se puede cerrar o resolver una práctica que se encuentre 'en curso' (Estado actual: {$practica->estado})."])->withInput();
        }

        $request->validate([
            'nuevo_estado' => 'required|in:concluida,abandonada,cancelada',
            'motivo' => 'required|string|min:5|max:500',
        ], [
            'nuevo_estado.required' => 'Selecciona el estado de cierre para la práctica.',
            'nuevo_estado.in' => 'El estado de cierre debe ser concluida, abandonada o cancelada.',
            'motivo.required' => 'Ingresa el motivo o resolución institucional del cierre de la práctica.',
            'motivo.min' => 'El motivo del cierre debe contener al menos 5 caracteres.',
        ]);

        $estadoAnterior = $practica->estado;
        $nuevoEstado = $request->nuevo_estado;

        // RF-14: Verificaciones estrictas antes de declarar CONCLUIDA una práctica
        if ($nuevoEstado === 'concluida') {
            // 1. Cumplimiento del objetivo de horas validadas
            $horasValidadas = (float) $practica->horas_validadas;
            $horasMeta = (float) $practica->horas_objetivo;
            if ($horasValidadas < $horasMeta) {
                return back()->withErrors([
                    'nuevo_estado' => "No se puede concluir la práctica: El estudiante no ha cumplido el objetivo de horas validadas ({$horasValidadas} de {$horasMeta} hrs requeridas)."
                ])->withInput();
            }

            // 2. Existencia de evaluación final emitida por el docente tutor
            if (!$practica->evaluacionFinal) {
                return back()->withErrors([
                    'nuevo_estado' => 'No se puede concluir la práctica: Es obligatorio que el docente tutor haya registrado la evaluación final.'
                ])->withInput();
            }

            // 3. Informes obligatorios de avance revisados y aprobados
            $informesRevisados = $practica->informes()->where('estado', 'revisado')->count();
            if ($informesRevisados === 0) {
                return back()->withErrors([
                    'nuevo_estado' => 'No se puede concluir la práctica: Es obligatorio contar con al menos un informe de avance revisado y aprobado por el tutor.'
                ])->withInput();
            }
        }

        DB::transaction(function () use ($practica, $estadoAnterior, $nuevoEstado, $request, $user) {
            $practica->update([
                'estado' => $nuevoEstado,
                'fecha_fin_real' => now()->toDateString(),
                'observacion_cierre' => $request->motivo,
                'motivo_abandono' => in_array($nuevoEstado, ['abandonada', 'cancelada']) ? $request->motivo : null,
            ]);

            HistorialEstadoPractica::create([
                'practica_id' => $practica->id,
                'estado_anterior' => $estadoAnterior,
                'estado_nuevo' => $nuevoEstado,
                'motivo' => "Resolución de Coordinación Académica ({$nuevoEstado}): {$request->motivo}",
                'cambiado_por' => $user->id,
                'fecha_cambio' => now(),
            ]);
        });

        return back()->with('success', "Práctica actualizada correctamente a estado: " . ucfirst($nuevoEstado) . ".");
    }

    public function storeInforme(Request $request, Practica $practica)
    {
        $user = Auth::user();

        // RF-12: Validar que el usuario sea el estudiante propietario de la práctica
        if (!$user->isEstudiante() || $user->estudiante?->id !== $practica->solicitud->estudiante_id) {
            abort(403, 'Acceso denegado. No tienes autorización para presentar informes en esta práctica.');
        }

        // RF-12: Solo permitir informes en prácticas en curso
        if ($practica->estado !== 'en_curso') {
            return back()->withErrors(['informe' => "Solo se pueden presentar informes en prácticas 'en curso' (Estado actual: {$practica->estado})."])->withInput();
        }

        $request->validate([
            'titulo' => 'required|string|min:3|max:200',
            'periodo_inicio' => 'required|date',
            'periodo_fin' => 'required|date|after_or_equal:periodo_inicio',
            'resumen' => 'required|string|min:10|max:2000',
            'archivo' => 'required|file|mimes:pdf,doc,docx|max:10240',
        ], [
            'titulo.required' => 'Ingresa el título del informe de avance.',
            'periodo_fin.after_or_equal' => 'La fecha fin no puede ser anterior a la fecha de inicio del periodo reportado.',
            'resumen.required' => 'Describe el resumen de actividades del periodo reportado.',
            'resumen.min' => 'El resumen debe contener al menos 10 caracteres.',
            'archivo.required' => 'Selecciona un documento para el informe.',
            'archivo.mimes' => 'Formato no permitido. Para informes académicos, adjunta únicamente archivos PDF o Word (.pdf, .doc, .docx).',
            'archivo.max' => 'El archivo supera el tamaño máximo permitido de 10 MB.',
        ]);

        // Evitar informes duplicados para el mismo periodo y título
        $duplicado = InformeAvance::where('practica_id', $practica->id)
            ->where('titulo', trim($request->titulo))
            ->where('periodo_inicio', $request->periodo_inicio)
            ->exists();

        if ($duplicado) {
            return back()->withErrors(['titulo' => 'Ya existe un informe de avance registrado con este título para el mismo periodo.'])->withInput();
        }

        $path = $request->file('archivo')->store("practicas/{$practica->id}/informes", 'local');

        InformeAvance::create([
            'practica_id' => $practica->id,
            'titulo' => trim($request->titulo),
            'periodo_inicio' => $request->periodo_inicio,
            'periodo_fin' => $request->periodo_fin,
            'resumen' => trim($request->resumen),
            'ruta_privada' => $path,
            'estado' => 'enviado',
            'fecha_envio' => now(),
        ]);

        return back()->with('success', 'Informe de avance presentado correctamente. Tu docente tutor lo revisará.');
    }

    public function descargarInforme(InformeAvance $informe)
    {
        $user = Auth::user();

        // RF-12: Autorización de descarga
        if ($user->isEstudiante() && $user->estudiante?->id !== $informe->practica->solicitud->estudiante_id) {
            abort(403, 'Acceso denegado. No tienes autorización para descargar este informe.');
        }

        if ($user->isTutor() && $informe->practica->tutor_id !== $user->tutor?->id) {
            abort(403, 'Acceso denegado. No tienes autorización para descargar informes de prácticas que no supervisas.');
        }

        if (!$user->isEstudiante() && !$user->isTutor() && !$user->isCoordinador() && !$user->isAdmin()) {
            abort(403, 'Acceso denegado.');
        }

        // RF-12: No fabricar archivos ficticios. Si el físico no existe, reportar error real.
        if (!$informe->ruta_privada || !Storage::disk('local')->exists($informe->ruta_privada)) {
            return back()->withErrors([
                'archivo' => "El archivo del informe '{$informe->titulo}' no se encuentra en el almacenamiento del servidor.",
            ]);
        }

        return Storage::disk('local')->download($informe->ruta_privada, "Informe_{$informe->id}_{$informe->titulo}");
    }

    public function revisarInforme(Request $request, InformeAvance $informe)
    {
        $user = Auth::user();

        // RF-12: Solo el docente tutor asignado puede revisar informes
        if (!$user->isTutor()) {
            abort(403, 'Acción no autorizada. La revisión de informes corresponde al docente tutor asignado.');
        }

        if ($informe->practica->tutor_id !== $user->tutor?->id) {
            abort(403, 'Acceso denegado. No tienes autorización para revisar informes de una práctica que no supervisas.');
        }

        $request->validate([
            'estado' => 'required|in:revisado,observado',
            'observacion' => $request->estado === 'observado' ? 'required|string|min:5|max:1000' : 'nullable|string|max:1000',
        ], [
            'observacion.required' => 'Debe ingresar una observación detallada al marcar un informe como observado.',
            'observacion.min' => 'La observación debe contener al menos 5 caracteres.',
        ]);

        $tutor = $user->tutor ?? $informe->practica->tutor ?? DocenteTutor::first();

        $informe->update([
            'estado' => $request->estado,
            'revisado_por_tutor_id' => $tutor?->id,
            'fecha_revision' => now(),
        ]);

        if ($request->filled('observacion')) {
            ObservacionTutor::create([
                'practica_id' => $informe->practica_id,
                'tutor_id' => $tutor?->id ?? 1,
                'informe_id' => $informe->id,
                'detalle' => "Observación sobre informe '{$informe->titulo}': " . $request->observacion,
                'fecha' => now(),
            ]);
        }

        return back()->with('success', "Informe de avance actualizado a estado: {$request->estado}.");
    }

    public function storeObservacion(Request $request, Practica $practica)
    {
        $user = Auth::user();

        // RF-13: Exclusivo del docente tutor asignado
        if (!$user->isTutor()) {
            abort(403, 'Acción no autorizada. Solo los docentes tutores pueden registrar observaciones de seguimiento.');
        }

        if ($practica->tutor_id !== $user->tutor?->id) {
            abort(403, 'No tienes autorización para registrar observaciones en una práctica que no supervisas.');
        }

        $request->validate([
            'detalle' => 'required|string|min:5|max:1000',
            'informe_id' => 'nullable|exists:informes_avance,id',
        ], [
            'detalle.required' => 'Ingresa el detalle de la observación de seguimiento.',
            'detalle.min' => 'La observación debe contener al menos 5 caracteres.',
        ]);

        ObservacionTutor::create([
            'practica_id' => $practica->id,
            'tutor_id' => $user->tutor->id,
            'informe_id' => $request->informe_id,
            'detalle' => trim($request->detalle),
            'fecha' => now(),
        ]);

        return back()->with('success', 'Observación de seguimiento registrada para el estudiante.');
    }

    public function storeEvaluacionParcial(Request $request, Practica $practica)
    {
        $user = Auth::user();

        if (!$user->isTutor()) {
            abort(403, 'Acción no autorizada. Las evaluaciones parciales solo pueden ser registradas por el docente tutor asignado.');
        }

        if ($practica->tutor_id !== $user->tutor?->id) {
            abort(403, 'No tienes autorización para registrar evaluaciones en una práctica que no supervisas.');
        }

        $request->validate([
            'periodo' => 'required|string|max:100',
            'fecha' => 'required|date',
            'calificacion' => 'required|numeric|between:0,100',
            'resultado' => 'required|string|max:50',
            'comentario' => 'nullable|string|max:1000',
        ]);

        EvaluacionSeguimiento::create([
            'practica_id' => $practica->id,
            'tutor_id' => $user->tutor->id,
            'fecha' => $request->fecha,
            'periodo' => $request->periodo,
            'resultado' => $request->resultado,
            'calificacion' => $request->calificacion,
            'comentario' => $request->comentario,
        ]);

        return back()->with('success', 'Evaluación parcial registrada correctamente.');
    }

    public function storeEvaluacionFinal(Request $request, Practica $practica)
    {
        $user = Auth::user();

        // RF-13: Exclusivo del docente tutor asignado
        if (!$user->isTutor()) {
            abort(403, 'Acción no autorizada. La evaluación final es competencia exclusiva del docente tutor asignado.');
        }

        if ($practica->tutor_id !== $user->tutor?->id) {
            abort(403, 'Solo el docente tutor asignado a la práctica puede registrar la evaluación final.');
        }

        if ($practica->estado === 'concluida') {
            return back()->withErrors(['evaluacion' => 'Esta práctica ya fue concluida formalmente por Coordinación Académica.']);
        }

        $request->validate([
            'calificacion' => 'required|numeric|between:0,100',
            'resultado' => 'required|in:Aprobado,Reprobado',
            'comentario' => 'required|string|min:5|max:1000',
        ], [
            'calificacion.required' => 'Ingresa la calificación de la evaluación final (0 a 100).',
            'resultado.required' => 'Selecciona el resultado de la evaluación (Aprobado o Reprobado).',
            'comentario.required' => 'Ingresa el comentario u opinión evaluativa sobre el desempeño del estudiante.',
        ]);

        $tutor = $practica->tutor ?? $user->tutor ?? DocenteTutor::first();

        // RF-13 CORRECCIÓN FUNDAMENTAL: Guardar la evaluación final NO concluye la práctica.
        // La evaluación final es potestad del tutor; el cierre formal corresponde a Coordinación.
        EvaluacionFinal::updateOrCreate(
            ['practica_id' => $practica->id],
            [
                'tutor_id' => $tutor?->id ?? 1,
                'fecha' => now()->toDateString(),
                'calificacion' => $request->calificacion,
                'resultado' => $request->resultado,
                'comentario' => trim($request->comentario),
            ]
        );

        return back()->with('success', 'Evaluación final guardada exitosamente. La práctica permanece en curso hasta el cierre formal por Coordinación Académica.');
    }
}

