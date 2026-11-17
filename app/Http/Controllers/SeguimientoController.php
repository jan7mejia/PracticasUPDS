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
        $query = Practica::with(['solicitud.estudiante.carrera', 'solicitud.organizacion', 'tutor']);

        if ($user->isTutor()) {
            $tutor = $user->tutor;
            $query->where('tutor_id', $tutor?->id);
        }

        if ($request->filled('estado')) {
            $query->where('estado', $request->estado);
        }

        $practicas = $query->latest()->paginate(10);
        return view('seguimiento.lista', compact('practicas'));
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
        if ($user->isEstudiante() || $user->isTutor()) {
            abort(403, 'Acción no autorizada. La asignación de tutores es potestad de la Coordinación Académica.');
        }

        $request->validate([
            'tutor_id' => 'required|exists:docentes_tutores,id',
        ]);

        $tutor = DocenteTutor::where('id', $request->tutor_id)->where('activo', true)->first();
        if (!$tutor) {
            return back()->withErrors(['tutor_id' => 'El docente tutor seleccionado no se encuentra activo para asignaciones.']);
        }

        $tutorAnterior = $practica->tutor ? $practica->tutor->nombre_completo : 'Sin tutor';

        $practica->update([
            'tutor_id' => $tutor->id,
            'estado' => $practica->estado === 'planificada' ? 'en_curso' : $practica->estado,
        ]);

        HistorialEstadoPractica::create([
            'practica_id' => $practica->id,
            'estado_anterior' => $practica->estado,
            'estado_nuevo' => $practica->estado,
            'motivo' => "Asignación de docente tutor: {$tutor->nombre_completo} (Anterior: {$tutorAnterior}).",
            'cambiado_por' => $user->id,
            'fecha_cambio' => now(),
        ]);

        return back()->with('success', "Docente tutor {$tutor->nombre_completo} asignado correctamente a la práctica.");
    }

    public function storeHora(Request $request, Practica $practica)
    {
        $user = Auth::user();

        // 1. Validar que la práctica pertenezca al estudiante autenticado
        if ($user->isEstudiante() && $user->estudiante?->id !== $practica->solicitud->estudiante_id) {
            abort(403, 'No tienes autorización para registrar horas en esta práctica.');
        }

        // 2. No permitir registrar horas en prácticas finalizadas, canceladas o abandonadas
        if (in_array($practica->estado, ['concluida', 'cancelada', 'abandonada'])) {
            return back()->withErrors(['horas' => "No se pueden registrar horas en una práctica con estado: {$practica->estado}."])->withInput();
        }

        $request->validate([
            'fecha' => 'required|date|before_or_equal:today',
            'horas' => 'required|numeric|min:0.5|max:24',
            'descripcion' => 'required|string|max:500',
            'actividad_id' => 'nullable|exists:cronograma_actividades,id',
        ]);

        // 3. Validar que la fecha esté dentro del periodo de la práctica (si tiene fecha de inicio definida)
        if ($practica->fecha_inicio && $request->fecha < $practica->fecha_inicio->format('Y-m-d')) {
            return back()->withErrors(['fecha' => "La fecha de la jornada no puede ser anterior al inicio de la práctica ({$practica->fecha_inicio->format('d/m/Y')})."])->withInput();
        }

        // 4. Validar suma diaria: Máximo 24 horas por día en total para evitar incongruencias
        $horasDelDia = RegistroHora::where('practica_id', $practica->id)
            ->whereDate('fecha', $request->fecha)
            ->sum('horas');

        if (($horasDelDia + (float)$request->horas) > 24) {
            $disponibles = max(0, 24 - $horasDelDia);
            return back()->withErrors(['horas' => "El total de horas para el día {$request->fecha} superaría el límite de 24 horas diarias. Horas ya registradas: {$horasDelDia}h (máximo disponible: {$disponibles}h)."])->withInput();
        }

        RegistroHora::create([
            'practica_id' => $practica->id,
            'actividad_id' => $request->actividad_id,
            'fecha' => $request->fecha,
            'horas' => $request->horas,
            'descripcion' => $request->descripcion,
            'estado' => 'pendiente',
            'created_at' => now(),
        ]);

        return back()->with('success', 'Jornada de horas registrada exitosamente en estado pendiente. Tu docente tutor revisará y validará la jornada.');
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
        if (!$user->isCoordinador()) {
            abort(403, 'Acción no autorizada. El cierre y resolución de prácticas corresponde a Coordinación Académica.');
        }

        $request->validate([
            'nuevo_estado' => 'required|in:concluida,abandonada,cancelada',
            'motivo' => 'required|string|max:500',
        ]);

        $estadoAnterior = $practica->estado;
        $nuevoEstado = $request->nuevo_estado;

        // Validaciones previas al cierre formal
        if ($nuevoEstado === 'concluida') {
            if (!$practica->evaluacionFinal) {
                return back()->withErrors(['nuevo_estado' => 'No se puede concluir la práctica: Es obligatorio que el docente tutor haya registrado la evaluación final.'])->withInput();
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

        return back()->with('success', "Práctica actualizada a estado: " . strtoupper($nuevoEstado) . " exitosamente.");
    }

    public function storeInforme(Request $request, Practica $practica)
    {
        $user = Auth::user();

        // 1. Validar que la práctica pertenezca al estudiante autenticado
        if ($user->isEstudiante() && $user->estudiante?->id !== $practica->solicitud->estudiante_id) {
            abort(403, 'No tienes autorización para presentar informes en esta práctica.');
        }

        $request->validate([
            'titulo' => 'required|string|max:200',
            'periodo_inicio' => 'required|date',
            'periodo_fin' => 'required|date|after_or_equal:periodo_inicio',
            'resumen' => 'nullable|string|max:2000',
            'archivo' => 'nullable|file|mimes:pdf,doc,docx|max:10240',
        ]);

        $path = null;
        if ($request->hasFile('archivo')) {
            $path = $request->file('archivo')->store("practicas/{$practica->id}/informes", 'local');
        }

        InformeAvance::create([
            'practica_id' => $practica->id,
            'titulo' => $request->titulo,
            'periodo_inicio' => $request->periodo_inicio,
            'periodo_fin' => $request->periodo_fin,
            'resumen' => $request->resumen,
            'ruta_privada' => $path,
            'estado' => 'enviado',
            'fecha_envio' => now(),
        ]);

        return back()->with('success', 'Informe de avance presentado con éxito. Tu docente tutor lo revisará.');
    }

    public function descargarInforme(InformeAvance $informe)
    {
        $user = Auth::user();

        // Si es estudiante, validar que sea su práctica
        if ($user->isEstudiante() && $user->estudiante?->id !== $informe->practica->solicitud->estudiante_id) {
            abort(403, 'No tienes autorización para descargar este informe.');
        }

        // Si es tutor, validar que supervise la práctica
        if ($user->isTutor() && $informe->practica->tutor_id !== $user->tutor?->id) {
            abort(403, 'No tienes autorización para descargar informes de prácticas que no supervisas.');
        }

        if (!$informe->ruta_privada || !Storage::disk('local')->exists($informe->ruta_privada)) {
            $demoPath = "practicas/{$informe->practica_id}/informes/informe_{$informe->id}.txt";
            if (!Storage::disk('local')->exists($demoPath)) {
                Storage::disk('local')->put($demoPath, "UPDS - Informe de Avance #{$informe->id}\nTítulo: {$informe->titulo}\nPeríodo: {$informe->periodo_inicio} al {$informe->periodo_fin}\nResumen: " . ($informe->resumen ?? 'Sin resumen'));
            }
            return Storage::disk('local')->download($demoPath, "Informe_{$informe->id}_{$informe->titulo}.txt");
        }

        return Storage::disk('local')->download($informe->ruta_privada, "Informe_{$informe->id}_{$informe->titulo}");
    }

    public function revisarInforme(Request $request, InformeAvance $informe)
    {
        $user = Auth::user();

        if ($user->isEstudiante()) {
            abort(403, 'Acción no autorizada. Los estudiantes no tienen permisos para revisar informes.');
        }

        if ($user->isTutor() && $informe->practica->tutor_id !== $user->tutor?->id) {
            abort(403, 'No tienes autorización para revisar informes de una práctica que no supervisas.');
        }

        $request->validate([
            'estado' => 'required|in:revisado,observado',
            'observacion' => $request->estado === 'observado' ? 'required|string|min:5|max:1000' : 'nullable|string|max:1000',
        ], [
            'observacion.required' => 'Debe ingresar una observación detallada al marcar un informe como observado.',
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

        // Seguridad: Exclusivo del docente tutor asignado
        if (!$user->isTutor()) {
            abort(403, 'Acción no autorizada. Solo los docentes tutores pueden registrar observaciones de seguimiento.');
        }

        if ($practica->tutor_id !== $user->tutor?->id) {
            abort(403, 'No tienes autorización para registrar observaciones en una práctica que no supervisas.');
        }

        $request->validate([
            'detalle' => 'required|string|max:1000',
            'informe_id' => 'nullable|exists:informes_avance,id',
        ]);

        ObservacionTutor::create([
            'practica_id' => $practica->id,
            'tutor_id' => $user->tutor->id,
            'informe_id' => $request->informe_id,
            'detalle' => $request->detalle,
            'fecha' => now(),
        ]);

        return back()->with('success', 'Observación de seguimiento registrada para el estudiante.');
    }

    public function storeEvaluacionParcial(Request $request, Practica $practica)
    {
        $user = Auth::user();

        // Seguridad: Exclusivo del docente tutor asignado
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

        // Seguridad: Exclusivo del docente tutor asignado
        if (!$user->isTutor()) {
            abort(403, 'Acción no autorizada. La evaluación final es competencia exclusiva del docente tutor asignado.');
        }

        if ($practica->tutor_id !== $user->tutor?->id) {
            abort(403, 'Solo el docente tutor asignado a la práctica puede registrar la evaluación final.');
        }

        if ($practica->estado === 'concluida') {
            return back()->withErrors(['evaluacion' => 'Esta práctica ya fue concluida con evaluación final previamente.']);
        }

        $request->validate([
            'calificacion' => 'required|numeric|between:0,100',
            'resultado' => 'required|in:Aprobado,Reprobado',
            'comentario' => 'required|string|max:1000',
        ]);

        $tutor = $practica->tutor ?? $user->tutor ?? DocenteTutor::first();

        DB::transaction(function () use ($practica, $request, $tutor, $user) {
            EvaluacionFinal::updateOrCreate(
                ['practica_id' => $practica->id],
                [
                    'tutor_id' => $tutor?->id ?? 1,
                    'fecha' => now()->toDateString(),
                    'calificacion' => $request->calificacion,
                    'resultado' => $request->resultado,
                    'comentario' => $request->comentario,
                ]
            );

            $estadoAnterior = $practica->estado;
            $practica->update([
                'estado' => 'concluida',
                'fecha_fin_real' => now()->toDateString(),
                'observacion_cierre' => $request->comentario,
            ]);

            HistorialEstadoPractica::create([
                'practica_id' => $practica->id,
                'estado_anterior' => $estadoAnterior,
                'estado_nuevo' => 'concluida',
                'motivo' => "Evaluación final registrada: {$request->resultado} con nota {$request->calificacion}/100.",
                'cambiado_por' => $user->id,
                'fecha_cambio' => now(),
            ]);
        });

        return back()->with('success', 'Evaluación final guardada y práctica marcada como CONCLUIDA.');
    }
}

