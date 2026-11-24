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
use App\Models\Coordinador;
use App\Models\JustificacionAsistencia;
use App\Models\IncidenciaPractica;
use App\Models\RubricaActividad;
use App\Models\CriterioRubrica;
use App\Models\EvaluacionCriterio;

class SeguimientoController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();

        if ($user->isEstudiante()) {
            $estudiante = $user->estudiante;
            // Priorizar práctica en_curso; si no hay, tomar la más reciente
            $practica = Practica::whereHas('solicitud', function ($q) use ($estudiante) {
                $q->where('estudiante_id', $estudiante?->id);
            })->orderByRaw("FIELD(estado,'en_curso','observada','planificada','concluida','abandonada','cancelada')")
              ->orderBy('id', 'desc')
              ->first();

            if ($practica) {
                return redirect()->route('seguimiento.show', $practica->id);
            }

            return view('seguimiento.vacio', ['mensaje' => 'Aún no tienes una práctica profesional en seguimiento. Inicia con una solicitud de práctica.']);
        }

        // Para Tutor, Coordinador y Administrador
        $perPage = in_array((int)$request->get('per_page'), [10, 25, 50]) ? (int)$request->get('per_page') : 10;
        $query = Practica::with([
            'solicitud.estudiante.carrera',
            'solicitud.organizacion',
            'tutor',
            'registrosHoras' => fn($q) => $q->select('id','practica_id','horas','estado'),
        ]);

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
            'actividades.rubrica.criterios.evaluaciones',
            'registrosHoras' => fn($q) => $q->with(['revisor', 'actividad'])->orderBy('fecha', 'desc'),
            'informes' => fn($q) => $q->with(['actividad', 'evaluacionesCriterio'])->orderBy('periodo_inicio', 'desc'),
            'observaciones.tutor',
            'evaluacionesSeguimiento.tutor',
            'evaluacionFinal',
            'evaluacionOrganizacion.responsable',
            'historial.usuario',
            'justificaciones.coordinador',
            'incidencias.registrador',
        ]);

        $carreraId = $practica->solicitud->estudiante?->carrera_id;
        $tutores = DocenteTutor::where('activo', true)
            ->when($carreraId, function ($query) use ($carreraId) {
                $query->where(function ($q) use ($carreraId) {
                    $q->where('carrera_id', $carreraId)->orWhereNull('carrera_id');
                });
            })
            ->with('carrera')
            ->get();

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

        // Control de Carrera: El docente tutor y el estudiante deben pertenecer a la misma carrera académica
        $carreraEstudianteId = $practica->solicitud->estudiante?->carrera_id;
        if ($carreraEstudianteId && $tutor->carrera_id && $tutor->carrera_id !== $carreraEstudianteId) {
            $carreraNombre = $practica->solicitud->estudiante->carrera->nombre ?? 'la carrera del estudiante';
            return back()->withErrors([
                'tutor_id' => "No es posible asignar al tutor {$tutor->nombre_completo}: Por normativa institucional, el docente tutor debe pertenecer a la misma carrera que el estudiante ({$carreraNombre})."
            ])->withInput();
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
            return back()->withErrors(['horas' => "No puedes registrar horas en esta práctica: solo se pueden registrar horas cuando la práctica se encuentra en estado 'en curso' (Estado actual: {$practica->estado})."])->withInput();
        }

        // P0: Para prácticas nuevas, el cronograma de actividades debe estar aprobado por Coordinación
        if (!$practica->esHistorica() && !$practica->cronograma_aprobado) {
            return back()->withErrors(['horas' => 'El cronograma de actividades debe ser aprobado formalmente por la Coordinación Académica antes de iniciar el registro de horas.'])->withInput();
        }

        // Compatibilidad: calcular horas a partir de horas y minutos si fueron enviados
        if ($request->has('duracion_horas') || $request->has('duracion_minutos')) {
            $h = (int) $request->input('duracion_horas', 0);
            $m = (int) $request->input('duracion_minutos', 0);
            $totalDecimal = round($h + ($m / 60.0), 2);
            $request->merge(['horas' => $totalDecimal]);
        }

        $maxDiarias = (float) config('practicas.max_horas_diarias', 4.0);

        $request->validate([
            'fecha' => 'required|date|before_or_equal:today',
            'horas' => 'required|numeric|min:0.5|max:' . $maxDiarias,
            'descripcion' => 'required|string|min:5|max:500',
            'actividad_id' => 'nullable|exists:cronograma_actividades,id',
        ], [
            'fecha.required' => 'La fecha de la jornada es obligatoria.',
            'fecha.before_or_equal' => 'La fecha no está dentro del periodo permitido.',
            'horas.required' => 'Ingresa la cantidad de horas realizadas.',
            'horas.min' => 'El tiempo mínimo por jornada debe ser de al menos 30 minutos (0.5 hrs).',
            'horas.max' => "El máximo diario es de {$maxDiarias} horas.",
            'descripcion.required' => 'Describe las actividades realizadas durante la jornada.',
            'descripcion.min' => 'La descripción debe tener al menos 5 caracteres.',
        ]);

        // 3. Validar que la fecha esté dentro del periodo de la práctica (si tiene fecha de inicio)
        if ($practica->fecha_inicio && $request->fecha < $practica->fecha_inicio->format('Y-m-d')) {
            return back()->withErrors(['fecha' => "La fecha no está dentro del periodo permitido: no puede ser anterior al inicio de la práctica ({$practica->fecha_inicio->format('d/m/Y')})."])->withInput();
        }
        if ($practica->fecha_fin_real && $request->fecha > $practica->fecha_fin_real->format('Y-m-d')) {
            return back()->withErrors(['fecha' => "La fecha no está dentro del periodo permitido: la práctica concluyó formalmente el {$practica->fecha_fin_real->format('d/m/Y')}."])->withInput();
        }

        // 4. Regla UPDS: Un único registro de jornada por día (asistencia a turno continuo)
        return DB::transaction(function () use ($practica, $request, $maxDiarias) {
            $registroPrevio = RegistroHora::where('practica_id', $practica->id)
                ->whereDate('fecha', $request->fecha)
                ->lockForUpdate()
                ->first();

            if ($registroPrevio) {
                $fechaFmt = \Carbon\Carbon::parse($request->fecha)->format('d/m/Y');
                return back()->withErrors([
                    'horas' => "Ya registraste tu jornada de práctica para el día {$fechaFmt} ({$registroPrevio->horas} hrs registradas en estado '{$registroPrevio->estado}'). Por normativa académica de la UPDS, solo se permite un único registro de asistencia por día."
                ])->withInput();
            }

            // Validar que no supere el máximo diario permitido (4.0 horas)
            if ((float)$request->horas > $maxDiarias) {
                return back()->withErrors(['horas' => "El máximo permitido por jornada diaria es de {$maxDiarias} horas."])->withInput();
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

            return back()->with('success', 'Jornada registrada.');
        });
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
            abort(403, 'Acción no autorizada. Solo la Coordinación Académica puede configurar parámetros de la práctica.');
        }

        $rules = [
            'fecha_inicio' => 'required|date',
            'tutor_id' => 'nullable|exists:docentes_tutores,id',
        ];

        if ($practica->esHistorica()) {
            $rules['fecha_fin_prevista'] = 'required|date|after_or_equal:fecha_inicio';
            $rules['horas_objetivo'] = 'required|numeric|min:20|max:1000';
            $request->validate($rules);

            $practica->update([
                'fecha_inicio' => $request->fecha_inicio,
                'fecha_fin_prevista' => $request->fecha_fin_prevista,
                'horas_objetivo' => $request->horas_objetivo,
                'tutor_id' => $request->tutor_id,
            ]);
        } else {
            $request->validate($rules);
            // P0: Duración exacta de 3 meses calendario para nuevas prácticas
            // Horas requeridas = Días hábiles (L-V) * 4 horas/día
            $fechaFin = Practica::calcularFechaFin3Meses($request->fecha_inicio);
            $horasCalc = Practica::calcularHorasRequeridasV2($request->fecha_inicio, $fechaFin);

            $practica->update([
                'fecha_inicio' => $request->fecha_inicio,
                'fecha_fin_prevista' => $fechaFin,
                'horas_objetivo' => $horasCalc,
                'horas_programadas' => $horasCalc,
                'tutor_id' => $request->tutor_id,
            ]);
        }

        return back()->with('success', 'Parámetros institucionales de la práctica actualizados.');
    }

    public function storeActividad(Request $request, Practica $practica)
    {
        $user = Auth::user();
        $esCoord = $user->isCoordinador();
        $esTutorAsignado = $user->isTutor() && ($practica->tutor_id === $user->tutor?->id);

        if (!$esCoord && !$esTutorAsignado) {
            abort(403, 'Acción no autorizada. La planificación de actividades corresponde al docente tutor asignado o a la Coordinación Académica.');
        }

        $rules = [
            'titulo' => 'required|string|max:180',
            'objetivo' => 'nullable|string|max:300',
            'descripcion' => 'nullable|string|max:1000',
            'entregable_esperado' => 'nullable|string|max:255',
            'fecha_inicio' => 'nullable|date',
            'fecha_fin' => 'nullable|date|after_or_equal:fecha_inicio',
            'horas_planificadas' => 'nullable|numeric|min:1|max:240',
        ];

        if ($practica->fecha_inicio && $practica->fecha_fin_prevista) {
            $rules['fecha_inicio'] .= '|after_or_equal:' . $practica->fecha_inicio->format('Y-m-d');
            $rules['fecha_fin'] .= '|before_or_equal:' . $practica->fecha_fin_prevista->format('Y-m-d');
        }

        $request->validate($rules, [
            'fecha_inicio.after_or_equal' => 'La fecha de inicio de la actividad no puede ser anterior al inicio de la práctica (' . ($practica->fecha_inicio?->format('d/m/Y') ?? '') . ').',
            'fecha_fin.before_or_equal' => 'La fecha final de la actividad no puede superar el límite del periodo de 3 meses (' . ($practica->fecha_fin_prevista?->format('d/m/Y') ?? '') . ').',
        ]);

        CronogramaActividad::create([
            'practica_id' => $practica->id,
            'titulo' => trim($request->titulo),
            'objetivo' => $request->objetivo,
            'descripcion' => $request->descripcion,
            'entregable_esperado' => $request->entregable_esperado,
            'fecha_inicio' => $request->fecha_inicio,
            'fecha_fin' => $request->fecha_fin,
            'horas_planificadas' => $request->horas_planificadas ?? 0,
            'estado' => 'pendiente',
            'aprobada_coordinacion' => $esCoord,
        ]);

        return back()->with('success', 'Actividad planificada agregada al cronograma de la práctica.');
    }

    public function updateActividad(Request $request, CronogramaActividad $actividad)
    {
        $user = Auth::user();
        $practica = $actividad->practica;
        $esCoord = $user->isCoordinador();
        $esTutorAsignado = $user->isTutor() && ($practica->tutor_id === $user->tutor?->id);

        if (!$esCoord && !$esTutorAsignado) {
            abort(403, 'Acción no autorizada.');
        }

        $rules = [
            'titulo' => 'required|string|max:180',
            'objetivo' => 'nullable|string|max:300',
            'descripcion' => 'nullable|string|max:1000',
            'entregable_esperado' => 'nullable|string|max:255',
            'fecha_inicio' => 'nullable|date',
            'fecha_fin' => 'nullable|date|after_or_equal:fecha_inicio',
            'horas_planificadas' => 'nullable|numeric|min:1|max:240',
            'observacion' => 'nullable|string|max:1000',
        ];

        if ($practica->fecha_inicio && $practica->fecha_fin_prevista) {
            $rules['fecha_inicio'] .= '|after_or_equal:' . $practica->fecha_inicio->format('Y-m-d');
            $rules['fecha_fin'] .= '|before_or_equal:' . $practica->fecha_fin_prevista->format('Y-m-d');
        }

        $request->validate($rules);

        // El estado NO se actualiza manualmente; es automático según entregas de informe y rúbricas
        $actividad->update([
            'titulo' => trim($request->titulo),
            'objetivo' => $request->objetivo,
            'descripcion' => $request->descripcion,
            'entregable_esperado' => $request->entregable_esperado ?? $actividad->entregable_esperado,
            'fecha_inicio' => $request->fecha_inicio,
            'fecha_fin' => $request->fecha_fin,
            'horas_planificadas' => $request->horas_planificadas ?? $actividad->horas_planificadas,
            'observacion' => $request->observacion ?? $actividad->observacion,
        ]);

        return back()->with('success', 'Actividad del cronograma actualizada correctamente.');
    }

    public function aprobarCronograma(Request $request, Practica $practica)
    {
        $user = Auth::user();
        if (!$user->isCoordinador()) {
            abort(403, 'Acción no autorizada. La aprobación formal del cronograma es competencia exclusiva de la Coordinación Académica.');
        }

        if ($practica->actividades()->count() === 0) {
            return back()->withErrors(['cronograma' => 'No se puede aprobar el cronograma: No existen actividades planificadas para esta práctica.']);
        }

        $coord = Coordinador::where('usuario_id', $user->id)->first();

        DB::transaction(function () use ($practica, $coord) {
            $practica->update([
                'cronograma_aprobado' => true,
                'cronograma_aprobado_at' => now(),
                'cronograma_aprobado_por' => $coord?->id,
            ]);

            CronogramaActividad::where('practica_id', $practica->id)->update([
                'aprobada_coordinacion' => true,
            ]);
        });

        return back()->with('success', 'Cronograma de actividades formalmente aprobado por la Coordinación Académica. El registro de horas queda habilitado.');
    }

    public function storeJustificacion(Request $request, Practica $practica)
    {
        $user = Auth::user();
        $esEstudiante = $user->isEstudiante() && ($user->estudiante?->id === $practica->solicitud->estudiante_id);
        $esCoord = $user->isCoordinador();

        if (!$esEstudiante && !$esCoord) {
            abort(403, 'Acceso denegado. No tienes autorización para registrar justificaciones en esta práctica.');
        }

        $request->validate([
            'fecha' => 'required|date',
            'tipo' => 'required|in:falta,incompleta',
            'horas_reales' => 'nullable|numeric|between:0,3.5',
            'motivo' => 'required|string|min:5|max:1000',
            'documento' => 'nullable|file|mimes:pdf,jpg,png,doc,docx|max:10240',
        ], [
            'fecha.required' => 'La fecha de la jornada es obligatoria.',
            'tipo.required' => 'Indica si es falta (0 hrs) o jornada incompleta (1 a 3.5 hrs).',
            'motivo.required' => 'Ingresa el motivo o justificación de la ausencia o jornada incompleta.',
            'motivo.min' => 'El motivo debe contener al menos 5 caracteres.',
        ]);

        $path = null;
        if ($request->hasFile('documento')) {
            $path = $request->file('documento')->store("practicas/{$practica->id}/justificaciones", 'local');
        }

        JustificacionAsistencia::create([
            'practica_id' => $practica->id,
            'estudiante_id' => $practica->solicitud->estudiante_id,
            'fecha' => $request->fecha,
            'tipo' => $request->tipo,
            'horas_reales' => $request->tipo === 'falta' ? 0.00 : ($request->horas_reales ?? 0.00),
            'motivo' => trim($request->motivo),
            'ruta_documento' => $path,
            'estado' => 'pendiente',
        ]);

        return back()->with('success', 'Justificación registrada exitosamente. Queda pendiente de revisión y resolución por Coordinación.');
    }

    public function resolverJustificacion(Request $request, JustificacionAsistencia $justificacion)
    {
        $user = Auth::user();
        if (!$user->isCoordinador()) {
            abort(403, 'Acción no autorizada. La resolución de justificaciones corresponde exclusivamente a la Coordinación Académica.');
        }

        $request->validate([
            'estado' => 'required|in:aprobada,rechazada',
            'observacion_resolucion' => 'required|string|min:3|max:500',
        ], [
            'observacion_resolucion.required' => 'Ingresa la observación o resolución institucional de la justificación.',
        ]);

        $coord = Coordinador::where('usuario_id', $user->id)->first();
        $practica = $justificacion->practica;

        DB::transaction(function () use ($justificacion, $practica, $request, $coord, $user) {
            $justificacion->update([
                'estado' => $request->estado,
                'observacion_resolucion' => trim($request->observacion_resolucion),
                'revisada_por_coordinador_id' => $coord?->id,
                'fecha_resolucion' => now(),
            ]);

            if ($request->estado === 'aprobada') {
                $maxFaltas = (int) config('practicas.max_faltas_justificadas', 2);
                $maxIncompletas = (int) config('practicas.max_incompletas_justificadas', 2);

                if ($justificacion->tipo === 'falta') {
                    $practica->increment('faltas_justificadas');
                    $practica->refresh();
                    if ($practica->faltas_justificadas > $maxFaltas) {
                        IncidenciaPractica::create([
                            'practica_id' => $practica->id,
                            'tipo' => 'tercera_falta',
                            'descripcion' => "Se ha superado el límite máximo de {$maxFaltas} faltas justificadas autorizadas (Total registradas: {$practica->faltas_justificadas}). Genera bloqueo de conclusión hasta resolución formal.",
                            'bloquea_cierre' => true,
                            'resuelta' => false,
                            'registrada_por_usuario_id' => $user->id,
                        ]);
                    }
                } elseif ($justificacion->tipo === 'incompleta') {
                    $practica->increment('incompletas_justificadas');
                    $practica->refresh();
                    if ($practica->incompletas_justificadas > $maxIncompletas) {
                        IncidenciaPractica::create([
                            'practica_id' => $practica->id,
                            'tipo' => 'tercera_incompleta',
                            'descripcion' => "Se ha superado el límite máximo de {$maxIncompletas} jornadas incompletas autorizadas (Total registradas: {$practica->incompletas_justificadas}). Genera bloqueo de conclusión hasta resolución formal.",
                            'bloquea_cierre' => true,
                            'resuelta' => false,
                            'registrada_por_usuario_id' => $user->id,
                        ]);
                    }
                }
            }
        });

        return back()->with('success', "Justificación marcada como: {$request->estado}.");
    }

    public function storeIncidencia(Request $request, Practica $practica)
    {
        $user = Auth::user();
        if (!$user->isCoordinador() && !$user->isTutor()) {
            abort(403, 'Acción no autorizada.');
        }

        $request->validate([
            'tipo' => 'required|string|max:60',
            'descripcion' => 'required|string|min:5|max:1000',
            'bloquea_cierre' => 'nullable|boolean',
        ]);

        IncidenciaPractica::create([
            'practica_id' => $practica->id,
            'tipo' => $request->tipo,
            'descripcion' => trim($request->descripcion),
            'bloquea_cierre' => $request->boolean('bloquea_cierre', true),
            'resuelta' => false,
            'registrada_por_usuario_id' => $user->id,
        ]);

        return back()->with('success', 'Incidencia registrada en el expediente de la práctica.');
    }

    public function resolverIncidencia(Request $request, IncidenciaPractica $incidencia)
    {
        $user = Auth::user();
        if (!$user->isCoordinador()) {
            abort(403, 'Acción no autorizada. Solo la Coordinación Académica puede resolver incidencias.');
        }

        $request->validate([
            'resolucion' => 'required|string|min:5|max:1000',
        ]);

        $incidencia->update([
            'resuelta' => true,
            'resolucion' => trim($request->resolucion),
        ]);

        return back()->with('success', 'Incidencia marcada como resuelta.');
    }

    public function storeRubrica(Request $request, CronogramaActividad $actividad)
    {
        $user = Auth::user();
        $practica = $actividad->practica;

        if (!$user->isTutor() || ($practica->tutor_id !== $user->tutor?->id)) {
            abort(403, 'Acción no autorizada. Solo el docente tutor asignado puede definir rúbricas para la actividad.');
        }

        $request->validate([
            'titulo' => 'required|string|max:200',
            'descripcion' => 'nullable|string|max:1000',
            'criterios' => 'required|array|min:1',
            'criterios.*.nombre' => 'required|string|max:200',
            'criterios.*.ponderacion' => 'required|numeric|min:1|max:100',
        ]);

        // Verificar que la suma de ponderaciones sea exactamente 100%
        $sumaPonderacion = array_sum(array_column($request->criterios, 'ponderacion'));
        if (abs($sumaPonderacion - 100.0) > 0.01) {
            return back()->withErrors(['criterios' => "La suma de las ponderaciones de los criterios debe ser exactamente 100% (Suma actual: {$sumaPonderacion}%)."])->withInput();
        }

        DB::transaction(function () use ($actividad, $request) {
            $rubrica = RubricaActividad::updateOrCreate(
                ['actividad_id' => $actividad->id],
                [
                    'titulo' => trim($request->titulo),
                    'descripcion' => $request->descripcion,
                ]
            );

            // Recrear criterios
            $rubrica->criterios()->delete();
            foreach ($request->criterios as $crit) {
                CriterioRubrica::create([
                    'rubrica_id' => $rubrica->id,
                    'nombre' => trim($crit['nombre']),
                    'descripcion' => $crit['descripcion'] ?? null,
                    'ponderacion' => (float) $crit['ponderacion'],
                    'puntuacion_maxima' => 100.00,
                ]);
            }

            $actividad->update(['rubrica_definida' => true]);
        });

        return back()->with('success', 'Rúbrica de evaluación configurada exitosamente con ponderación al 100%.');
    }

    public function evaluarRubrica(Request $request, RubricaActividad $rubrica)
    {
        $user = Auth::user();
        $actividad = $rubrica->actividad;
        $practica = $actividad->practica;

        if (!$user->isTutor() || ($practica->tutor_id !== $user->tutor?->id)) {
            abort(403, 'Acción no autorizada. Solo el docente tutor asignado puede evaluar rúbricas.');
        }

        $request->validate([
            'puntuaciones' => 'required|array',
            'puntuaciones.*' => 'required|numeric|between:0,100',
            'informe_avance_id' => 'nullable|exists:informes_avance,id',
            'comentarios' => 'nullable|array',
        ], [
            'puntuaciones.required' => 'Debes calificar todos los criterios de la rúbrica.',
            'puntuaciones.*.required' => 'La calificación de cada criterio es obligatoria.',
            'puntuaciones.*.numeric' => 'La calificación debe ser un valor numérico.',
            'puntuaciones.*.between' => 'La calificación de cada criterio debe estar comprendida estrictamente entre 0 y 100 puntos.',
        ]);

        $tutor = $user->tutor;
        $minAprobacion = (float) config('practicas.nota_minima_aprobacion', 51.0);

        DB::transaction(function () use ($rubrica, $actividad, $practica, $request, $tutor, $minAprobacion) {
            $criterios = $rubrica->criterios;
            $notaTotal = 0;

            foreach ($criterios as $criterio) {
                $puntos = (float) ($request->puntuaciones[$criterio->id] ?? 0);
                $comentario = $request->comentarios[$criterio->id] ?? null;

                EvaluacionCriterio::create([
                    'criterio_id' => $criterio->id,
                    'informe_avance_id' => $request->informe_avance_id,
                    'practica_id' => $practica->id,
                    'tutor_id' => $tutor->id,
                    'puntuacion' => $puntos,
                    'comentario' => $comentario,
                ]);

                $notaTotal += ($puntos * ($criterio->ponderacion / 100.0));
            }

            $notaTotal = round($notaTotal, 2);
            $aprobado = ($notaTotal >= $minAprobacion);

            $actividad->update([
                'estado' => $aprobado ? 'completada' : 'en_proceso',
                'fecha_completada' => $aprobado ? now()->toDateString() : null,
                'observacion' => "Evaluada mediante rúbrica. Nota obtenida: {$notaTotal}/100 (" . ($aprobado ? 'Aprobado' : 'Reprobado') . ").",
            ]);

            // Sincronizar informe de avance vinculado o informe de la actividad
            $informe = null;
            if ($request->filled('informe_avance_id')) {
                $informe = InformeAvance::find($request->informe_avance_id);
            }
            if (!$informe) {
                $informe = InformeAvance::where('actividad_id', $actividad->id)->where('practica_id', $practica->id)->first();
            }

            if ($informe) {
                $informe->update([
                    'estado' => 'revisado',
                    'calificacion' => $notaTotal,
                    'revisado_por_tutor_id' => $tutor->id,
                    'fecha_revision' => now(),
                    'observaciones_tutor' => "Calificación obtenida por rúbrica técnica: {$notaTotal}/100 (" . ($aprobado ? 'Aprobado' : 'Reprobado') . ").",
                ]);
            }

            // Sincronizar automáticamente en Evaluaciones de Seguimiento (Parciales)
            EvaluacionSeguimiento::updateOrCreate(
                [
                    'practica_id' => $practica->id,
                    'periodo' => "Evaluación Parcial: {$actividad->titulo}",
                ],
                [
                    'tutor_id' => $tutor->id,
                    'fecha' => now()->toDateString(),
                    'calificacion' => $notaTotal,
                    'resultado' => $aprobado ? 'Aprobado' : 'Reprobado',
                    'comentario' => "Calificación obtenida por rúbrica técnica de actividad ({$notaTotal}/100 - " . ($aprobado ? 'Aprobado' : 'Reprobado') . ").",
                ]
            );

            // Nota final calculada automáticamente si todas las actividades han sido evaluadas
            $todasActividades = $practica->actividades()->with('rubrica.criterios.evaluaciones')->get();
            $sumaNotas = 0;
            $todasCompletas = true;
            foreach ($todasActividades as $act) {
                $notaAct = $act->calcularNota();
                if ($notaAct === null) {
                    $todasCompletas = false;
                    break;
                }
                $sumaNotas += $notaAct;
            }

            if ($todasCompletas && $todasActividades->isNotEmpty()) {
                $notaFinalCalc = round($sumaNotas / $todasActividades->count(), 2);
                $resultadoFinal = ($notaFinalCalc >= $minAprobacion) ? 'Aprobado' : 'Reprobado';

                EvaluacionFinal::updateOrCreate(
                    ['practica_id' => $practica->id],
                    [
                        'tutor_id' => $tutor->id,
                        'fecha' => now()->toDateString(),
                        'calificacion' => $notaFinalCalc,
                        'resultado' => $resultadoFinal,
                        'comentario' => "Calificación calculada automáticamente por rúbricas de cronograma ({$notaFinalCalc}/100 - {$resultadoFinal}).",
                    ]
                );
            }
        });

        return back()->with('success', 'Evaluación por rúbrica registrada. Calificación de la actividad calculada automáticamente.');
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

        // RF-14 y Cierre Institucional Automatizado: Verificaciones estrictas antes de declarar CONCLUIDA una práctica
        if ($nuevoEstado === 'concluida') {
            $horasValidadas = (float) $practica->horas_validadas;
            $horasMeta = (float) $practica->horas_objetivo;
            $pctReal = $horasMeta > 0 ? round(($horasValidadas / $horasMeta) * 100, 1) : 0;
            $minPct = $practica->esHistorica() ? 100.0 : (float) config('practicas.porcentaje_minimo_aprobacion_horas', 90.0);
            $maxFaltas = (int) config('practicas.max_faltas_justificadas', 2);
            $maxIncompletas = (int) config('practicas.max_incompletas_justificadas', 2);

            // 1. Cumplimiento del porcentaje mínimo de horas validadas (90% en v2, 100% en históricas)
            if ($pctReal < $minPct) {
                return back()->withErrors([
                    'nuevo_estado' => "No se puede concluir la práctica: Horas validadas insuficientes ({$horasValidadas} de {$horasMeta} hrs requeridas, {$pctReal}% alcanzado. Se requiere al menos {$minPct}%)."
                ])->withInput();
            }

            // 2. Control de excepciones: Límite máximo de 2 faltas justificadas
            if ($practica->faltas_justificadas > $maxFaltas) {
                return back()->withErrors([
                    'nuevo_estado' => "No se puede concluir la práctica: Ha superado el límite máximo de {$maxFaltas} faltas justificadas permitidas (Total: {$practica->faltas_justificadas})."
                ])->withInput();
            }

            // 3. Control de excepciones: Límite máximo de 2 jornadas incompletas justificadas
            if ($practica->incompletas_justificadas > $maxIncompletas) {
                return back()->withErrors([
                    'nuevo_estado' => "No se puede concluir la práctica: Ha superado el límite máximo de {$maxIncompletas} jornadas incompletas permitidas (Total: {$practica->incompletas_justificadas})."
                ])->withInput();
            }

            // 4. Comprobar que no existan incidencias graves impeditivas pendientes
            $tieneIncidencias = IncidenciaPractica::where('practica_id', $practica->id)
                ->where('bloquea_cierre', true)
                ->where('resuelta', false)
                ->exists();
            if ($tieneIncidencias) {
                return back()->withErrors([
                    'nuevo_estado' => 'No se puede concluir la práctica: Existen incidencias graves pendientes de resolución por Coordinación Académica.'
                ])->withInput();
            }

            // 5. Existencia de evaluación final emitida por el docente tutor
            if (!$practica->evaluacionFinal) {
                return back()->withErrors([
                    'nuevo_estado' => 'No se puede concluir la práctica: Es obligatorio que el docente tutor haya registrado la evaluación final.'
                ])->withInput();
            }

            if ($practica->evaluacionFinal->resultado === 'Reprobado') {
                return back()->withErrors([
                    'nuevo_estado' => 'No se puede concluir como aprobada una práctica cuya evaluación final es reprobatoria.'
                ])->withInput();
            }

            // 6. Informes obligatorios de avance revisados y aprobados
            $informesRevisados = $practica->informes()->where('estado', 'revisado')->count();
            if ($informesRevisados === 0) {
                return back()->withErrors([
                    'nuevo_estado' => 'No se puede concluir la práctica: Es obligatorio contar con al menos un informe de avance revisado y aprobado por el tutor.'
                ])->withInput();
            }

            // 7. Valoración empresarial (obligatoria en prácticas del nuevo reglamento)
            if (!$practica->esHistorica() && !$practica->evaluacionOrganizacion) {
                return back()->withErrors([
                    'nuevo_estado' => 'No se puede concluir la práctica: Es obligatorio haber registrado la valoración de la organización receptora.'
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

        $tieneActividades = $practica->actividades()->count() > 0;

        $request->validate([
            'actividad_id' => $tieneActividades ? 'required|exists:cronograma_actividades,id' : 'nullable|exists:cronograma_actividades,id',
            'titulo' => 'required|string|min:3|max:200',
            'periodo_inicio' => 'required|date',
            'periodo_fin' => 'required|date|after_or_equal:periodo_inicio',
            'resumen' => 'required|string|min:10|max:2000',
            'archivo' => 'required|file|mimes:pdf,doc,docx|max:10240',
        ], [
            'actividad_id.required' => 'Es obligatorio vincular el informe a una actividad planificada del cronograma.',
            'titulo.required' => 'Ingresa el título del informe de avance.',
            'periodo_fin.after_or_equal' => 'La fecha fin no puede ser anterior a la fecha de inicio del periodo reportado.',
            'resumen.required' => 'Describe el resumen de actividades del periodo reportado.',
            'resumen.min' => 'El resumen debe contener al menos 10 caracteres.',
            'archivo.required' => 'Selecciona un documento para el informe.',
            'archivo.mimes' => 'Formato no permitido. Para informes académicos, adjunta únicamente archivos PDF o Word (.pdf, .doc, .docx).',
            'archivo.max' => 'El archivo supera el tamaño máximo permitido de 10 MB.',
        ]);

        $actividadId = $request->actividad_id;
        $version = 1;
        $act = null;

        if ($actividadId) {
            $act = CronogramaActividad::where('practica_id', $practica->id)->find($actividadId);
            if (!$act) {
                return back()->withErrors(['actividad_id' => 'La actividad seleccionada no pertenece a esta práctica.'])->withInput();
            }

            $ultimo = InformeAvance::where('practica_id', $practica->id)
                ->where('actividad_id', $actividadId)
                ->orderBy('version', 'desc')
                ->first();

            // Control estricto de fechas de entrega según Cronograma
            $hoy = now()->toDateString();
            if ($act->fecha_fin && $hoy > $act->fecha_fin->format('Y-m-d')) {
                // Si la fecha límite ya venció y NO es una corrección autorizada (informe observado previo)
                if (!$ultimo || $ultimo->estado !== 'observado') {
                    return back()->withErrors([
                        'actividad_id' => "El plazo de entrega para la actividad '{$act->titulo}' venció el {$act->fecha_fin->format('d/m/Y')}. No se admiten entregas extemporáneas fuera del cronograma."
                    ])->withInput();
                }
            }

            if ($ultimo) {
                if (in_array($ultimo->estado, ['enviado', 'revisado'])) {
                    return back()->withErrors([
                        'actividad_id' => "La actividad '{$act->titulo}' ya cuenta con un informe presentado en estado '{$ultimo->estado}'. Solo se permite una nueva entrega si el docente tutor emite una observación previa."
                    ])->withInput();
                }
                $version = $ultimo->version + 1;
            }
        } else {
            // Evitar informes duplicados para el mismo periodo y título si no tiene actividad
            $duplicado = InformeAvance::where('practica_id', $practica->id)
                ->whereNull('actividad_id')
                ->where('titulo', trim($request->titulo))
                ->where('periodo_inicio', $request->periodo_inicio)
                ->exists();

            if ($duplicado) {
                return back()->withErrors(['titulo' => 'Ya existe un informe de avance registrado con este título para el mismo periodo.'])->withInput();
            }
        }

        $path = $request->file('archivo')->store("practicas/{$practica->id}/informes", 'local');

        $nuevoInforme = InformeAvance::create([
            'practica_id' => $practica->id,
            'actividad_id' => $actividadId,
            'version' => $version,
            'titulo' => trim($request->titulo) . ($version > 1 ? " (v{$version})" : ""),
            'periodo_inicio' => $request->periodo_inicio,
            'periodo_fin' => $request->periodo_fin,
            'resumen' => trim($request->resumen),
            'ruta_privada' => $path,
            'estado' => 'enviado',
            'fecha_envio' => now(),
        ]);

        // Si la actividad estaba pendiente, cambia automáticamente a en_proceso al recibir informe
        if ($act && $act->estado === 'pendiente') {
            $act->update(['estado' => 'en_proceso']);
        }

        return back()->with('success', "Informe de avance presentado correctamente (Versión {$version}). Tu docente tutor lo revisará.");
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

        $extension = pathinfo($informe->ruta_privada, PATHINFO_EXTENSION) ?: 'pdf';
        $nombreLimpio = \Illuminate\Support\Str::slug("informe-{$informe->id}-" . substr($informe->titulo, 0, 50)) . '.' . $extension;
        return Storage::disk('local')->download($informe->ruta_privada, $nombreLimpio);
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
            'observaciones_tutor' => $request->observacion,
        ]);

        if ($request->estado === 'observado' && $informe->actividad) {
            $informe->actividad->update([
                'observacion' => "Informe con observaciones del tutor: " . \Illuminate\Support\Str::limit($request->observacion, 150),
            ]);
        }

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

        // RF-13: Solo el docente tutor asignado puede calificar parciales
        if (!$user->isTutor()) {
            abort(403, 'Acción no autorizada. Las evaluaciones parciales solo pueden ser registradas por el docente tutor asignado.');
        }

        if (!$practica->tutor_id || $practica->tutor_id !== $user->tutor?->id) {
            abort(403, 'No tienes autorización para registrar evaluaciones en una práctica que no supervisas.');
        }

        if (in_array($practica->estado, ['concluida', 'cancelada', 'abandonada'])) {
            return back()->withErrors(['evaluacion' => "No es posible registrar evaluaciones parciales en una práctica con estado '{$practica->estado}'."]);
        }

        $minAprobacion = (float) config('practicas.nota_minima_aprobacion', 51.0);

        // Para prácticas v2/nuevas, la evaluación DEBE ser mediante la rúbrica técnica de una actividad
        if (!$practica->esHistorica() && (!$request->filled('rubrica_id') || !$request->has('puntuaciones'))) {
            return back()->withErrors([
                'evaluacion' => 'La evaluación parcial debe realizarse obligatoriamente mediante los criterios de la rúbrica técnica de la actividad seleccionada.'
            ])->withInput();
        }

        // Si se evalúa por rúbrica de actividad seleccionada
        if ($request->filled('rubrica_id') && $request->has('puntuaciones')) {
            $request->validate([
                'puntuaciones' => 'required|array',
                'puntuaciones.*' => 'required|numeric|between:0,100',
            ], [
                'puntuaciones.required' => 'Debes calificar todos los criterios de la rúbrica.',
                'puntuaciones.*.required' => 'La calificación de cada criterio es obligatoria.',
                'puntuaciones.*.numeric' => 'La calificación debe ser un valor numérico.',
                'puntuaciones.*.between' => 'La calificación de cada criterio debe estar comprendida estrictamente entre 0 y 100 puntos.',
            ]);

            $rubrica = RubricaActividad::with('criterios', 'actividad')->findOrFail($request->rubrica_id);
            $actividad = $rubrica->actividad;

            $notaTotal = 0;
            foreach ($rubrica->criterios as $criterio) {
                $puntos = (float) ($request->puntuaciones[$criterio->id] ?? 0);
                $comentario = $request->comentarios[$criterio->id] ?? null;

                EvaluacionCriterio::create([
                    'criterio_id' => $criterio->id,
                    'informe_avance_id' => $request->informe_avance_id,
                    'practica_id' => $practica->id,
                    'tutor_id' => $user->tutor->id,
                    'puntuacion' => $puntos,
                    'comentario' => $comentario,
                ]);

                $notaTotal += ($puntos * ($criterio->ponderacion / 100.0));
            }

            $calificacion = round($notaTotal, 2);
            $resultado = ($calificacion >= $minAprobacion) ? 'Aprobado' : 'Reprobado';

            $actividad->update([
                'estado' => ($resultado === 'Aprobado') ? 'completada' : 'en_proceso',
                'fecha_completada' => ($resultado === 'Aprobado') ? now()->toDateString() : null,
                'observacion' => "Evaluada mediante rúbrica. Nota obtenida: {$calificacion}/100 ({$resultado}).",
            ]);

            if ($request->filled('informe_avance_id')) {
                $informe = InformeAvance::find($request->informe_avance_id);
                if ($informe) {
                    $informe->update([
                        'estado' => 'revisado',
                        'calificacion' => $calificacion,
                        'revisado_por_tutor_id' => $user->tutor->id,
                        'fecha_revision' => now(),
                        'observaciones_tutor' => "Calificación técnica por rúbrica: {$calificacion}/100 ({$resultado}).",
                    ]);
                }
            }

            // Automatización: Si todas las actividades tienen nota, calcular y actualizar automáticamente la Evaluación Final
            $todasActividades = $practica->actividades()->with('rubrica.criterios.evaluaciones')->get();
            $sumaNotas = 0;
            $todasCompletas = true;
            foreach ($todasActividades as $a) {
                $notaAct = $a->calcularNota();
                if ($notaAct === null) {
                    $todasCompletas = false;
                    break;
                }
                $sumaNotas += $notaAct;
            }

            if ($todasCompletas && $todasActividades->isNotEmpty()) {
                $notaFinalCalc = round($sumaNotas / $todasActividades->count(), 2);
                $resultadoFinal = ($notaFinalCalc >= $minAprobacion) ? 'Aprobado' : 'Reprobado';

                EvaluacionFinal::updateOrCreate(
                    ['practica_id' => $practica->id],
                    [
                        'tutor_id' => $user->tutor->id,
                        'fecha' => now()->toDateString(),
                        'calificacion' => $notaFinalCalc,
                        'resultado' => $resultadoFinal,
                        'comentario' => "Promedio acumulado de rúbricas ({$notaFinalCalc}/100 - {$resultadoFinal}) calculado automáticamente tras calificar '{$actividad->titulo}'.",
                    ]
                );
            }

            $periodo = $request->periodo ?: "Evaluación Rúbrica: {$actividad->titulo}";
            $comentarioFinal = $request->comentario ?: "Evaluación por rúbrica técnica de actividad '{$actividad->titulo}'.";
        } else {
            // Compatibilidad histórica v1
            $request->validate([
                'periodo' => 'required|string|max:100',
                'fecha' => 'required|date',
                'calificacion' => 'required|numeric|between:0,100',
                'comentario' => 'nullable|string|max:1000',
            ], [
                'periodo.required' => 'El período o título de evaluación es obligatorio.',
                'fecha.required' => 'La fecha de la evaluación es obligatoria.',
                'calificacion.required' => 'La calificación es obligatoria.',
                'calificacion.numeric' => 'La calificación debe ser un valor numérico.',
                'calificacion.between' => 'La calificación debe encontrarse entre 0 y 100 puntos.',
            ]);

            $calificacion = (float) $request->calificacion;
            $resultado = ($calificacion >= $minAprobacion) ? 'Aprobado' : 'Reprobado';
            $periodo = $request->periodo;
            $comentarioFinal = $request->comentario;
        }

        EvaluacionSeguimiento::create([
            'practica_id' => $practica->id,
            'tutor_id' => $user->tutor->id,
            'fecha' => $request->fecha ?: now()->toDateString(),
            'periodo' => $periodo,
            'resultado' => $resultado,
            'calificacion' => $calificacion,
            'comentario' => $comentarioFinal,
        ]);

        return back()->with('success', "Evaluación de seguimiento registrada exitosamente. Nota calculada: {$calificacion}/100 ({$resultado}).");
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
            'comentario' => 'required|string|min:5|max:1000',
        ], [
            'comentario.required' => 'Ingresa el dictamen u opinión evaluativa final sobre el desempeño del estudiante.',
            'comentario.min' => 'El dictamen debe contener al menos 5 caracteres.',
        ]);

        // Cálculo automatizado del promedio de las actividades evaluadas por rúbricas
        $actividades = $practica->actividades()->with('rubrica.criterios.evaluaciones')->get();
        $notas = [];
        foreach ($actividades as $act) {
            $n = $act->calcularNota();
            if ($n !== null) {
                $notas[] = $n;
            }
        }

        $minAprobacion = (float) config('practicas.nota_minima_aprobacion', 51.0);

        if (count($notas) > 0) {
            $calificacionFinal = round(array_sum($notas) / count($notas), 2);
        } elseif ($request->filled('calificacion')) {
            $calificacionFinal = (float) $request->calificacion;
        } else {
            $calificacionFinal = 0.00;
        }

        $resultadoFinal = ($calificacionFinal >= $minAprobacion) ? 'Aprobado' : 'Reprobado';
        $tutor = $practica->tutor ?? $user->tutor ?? DocenteTutor::first();

        // RF-13 CORRECCIÓN FUNDAMENTAL: Guardar la evaluación final NO concluye la práctica.
        // La evaluación final es potestad del tutor; el cierre formal corresponde a Coordinación.
        EvaluacionFinal::updateOrCreate(
            ['practica_id' => $practica->id],
            [
                'tutor_id' => $tutor?->id ?? 1,
                'fecha' => now()->toDateString(),
                'calificacion' => $calificacionFinal,
                'resultado' => $resultadoFinal,
                'comentario' => trim($request->comentario),
            ]
        );

        return back()->with('success', "Evaluación final guardada exitosamente con nota calculada de {$calificacionFinal}/100 ({$resultadoFinal}). La práctica permanece en curso hasta el cierre formal por Coordinación Académica.");
    }
}

