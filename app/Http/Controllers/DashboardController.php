<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Estudiante;
use App\Models\SolicitudPractica;
use App\Models\Practica;
use App\Models\OrganizacionReceptora;
use App\Models\RegistroHora;
use App\Models\InformeAvance;
use App\Models\DocumentoSolicitud;
use App\Models\DocenteTutor;

class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        // Datos para Estudiante
        if ($user->isEstudiante()) {
            $estudiante = $user->estudiante;
            
            $solicitudes = $estudiante ? SolicitudPractica::where('estudiante_id', $estudiante->id)->with(['organizacion', 'periodo', 'practica.evaluacionFinal'])->orderBy('id', 'desc')->get() : collect();
            $todasPracticas = $estudiante ? Practica::whereHas('solicitud', function ($q) use ($estudiante) {
                $q->where('estudiante_id', $estudiante->id);
            })->with(['solicitud.organizacion', 'tutor', 'evaluacionFinal', 'registrosHoras'])->orderBy('id', 'asc')->get() : collect();

            $practicaActiva = $todasPracticas->whereIn('estado', ['en_curso', 'planificada'])->first();
            $practicasConcluidas = $todasPracticas->where('estado', 'concluida');

            $totalSolicitudes = $solicitudes->count();
            $solicitudesPendientes = $solicitudes->whereIn('estado', ['presentada', 'en_revision', 'pendiente'])->count();
            
            $documentosPendientesObservados = $estudiante ? (
                DocumentoSolicitud::whereHas('solicitud', fn($q) => $q->where('estudiante_id', $estudiante->id))
                    ->whereIn('estado', ['observado', 'rechazado'])->count()
                + InformeAvance::whereHas('practica.solicitud', fn($q) => $q->where('estudiante_id', $estudiante->id))
                    ->where('estado', 'observado')->count()
            ) : 0;

            // Cómputo total de horas validadas acumuladas de todas sus prácticas
            $horasValidadas = $estudiante ? RegistroHora::whereHas('practica.solicitud', function ($q) use ($estudiante) {
                $q->where('estudiante_id', $estudiante->id);
            })->where('estado', 'validada')->sum('horas') : 0;

            return view('dashboard.index', [
                'user' => $user,
                'estudiante' => $estudiante,
                'practicaActiva' => $practicaActiva,
                'todasPracticas' => $todasPracticas,
                'practicasConcluidas' => $practicasConcluidas,
                'solicitudes' => $solicitudes,
                'kpi' => [
                    'solicitudes_total' => $totalSolicitudes,
                    'solicitudes_pendientes' => $solicitudesPendientes,
                    'practica_activa' => $practicaActiva ? 1 : 0,
                    'horas_validadas' => $horasValidadas,
                    'documentos_pendientes' => $documentosPendientesObservados,
                ]
            ]);
        }

        // Datos para Tutor
        if ($user->isTutor()) {
            $tutor = $user->tutor;
            $practicas = $tutor 
                ? Practica::where('tutor_id', $tutor->id)->with(['solicitud.estudiante.carrera', 'solicitud.organizacion'])->get() 
                : collect();

            $horasPendientes = $tutor ? RegistroHora::whereHas('practica', function ($q) use ($tutor) {
                $q->where('tutor_id', $tutor->id);
            })->where('estado', 'pendiente')->count() : 0;

            $informesPendientes = $tutor ? InformeAvance::whereHas('practica', function ($q) use ($tutor) {
                $q->where('tutor_id', $tutor->id);
            })->whereIn('estado', ['enviado', 'borrador'])->count() : 0;

            $evaluacionesPendientes = $practicas->where('estado', 'en_curso')->filter(function ($p) {
                return !$p->evaluacionFinal;
            })->count();

            $practicasFinalizadas = $practicas->where('estado', 'concluida')->count();

            return view('dashboard.index', [
                'user' => $user,
                'tutor' => $tutor,
                'practicas' => $practicas,
                'kpi' => [
                    'estudiantes_asignados' => $practicas->count(),
                    'practicas_en_curso' => $practicas->where('estado', 'en_curso')->count(),
                    'horas_pendientes' => $horasPendientes,
                    'informes_pendientes' => $informesPendientes,
                    'evaluaciones_pendientes' => $evaluacionesPendientes,
                    'practicas_finalizadas' => $practicasFinalizadas,
                ]
            ]);
        }

        // Datos para Administrador del Sistema
        if ($user->isAdmin()) {
            $totalUsuarios = \App\Models\User::count();
            $usuariosActivos = \App\Models\User::where('activo', 1)->count();
            $usuariosInactivos = \App\Models\User::where('activo', 0)->count();
            $totalEstudiantes = \App\Models\User::where('rol_id', 4)->count();
            $totalTutores = \App\Models\DocenteTutor::count();
            $totalCoordinadores = \App\Models\Coordinador::count();
            $organizacionesActivas = OrganizacionReceptora::where('estado', 'activa')->count();
            $periodoVigente = \App\Models\PeriodoAcademico::where('activo', 1)->first();
            $totalSolicitudes = SolicitudPractica::count();
            $totalPracticas = Practica::count();

            $usuariosRecientes = \App\Models\User::with('rol')->latest()->take(6)->get();
            $auditoriaReciente = \App\Models\AuditoriaAdministrativa::with(['admin', 'usuarioAfectado'])
                ->orderBy('created_at', 'desc')
                ->take(8)
                ->get();

            $solicitudesStats = [
                'total' => $totalSolicitudes,
                'pendientes' => SolicitudPractica::whereIn('estado', ['presentada', 'en_revision'])->count(),
                'aprobadas' => SolicitudPractica::where('estado', 'aprobada')->count(),
                'observadas' => SolicitudPractica::where('estado', 'observada')->count(),
                'rechazadas' => SolicitudPractica::where('estado', 'rechazada')->count(),
            ];

            $practicasStats = [
                'total' => $totalPracticas,
                'en_curso' => Practica::where('estado', 'en_curso')->count(),
                'concluidas' => Practica::where('estado', 'concluida')->count(),
                'abandonadas' => Practica::whereIn('estado', ['abandonada', 'cancelada'])->count(),
            ];

            return view('dashboard.index', [
                'user' => $user,
                'kpi' => [
                    'total_usuarios' => $totalUsuarios,
                    'usuarios_activos' => $usuariosActivos,
                    'usuarios_inactivos' => $usuariosInactivos,
                    'total_estudiantes' => $totalEstudiantes,
                    'total_tutores' => $totalTutores,
                    'total_coordinadores' => $totalCoordinadores,
                    'organizaciones_activas' => $organizacionesActivas,
                    'periodo_vigente' => $periodoVigente ? $periodoVigente->codigo : 'Sin período activo',
                    'solicitudes_total' => $totalSolicitudes,
                    'practicas_total' => $totalPracticas,
                ],
                'usuariosRecientes' => $usuariosRecientes,
                'auditoriaReciente' => $auditoriaReciente,
                'solicitudesStats' => $solicitudesStats,
                'practicasStats' => $practicasStats,
                'periodoVigenteObj' => $periodoVigente,
            ]);
        }

        // Datos para Coordinador Académico
        $totalEstudiantes = Estudiante::count();
        $solicitudesPendientes = SolicitudPractica::whereIn('estado', ['presentada', 'en_revision'])->count();
        $solicitudesObservadas = SolicitudPractica::where('estado', 'observada')->count();
        $solicitudesAprobadas = SolicitudPractica::where('estado', 'aprobada')->count();
        $practicasActivas = Practica::whereIn('estado', ['planificada', 'en_curso'])->count();
        $practicasFinalizadas = Practica::where('estado', 'concluida')->count();
        $practicasAbandonadas = Practica::whereIn('estado', ['abandonada', 'cancelada'])->count();
        $organizacionesActivas = OrganizacionReceptora::where('estado', 'activa')->count();
        $tutoresConPracticas = Practica::whereNotNull('tutor_id')->distinct('tutor_id')->count('tutor_id');
        $documentosPendientes = DocumentoSolicitud::where('estado', 'pendiente')->count();

        // Alertas académicas reales
        $practicasSinTutor = Practica::whereIn('estado', ['planificada', 'en_curso'])
            ->whereNull('tutor_id')
            ->with(['solicitud.estudiante', 'solicitud.organizacion'])
            ->get();

        $practicasPorVencer = Practica::where('estado', 'en_curso')
            ->whereNotNull('fecha_fin_prevista')
            ->where('fecha_fin_prevista', '<=', now()->addDays(15))
            ->with(['solicitud.estudiante', 'solicitud.organizacion', 'tutor'])
            ->get();

        $solicitudesRecientes = SolicitudPractica::with(['estudiante.carrera', 'organizacion'])
            ->latest('fecha_solicitud')
            ->take(6)
            ->get();

        return view('dashboard.index', [
            'user' => $user,
            'kpi' => [
                'total_estudiantes' => $totalEstudiantes,
                'solicitudes_pendientes' => $solicitudesPendientes,
                'solicitudes_observadas' => $solicitudesObservadas,
                'solicitudes_aprobadas' => $solicitudesAprobadas,
                'practicas_activas' => $practicasActivas,
                'practicas_finalizadas' => $practicasFinalizadas,
                'practicas_abandonadas' => $practicasAbandonadas,
                'organizaciones_activas' => $organizacionesActivas,
                'tutores_con_practicas' => $tutoresConPracticas,
                'documentos_pendientes' => $documentosPendientes,
            ],
            'alertas' => [
                'sin_tutor' => $practicasSinTutor,
                'por_vencer' => $practicasPorVencer,
            ],
            'solicitudesRecientes' => $solicitudesRecientes,
        ]);
    }
}

