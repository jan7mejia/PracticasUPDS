<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use App\Models\Estudiante;
use App\Models\User;
use App\Models\Carrera;
use App\Models\RequisitoAcademico;
use App\Models\EstudianteRequisito;

class EstudianteController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();

        // Si es Docente Tutor, SOLO puede ver sus estudiantes asignados a través de prácticas
        if ($user->isTutor()) {
            $tutor = $user->tutor;
            $practicas = $tutor 
                ? \App\Models\Practica::where('tutor_id', $tutor->id)->with(['solicitud.estudiante.carrera', 'solicitud.organizacion'])->get() 
                : collect();

            return view('estudiantes.tutor_index', compact('practicas', 'tutor'));
        }

        $perPage = in_array((int)$request->get('per_page'), [10, 25, 50]) ? (int)$request->get('per_page') : 10;
        $query = Estudiante::with(['carrera', 'user']);

        if ($request->filled('codigo')) {
            $query->where('ru', 'like', '%' . $request->codigo . '%');
        }

        if ($request->filled('nombre')) {
            $nombre = $request->nombre;
            $query->where(function ($q) use ($nombre) {
                $q->where('nombres', 'like', "%{$nombre}%")
                  ->orWhere('apellidos', 'like', "%{$nombre}%");
            });
        }

        if ($request->filled('carrera_id')) {
            $query->where('carrera_id', $request->carrera_id);
        }

        if ($request->filled('estado')) {
            $query->where('estado', $request->estado);
        }

        $estudiantes = $query->orderBy('id', 'desc')->paginate($perPage)->withQueryString();
        $carreras = Carrera::where('activa', true)->get();
        $cuentasEstudiantes = User::where('rol_id', 4)->whereDoesntHave('estudiante')->orderBy('name')->get();

        return view('estudiantes.index', compact('estudiantes', 'carreras', 'cuentasEstudiantes', 'perPage'));
    }

    public function create()
    {
        if (auth()->user()->isTutor() || auth()->user()->isEstudiante()) {
            abort(403, 'Acceso denegado. Solo la coordinación académica y administración pueden registrar estudiantes.');
        }
        $carreras = Carrera::where('activa', true)->get();
        // Cuentas de usuario de rol Estudiante creadas por el Administrador pendientes de ficha académica
        $cuentasEstudiantes = User::where('rol_id', 4)->whereDoesntHave('estudiante')->orderBy('name')->get();

        return view('estudiantes.create', compact('carreras', 'cuentasEstudiantes'));
    }

    public function store(Request $request)
    {
        if (auth()->user()->isTutor() || auth()->user()->isEstudiante()) {
            abort(403, 'Acceso denegado. No tienes autorización para crear estudiantes.');
        }
        $request->validate([
            'nombres' => 'required|string|max:100',
            'apellidos' => 'required|string|max:120',
            'ru' => 'required|string|max:40|unique:estudiantes,ru',
            'ci' => 'nullable|string|max:40|unique:estudiantes,ci',
            'usuario_id' => 'nullable|exists:users,id|unique:estudiantes,usuario_id',
            'carrera_id' => 'required|exists:carreras,id',
            'semestre' => 'nullable|integer|between:1,15',
            'telefono' => 'nullable|string|max:30',
        ]);

        $estudiante = DB::transaction(function () use ($request) {
            $estudiante = Estudiante::create([
                'usuario_id' => $request->usuario_id,
                'carrera_id' => $request->carrera_id,
                'ru' => $request->ru,
                'ci' => $request->ci,
                'nombres' => $request->nombres,
                'apellidos' => $request->apellidos,
                'telefono' => $request->telefono,
                'semestre' => $request->semestre,
                'estado' => 'activo',
            ]);

            // Asignar requisitos académicos de base
            $requisitos = RequisitoAcademico::where('activo', true)
                ->where(function ($q) use ($request) {
                    $q->whereNull('carrera_id')->orWhere('carrera_id', $request->carrera_id);
                })->get();

            foreach ($requisitos as $req) {
                EstudianteRequisito::create([
                    'estudiante_id' => $estudiante->id,
                    'requisito_id' => $req->id,
                    'estado' => 'pendiente',
                ]);
            }

            return $estudiante;
        });

        $msg = $estudiante->usuario_id 
            ? 'Ficha académica del estudiante registrada y vinculada a la cuenta de usuario.'
            : 'Ficha académica registrada exitosamente (Pendiente de vinculación de cuenta por el Administrador).';

        return redirect()->route('estudiantes.show', $estudiante->id)->with('success', $msg);
    }

    public function show(Estudiante $estudiante)
    {
        $user = auth()->user();

        // Si es tutor, verificar que el estudiante esté asignado a una de sus prácticas
        if ($user->isTutor()) {
            $tutor = $user->tutor;
            $asignado = \App\Models\Practica::where('tutor_id', $tutor?->id)
                ->whereHas('solicitud', fn($q) => $q->where('estudiante_id', $estudiante->id))
                ->exists();

            if (!$asignado) {
                abort(403, 'Acceso denegado. Este estudiante no está asignado a tu supervisión académica.');
            }
        }

        $estudiante->load(['carrera', 'user', 'requisitos.requisito', 'solicitudes.organizacion', 'solicitudes.practica']);
        return view('estudiantes.show', compact('estudiante'));
    }

    public function edit(Estudiante $estudiante)
    {
        if (auth()->user()->isTutor() || auth()->user()->isEstudiante()) {
            abort(403, 'Acceso denegado. Los docentes tutores no pueden editar los registros académicos de los estudiantes.');
        }
        $carreras = Carrera::where('activa', true)->get();
        return view('estudiantes.edit', compact('estudiante', 'carreras'));
    }

    public function update(Request $request, Estudiante $estudiante)
    {
        if (auth()->user()->isTutor() || auth()->user()->isEstudiante()) {
            abort(403, 'Acceso denegado. Operación no permitida.');
        }
        $request->validate([
            'nombres' => 'required|string|max:100',
            'apellidos' => 'required|string|max:120',
            'ru' => 'required|string|max:40|unique:estudiantes,ru,' . $estudiante->id,
            'ci' => 'nullable|string|max:40|unique:estudiantes,ci,' . $estudiante->id,
            'carrera_id' => 'required|exists:carreras,id',
            'semestre' => 'nullable|integer|between:1,15',
            'telefono' => 'nullable|string|max:30',
            'estado' => 'required|in:activo,inactivo,egresado',
        ]);

        $estudiante->update($request->only([
            'nombres', 'apellidos', 'ru', 'ci', 'carrera_id', 'semestre', 'telefono', 'estado'
        ]));

        if ($estudiante->user) {
            $estudiante->user->update([
                'name' => "{$request->nombres} {$request->apellidos}",
            ]);
        }

        return redirect()->route('estudiantes.show', $estudiante->id)->with('success', 'Datos del estudiante actualizados correctamente.');
    }

    public function verificarRequisito(Request $request, Estudiante $estudiante, EstudianteRequisito $requisito)
    {
        if (auth()->user()->isTutor() || auth()->user()->isEstudiante()) {
            abort(403, 'Acceso denegado. Solo la coordinación académica puede verificar requisitos académicos.');
        }

        $request->validate([
            'estado' => 'required|in:cumple,pendiente,no_cumple',
            'observacion' => 'nullable|string|max:500',
        ]);

        $requisito->update([
            'estado' => $request->estado,
            'observacion' => $request->observacion,
            'verificado_por' => auth()->id(),
            'fecha_verificacion' => now(),
        ]);

        return back()->with('success', "Requisito verificado como: " . strtoupper($request->estado) . ".");
    }
}

