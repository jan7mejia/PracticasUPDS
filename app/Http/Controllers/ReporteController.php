<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\PeriodoAcademico;

class ReporteController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();

        if ($user->isEstudiante()) {
            abort(403, 'Acción no autorizada. El módulo de reportes consolidados institucionales está reservado para coordinación y tutoría.');
        }

        // Vista de reportes académicos exclusivos para el Docente Tutor
        if ($user->isTutor()) {
            $tutor = $user->tutor;
            $tutorId = $tutor?->id;

            $practicas = \App\Models\Practica::where('tutor_id', $tutorId)
                ->with([
                    'solicitud.estudiante.carrera',
                    'solicitud.organizacion',
                    'informes' => fn($q) => $q->orderBy('fecha_envio', 'desc'),
                    'evaluacionesSeguimiento' => fn($q) => $q->orderBy('fecha', 'desc'),
                    'evaluacionFinal',
                    'registrosHoras',
                ])
                ->latest()
                ->get();

            return view('reportes.tutor_index', compact('tutor', 'practicas'));
        }

        $tipo = $request->get('tipo', 'cumplimiento');
        $periodos = PeriodoAcademico::all();

        $enCurso = DB::table('vw_reporte_en_curso')->get();
        $concluidas = DB::table('vw_reporte_concluidas')->get();
        $abandonadas = DB::table('vw_reporte_abandonadas')->get();
        $pendientes = DB::table('vw_reporte_solicitudes_pendientes')->get();
        $organizaciones = DB::table('vw_reporte_organizaciones_activas')->get();
        $porTutor = DB::table('vw_reporte_estudiantes_por_tutor')->get();
        $cumplimiento = DB::table('vw_reporte_cumplimiento')->get();

        return view('reportes.index', compact(
            'tipo',
            'periodos',
            'enCurso',
            'concluidas',
            'abandonadas',
            'pendientes',
            'organizaciones',
            'porTutor',
            'cumplimiento'
        ));
    }

    public function exportar(Request $request)
    {
        $user = auth()->user();
        if ($user->isEstudiante()) {
            abort(403, 'Acceso denegado');
        }

        $tipo = $request->get('tipo', 'cumplimiento');
        $filename = "reporte_{$tipo}_" . date('Ymd_His') . ".csv";

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0'
        ];

        return response()->stream(function () use ($tipo) {
            $handle = fopen('php://output', 'w');
            // Write UTF-8 BOM so Excel opens with correct encoding
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));

            switch ($tipo) {
                case 'encurso':
                    fputcsv($handle, ['Estudiante', 'RU', 'Organización', 'Docente Tutor', 'Fecha Inicio', 'Horas Validadas', 'Horas Objetivo', '% Avance']);
                    $rows = DB::table('vw_reporte_en_curso')->get();
                    foreach ($rows as $r) {
                        fputcsv($handle, [
                            $r->estudiante,
                            $r->ru,
                            $r->organizacion,
                            $r->tutor,
                            $r->fecha_inicio,
                            $r->horas_validadas,
                            $r->horas_objetivo,
                            $r->porcentaje_horas . '%'
                        ]);
                    }
                    break;

                case 'concluidas':
                    fputcsv($handle, ['Estudiante', 'RU', 'Organización', 'Docente Tutor', 'Fecha Fin', 'Horas Validadas', 'Calificación Final', 'Resultado']);
                    $rows = DB::table('vw_reporte_concluidas')->get();
                    foreach ($rows as $r) {
                        fputcsv($handle, [
                            $r->estudiante,
                            $r->ru,
                            $r->organizacion,
                            $r->tutor,
                            $r->fecha_fin,
                            $r->horas_validadas,
                            $r->calificacion_final ?? 'S/C',
                            $r->resultado_final ?? 'Concluida'
                        ]);
                    }
                    break;

                case 'abandonadas':
                    fputcsv($handle, ['Estudiante', 'RU', 'Organización', 'Docente Tutor', 'Horas Realizadas', 'Motivo']);
                    $rows = DB::table('vw_reporte_abandonadas')->get();
                    foreach ($rows as $r) {
                        fputcsv($handle, [
                            $r->estudiante,
                            $r->ru,
                            $r->organizacion,
                            $r->tutor ?? 'No asignado',
                            $r->horas_validadas,
                            $r->motivo ?? 'Abandono registrado'
                        ]);
                    }
                    break;

                case 'pendientes':
                    fputcsv($handle, ['Código Solicitud', 'Estudiante', 'RU', 'Carrera', 'Organización', 'Fecha Solicitud', 'Estado']);
                    $rows = DB::table('vw_reporte_solicitudes_pendientes')->get();
                    foreach ($rows as $r) {
                        fputcsv($handle, [
                            $r->codigo ?? ('SOL-' . $r->id),
                            $r->estudiante,
                            $r->ru,
                            $r->carrera,
                            $r->organizacion,
                            $r->fecha_solicitud,
                            $r->estado
                        ]);
                    }
                    break;

                case 'tutor':
                    fputcsv($handle, ['Docente Tutor', 'Especialidad', 'Total Asignados', 'En Curso', 'Concluidas']);
                    $rows = DB::table('vw_reporte_estudiantes_por_tutor')->get();
                    foreach ($rows as $r) {
                        fputcsv($handle, [
                            $r->tutor,
                            $r->especialidad ?? '',
                            $r->total_asignados,
                            $r->en_curso ?? 0,
                            $r->concluidas ?? 0
                        ]);
                    }
                    break;

                case 'organizaciones':
                    fputcsv($handle, ['Organización', 'Sector', 'Convenio Activo', 'Total Prácticas', 'En Curso']);
                    $rows = DB::table('vw_reporte_organizaciones_activas')->get();
                    foreach ($rows as $r) {
                        fputcsv($handle, [
                            $r->organizacion,
                            $r->sector,
                            $r->tiene_convenio ? 'Sí' : 'No',
                            $r->total_practicas ?? 0,
                            $r->practicas_activas ?? 0
                        ]);
                    }
                    break;

                case 'cumplimiento':
                default:
                    fputcsv($handle, ['RU', 'Estudiante', 'Docente Tutor', 'Organización', 'Horas Validadas', 'Horas Objetivo', '% Avance', 'Estado']);
                    $rows = DB::table('vw_reporte_cumplimiento')->get();
                    foreach ($rows as $r) {
                        fputcsv($handle, [
                            $r->ru,
                            $r->estudiante,
                            $r->tutor ?? 'Sin tutor',
                            $r->organizacion,
                            $r->horas_validadas,
                            $r->horas_objetivo,
                            $r->porcentaje_horas . '%',
                            $r->estado
                        ]);
                    }
                    break;
            }

            fclose($handle);
        }, 200, $headers);
    }
}

