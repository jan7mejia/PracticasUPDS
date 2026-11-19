<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\SolicitudPractica;
use App\Models\OrganizacionReceptora;
use App\Models\PeriodoAcademico;
use App\Models\DocumentoSolicitud;
use App\Models\TipoDocumento;
use App\Models\HistorialEstadoSolicitud;
use App\Models\Practica;
use App\Models\Coordinador;

class SolicitudController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $tab = $request->get('tab', 'solicitudes');
        $perPage = in_array((int)$request->get('per_page'), [10, 25, 50]) ? (int)$request->get('per_page') : 10;

        if ($user->isEstudiante()) {
            $estudiante = $user->estudiante;
            $solicitudes = SolicitudPractica::where('estudiante_id', $estudiante?->id)
                ->with(['organizacion', 'periodo', 'documentos.tipoDocumento'])
                ->orderBy('created_at', 'desc')
                ->orderBy('id', 'desc')
                ->paginate($perPage, ['*'], 'sol_page')
                ->withQueryString();

            $documentos = DocumentoSolicitud::whereHas('solicitud', function ($q) use ($estudiante) {
                $q->where('estudiante_id', $estudiante?->id);
            })->with(['tipoDocumento', 'solicitud.organizacion'])
              ->orderBy('created_at', 'desc')
              ->orderBy('id', 'desc')
              ->paginate($perPage, ['*'], 'doc_page')
              ->withQueryString();
        } elseif ($user->isTutor()) {
            // Tutor: solo consulta las solicitudes de los estudiantes que tiene asignados en prácticas
            $tutor = $user->tutor;
            $query = SolicitudPractica::whereHas('practica', function ($q) use ($tutor) {
                $q->where('tutor_id', $tutor?->id);
            })->with(['estudiante.carrera', 'organizacion', 'periodo', 'documentos.tipoDocumento']);

            if ($request->filled('estado')) {
                $query->where('estado', $request->estado);
            }
            if ($request->filled('buscar')) {
                $search = $request->buscar;
                $query->whereHas('estudiante', function ($q) use ($search) {
                    $q->where('nombres', 'like', "%{$search}%")
                      ->orWhere('apellidos', 'like', "%{$search}%")
                      ->orWhere('ru', 'like', "%{$search}%");
                });
            }
            $solicitudes = $query->orderBy('created_at', 'desc')->orderBy('id', 'desc')->paginate($perPage, ['*'], 'sol_page')->withQueryString();

            $documentos = DocumentoSolicitud::whereHas('solicitud.practica', function ($q) use ($tutor) {
                $q->where('tutor_id', $tutor?->id);
            })->with(['tipoDocumento', 'solicitud.estudiante', 'solicitud.organizacion'])
              ->orderBy('created_at', 'desc')
              ->orderBy('id', 'desc')
              ->paginate($perPage, ['*'], 'doc_page')
              ->withQueryString();
        } else {
            // Coordinador / Admin
            $query = SolicitudPractica::with(['estudiante.carrera', 'organizacion', 'periodo', 'documentos.tipoDocumento']);
            if ($request->filled('estado')) {
                $query->where('estado', $request->estado);
            }
            if ($request->filled('carrera_id')) {
                $query->whereHas('estudiante', fn($q) => $q->where('carrera_id', $request->carrera_id));
            }
            if ($request->filled('organizacion_id')) {
                $query->where('organizacion_id', $request->organizacion_id);
            }
            if ($request->filled('buscar')) {
                $search = $request->buscar;
                $query->whereHas('estudiante', function ($q) use ($search) {
                    $q->where('nombres', 'like', "%{$search}%")
                      ->orWhere('apellidos', 'like', "%{$search}%")
                      ->orWhere('ru', 'like', "%{$search}%");
                });
            }
            $solicitudes = $query->orderBy('created_at', 'desc')->orderBy('id', 'desc')->paginate($perPage, ['*'], 'sol_page')->withQueryString();

            $docQuery = DocumentoSolicitud::with(['tipoDocumento', 'solicitud.estudiante', 'solicitud.organizacion']);
            if ($request->filled('estado_doc')) {
                $docQuery->where('estado', $request->estado_doc);
            }
            $documentos = $docQuery->orderBy('created_at', 'desc')->orderBy('id', 'desc')->paginate($perPage, ['*'], 'doc_page')->withQueryString();
        }

        $tiposDocumento = TipoDocumento::where('activo', true)->get();

        return view('solicitudes.index', compact('solicitudes', 'documentos', 'tab', 'tiposDocumento', 'perPage'));
    }

    public function create(Request $request)
    {
        $user = Auth::user();
        if (!$user->isEstudiante()) {
            abort(403, 'Acción no autorizada. Solo los estudiantes pueden iniciar nuevas solicitudes de práctica.');
        }

        // Flujo de postulación: comprobar si llega organizacion_id preseleccionada
        $organizacionId = $request->query('organizacion_id') ?? old('organizacion_id');
        $organizacionPreseleccionada = null;

        if ($organizacionId) {
            $organizacionPreseleccionada = OrganizacionReceptora::with('responsables')
                ->where('id', $organizacionId)
                ->where('estado', 'activa')
                ->first();
        }

        $organizaciones = OrganizacionReceptora::where('estado', 'activa')->orderBy('nombre')->get();
        $periodos = PeriodoAcademico::where('activo', true)->get();

        return view('solicitudes.create', compact('organizaciones', 'periodos', 'organizacionPreseleccionada'));
    }

    public function store(Request $request)
    {
        $user = Auth::user();
        if (!$user->isEstudiante()) {
            abort(403, 'Acción no autorizada. Solo los estudiantes pueden registrar solicitudes de práctica.');
        }

        $estudiante = $user->estudiante;

        if (!$estudiante) {
            return back()->withErrors(['error' => 'No tienes un perfil de estudiante asociado.']);
        }

        $request->validate([
            'organizacion_id' => 'required|exists:organizaciones_receptoras,id',
            'periodo_id' => 'required|exists:periodos_academicos,id',
            'observacion' => 'nullable|string|max:500',
        ]);

        // Regla: No permitir solicitudes a organizaciones inactivas
        $org = OrganizacionReceptora::findOrFail($request->organizacion_id);
        if ($org->estado !== 'activa') {
            return back()->withErrors(['organizacion_id' => 'La organización seleccionada se encuentra inactiva y no puede recibir nuevas solicitudes.'])->withInput();
        }

        // Regla: Evitar solicitudes duplicadas pendientes para la misma organización en el mismo período
        $solicitudDuplicada = SolicitudPractica::where('estudiante_id', $estudiante->id)
            ->where('organizacion_id', $request->organizacion_id)
            ->where('periodo_id', $request->periodo_id)
            ->whereIn('estado', ['presentada', 'en_revision', 'pendiente'])
            ->first();

        if ($solicitudDuplicada) {
            return back()->withErrors(['organizacion_id' => 'Ya tienes una solicitud en trámite para esta organización en el período académico seleccionado.'])->withInput();
        }

        $solicitud = DB::transaction(function () use ($request, $estudiante, $user) {
            $sol = SolicitudPractica::create([
                'estudiante_id' => $estudiante->id,
                'organizacion_id' => $request->organizacion_id,
                'periodo_id' => $request->periodo_id,
                'fecha_solicitud' => now(),
                'estado' => 'presentada',
                'observacion' => $request->observacion,
            ]);

            HistorialEstadoSolicitud::create([
                'solicitud_id' => $sol->id,
                'estado_anterior' => 'borrador',
                'estado_nuevo' => 'presentada',
                'motivo' => 'Presentación inicial de solicitud por el estudiante.',
                'cambiado_por' => $user->id,
                'fecha_cambio' => now(),
            ]);

            return $sol;
        });

        return redirect()->route('solicitudes.show', $solicitud->id)
            ->with('success', 'Solicitud registrada correctamente. Ahora puedes adjuntar tus documentos.');
    }

    public function show(SolicitudPractica $solicitude)
    {
        $user = Auth::user();

        // Seguridad: El estudiante SOLO puede ver sus propias solicitudes
        if ($user->isEstudiante() && $user->estudiante?->id !== $solicitude->estudiante_id) {
            abort(403, 'Acceso denegado. No tienes autorización para consultar solicitudes de otros estudiantes.');
        }

        // Seguridad: El tutor SOLO puede consultar solicitudes de estudiantes asignados a su supervisión
        if ($user->isTutor()) {
            $tutor = $user->tutor;
            $esAsignado = $solicitude->practica && $solicitude->practica->tutor_id === $tutor?->id;
            if (!$esAsignado) {
                abort(403, 'Acceso denegado. Esta solicitud no corresponde a un estudiante asignado a tu supervisión.');
            }
        }

        $solicitude->load(['estudiante.carrera', 'estudiante.requisitos.requisito', 'organizacion.responsables', 'periodo', 'documentos.tipoDocumento', 'historial.usuario', 'practica.tutor']);
        $tiposDocumento = TipoDocumento::where('activo', true)->get();
        return view('solicitudes.show', ['solicitud' => $solicitude, 'tiposDocumento' => $tiposDocumento]);
    }

    public function cambiarEstado(Request $request, SolicitudPractica $solicitude)
    {
        $user = Auth::user();

        // Seguridad: La aprobación, observación y resolución de solicitudes es competencia exclusiva del Coordinador Académico.
        // El Administrador técnico, los tutores y los estudiantes NO aprueban solicitudes académicas.
        if (!$user->isCoordinador()) {
            abort(403, 'Acceso denegado. La aprobación y resolución académica de solicitudes es potestad exclusiva de la Coordinación Académica.');
        }

        $request->validate([
            'nuevo_estado' => 'required|in:aprobada,observada,rechazada,en_revision',
            'motivo' => 'required|string|max:500',
        ]);
        $estadoAnterior = $solicitude->estado;
        $nuevoEstado = $request->nuevo_estado;

        // ── Reglas de negocio académicas para APROBAR una solicitud (RF-09) ──
        if ($nuevoEstado === 'aprobada') {
            // 1. La organización receptora debe estar activa
            if ($solicitude->organizacion->estado !== 'activa') {
                return back()->withErrors(['nuevo_estado' => 'No se puede aprobar la solicitud: La organización receptora seleccionada se encuentra inactiva.'])->withInput();
            }

            // 2. Transición de estado: solo se puede aprobar desde presentada/en_revision/observada
            $estadosValidos = ['presentada', 'en_revision', 'observada'];
            if (!in_array($estadoAnterior, $estadosValidos)) {
                return back()->withErrors([
                    'nuevo_estado' => "No se puede aprobar una solicitud con estado actual «{$estadoAnterior}». Solo se puede aprobar desde: Presentada, En revisión u Observada."
                ])->withInput();
            }

            // 3. Verificar que los requisitos académicos obligatorios estén cumplidos
            $reqsObligatoriosPendientes = \App\Models\EstudianteRequisito::where('estudiante_id', $solicitude->estudiante_id)
                ->whereHas('requisito', fn($q) => $q->where('obligatorio', true))
                ->where('estado', '!=', 'cumple')
                ->with('requisito')
                ->get();

            if ($reqsObligatoriosPendientes->isNotEmpty()) {
                $nombresFaltantes = $reqsObligatoriosPendientes->pluck('requisito.nombre')->implode(', ');
                return back()->withErrors([
                    'nuevo_estado' => "No se puede aprobar la solicitud: El estudiante tiene requisitos académicos obligatorios pendientes o no cumplidos ({$nombresFaltantes}). Verifique la ficha académica del estudiante."
                ])->withInput();
            }

            // 4. RF-09: Verificar que todos los documentos obligatorios estén adjuntos y validados
            $tiposObligatorios = \App\Models\TipoDocumento::where('activo', true)->where('obligatorio', true)->pluck('id');
            $docsEnExpediente  = $solicitude->documentos()->whereIn('tipo_documento_id', $tiposObligatorios)->get()->keyBy('tipo_documento_id');

            $faltantes   = [];
            $noValidados = [];
            foreach ($tiposObligatorios as $tipoId) {
                if (!isset($docsEnExpediente[$tipoId])) {
                    $faltantes[] = \App\Models\TipoDocumento::find($tipoId)?->nombre ?? "Tipo #{$tipoId}";
                } elseif (in_array($docsEnExpediente[$tipoId]->estado, ['rechazado', 'pendiente', 'observado'])) {
                    $noValidados[] = $docsEnExpediente[$tipoId]->nombre_original . ' (' . $docsEnExpediente[$tipoId]->estado . ')';
                }
            }

            if (!empty($faltantes)) {
                return back()->withErrors([
                    'nuevo_estado' => 'No se puede aprobar: Faltan documentos obligatorios en el expediente: ' . implode(', ', $faltantes) . '. El estudiante debe adjuntarlos primero.'
                ])->withInput();
            }

            if (!empty($noValidados)) {
                return back()->withErrors([
                    'nuevo_estado' => 'No se puede aprobar: Los siguientes documentos no están validados (pendientes, observados o rechazados): ' . implode(', ', $noValidados) . '. Valídelos antes de aprobar la solicitud.'
                ])->withInput();
            }
        }

        // ── Validar transición de estado para rechazada/observada/en_revision ──
        if ($nuevoEstado === 'rechazada' && in_array($estadoAnterior, ['aprobada', 'rechazada'])) {
            return back()->withErrors(['nuevo_estado' => "No se puede rechazar una solicitud ya {$estadoAnterior}."])->withInput();
        }

        DB::transaction(function () use ($solicitude, $estadoAnterior, $nuevoEstado, $request, $user) {
            $solicitude->estado = $nuevoEstado;
            $solicitude->fecha_revision = now();
            
            // Asignar coordinador si existe
            $coord = Coordinador::where('usuario_id', $user->id)->first();
            if ($coord) {
                $solicitude->revisada_por_coordinador_id = $coord->id;
            }
            $solicitude->observacion = $request->motivo;
            $solicitude->save();

            // Registrar trazabilidad
            HistorialEstadoSolicitud::create([
                'solicitud_id' => $solicitude->id,
                'estado_anterior' => $estadoAnterior,
                'estado_nuevo' => $nuevoEstado,
                'motivo' => $request->motivo,
                'cambiado_por' => $user->id,
                'fecha_cambio' => now(),
            ]);

            // RF-10: horas_objetivo y fecha_fin_prevista desde config/practicas.php, NO valores fijos.
            // RF-10: La asignación de tutor corresponde al coordinador DESPUÉS de crear la práctica.
            if ($nuevoEstado === 'aprobada' && !$solicitude->practica) {
                $defaultCoord = Coordinador::first();
                Practica::create([
                    'solicitud_id'      => $solicitude->id,
                    'coordinador_id'    => $coord ? $coord->id : ($defaultCoord ? $defaultCoord->id : 1),
                    'fecha_inicio'      => now()->toDateString(),
                    'fecha_fin_prevista'=> now()->addMonths(config('practicas.duracion_meses', 3))->toDateString(),
                    'horas_objetivo'    => config('practicas.horas_objetivo', 240.0),
                    'estado'            => 'planificada',
                    // tutor_id se asigna explícitamente desde el módulo de seguimiento por el coordinador.
                ]);
            }
        });

        return back()->with('success', "Estado de la solicitud actualizado a: {$nuevoEstado}.");
    }
}

