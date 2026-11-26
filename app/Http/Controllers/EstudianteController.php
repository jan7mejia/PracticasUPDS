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

        if ($request->filled('buscar')) {
            $search = trim($request->buscar);
            $query->where(function ($q) use ($search) {
                $q->where('nombres', 'like', "%{$search}%")
                  ->orWhere('apellidos', 'like', "%{$search}%")
                  ->orWhere('ru', 'like', "%{$search}%")
                  ->orWhere('ci', 'like', "%{$search}%")
                  ->orWhereHas('carrera', fn($cQ) => $cQ->where('nombre', 'like', "%{$search}%"));
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

        $tipoCuenta = $request->input('tipo_cuenta', 'nueva');
        $crearCuenta = ($tipoCuenta === 'nueva') || ($request->filled('email_institucional') && $tipoCuenta !== 'existente' && $tipoCuenta !== 'ninguna');

        $rules = [
            'nombres'    => ['required', 'string', 'max:100', 'regex:/^[a-zA-ZáéíóúÁÉÍÓÚñÑüÜ\s\.\'\-]+$/u'],
            'apellidos'  => ['required', 'string', 'max:120', 'regex:/^[a-zA-ZáéíóúÁÉÍÓÚñÑüÜ\s\.\'\-]+$/u'],
            'ru'         => ['required', 'string', 'max:20', 'unique:estudiantes,ru', 'regex:/^[0-9]{6,10}$/'],
            'ci'         => ['nullable', 'string', 'max:20', 'unique:estudiantes,ci', 'regex:/^[0-9]{5,9}(?:[\s\-]?[0-9A-Za-z]{1,3})?$/'],
            'carrera_id' => 'required|exists:carreras,id',
            'semestre'   => 'nullable|integer|between:1,15',
            'telefono'   => ['nullable', 'string', 'max:25', 'regex:/^(\+?591[\s\-]?)?(\(?4\)?[\s\-]?)?[467]\d{6,7}$/'],
        ];

        if ($crearCuenta) {
            $rules['email_institucional'] = 'required|email|max:150|unique:users,email';
            $rules['password_inicial'] = 'required|string|min:6';
        } else {
            $rules['usuario_id'] = 'nullable|exists:users,id|unique:estudiantes,usuario_id';
        }

        $request->validate($rules, [
            'nombres.required'           => 'Ingresa los nombres del estudiante.',
            'nombres.regex'              => 'Los nombres solo deben contener letras y espacios, no se permiten números.',
            'apellidos.required'         => 'Ingresa los apellidos del estudiante.',
            'apellidos.regex'            => 'Los apellidos solo deben contener letras y espacios, no se permiten números.',
            'ru.required'                => 'El Registro Universitario (RU) es obligatorio.',
            'ru.unique'                  => 'El RU ingresado ya se encuentra registrado.',
            'ru.regex'                   => 'El RU debe contener únicamente números (formato UPDS ej: 3200465).',
            'ci.regex'                   => 'La Cédula de Identidad (CI) debe tener un formato boliviano válido (5 a 9 dígitos numéricos).',
            'telefono.regex'             => 'El teléfono debe ser un número válido de Bolivia / Cochabamba (celular de 8 dígitos empezando en 6 o 7, o fijo de Cochabamba empezando en 4).',
            'carrera_id.required'        => 'Selecciona la carrera académica.',
            'email_institucional.required' => 'Ingresa el correo institucional UPDS para la cuenta del estudiante.',
            'email_institucional.unique' => 'Ya existe un usuario registrado con este correo electrónico institucional.',
            'password_inicial.required'  => 'Ingresa la contraseña inicial de acceso.',
            'password_inicial.min'       => 'La contraseña debe contener al menos 6 caracteres.',
        ]);

        $estudiante = DB::transaction(function () use ($request, $crearCuenta) {
            $usuarioId = $request->usuario_id;

            if ($crearCuenta) {
                $userEstudiante = User::create([
                    'name' => trim("{$request->nombres} {$request->apellidos}"),
                    'email' => strtolower(trim($request->email_institucional)),
                    'password' => Hash::make($request->password_inicial),
                    'rol_id' => 4, // Rol Estudiante
                    'email_verified_at' => now(),
                ]);
                $usuarioId = $userEstudiante->id;
            }

            $estudiante = Estudiante::create([
                'usuario_id' => $usuarioId,
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

            $semestreVal = (int) $request->semestre;
            $cumple = $request->has('cumple_requisitos') ? $request->boolean('cumple_requisitos') : ($semestreVal >= 7);
            $estadoInicial = $cumple ? 'cumple' : 'pendiente';

            foreach ($requisitos as $req) {
                EstudianteRequisito::create([
                    'estudiante_id' => $estudiante->id,
                    'requisito_id'  => $req->id,
                    'estado'        => $estadoInicial,
                    'observacion'   => $cumple ? "Verificado por registro regular (Semestre {$semestreVal}°)" : null,
                    'verificado_por'=> $cumple ? auth()->id() : null,
                    'fecha_verificacion' => $cumple ? now() : null,
                ]);
            }

            return $estudiante;
        });

        $msg = $estudiante->usuario_id 
            ? "Estudiante {$estudiante->nombre_completo} registrado y cuenta institucional ({$estudiante->user?->email}) habilitada inmediatamente."
            : "Estudiante {$estudiante->nombre_completo} registrado exitosamente.";

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

        $rules = [
            'nombres'    => ['required', 'string', 'max:100', 'regex:/^[a-zA-ZáéíóúÁÉÍÓÚñÑüÜ\s\.\'\-]+$/u'],
            'apellidos'  => ['required', 'string', 'max:120', 'regex:/^[a-zA-ZáéíóúÁÉÍÓÚñÑüÜ\s\.\'\-]+$/u'],
            'ru'         => ['required', 'string', 'max:20', 'unique:estudiantes,ru,' . $estudiante->id, 'regex:/^[0-9]{6,10}$/'],
            'ci'         => ['nullable', 'string', 'max:20', 'unique:estudiantes,ci,' . $estudiante->id, 'regex:/^[0-9]{5,9}(?:[\s\-]?[0-9A-Za-z]{1,3})?$/'],
            'carrera_id' => 'required|exists:carreras,id',
            'semestre'   => 'nullable|integer|between:1,15',
            'telefono'   => ['nullable', 'string', 'max:25', 'regex:/^(\+?591[\s\-]?)?(\(?4\)?[\s\-]?)?[467]\d{6,7}$/'],
            'estado'     => 'required|in:activo,inactivo,egresado',
        ];

        if (!$estudiante->usuario_id && $request->filled('email_institucional')) {
            $rules['email_institucional'] = 'required|email|max:150|unique:users,email';
            $rules['password_inicial'] = 'required|string|min:6';
        }

        $request->validate($rules, [
            'nombres.regex'   => 'Los nombres solo deben contener letras y espacios, no se permiten números.',
            'apellidos.regex' => 'Los apellidos solo deben contener letras y espacios, no se permiten números.',
            'ru.regex'        => 'El RU debe contener únicamente números (formato UPDS ej: 3200465).',
            'ci.regex'        => 'La Cédula de Identidad (CI) debe tener un formato boliviano válido (5 a 9 dígitos numéricos).',
            'telefono.regex'  => 'El teléfono debe ser un número válido de Bolivia / Cochabamba (celular de 8 dígitos empezando en 6 o 7, o fijo de Cochabamba empezando en 4).',
        ]);

        DB::transaction(function () use ($request, $estudiante) {
            $estudiante->update($request->only([
                'nombres', 'apellidos', 'ru', 'ci', 'carrera_id', 'semestre', 'telefono', 'estado'
            ]));

            if ($estudiante->user) {
                $estudiante->user->update([
                    'name' => "{$request->nombres} {$request->apellidos}",
                ]);
            } elseif ($request->filled('email_institucional') && $request->filled('password_inicial')) {
                $newUser = User::create([
                    'name' => trim("{$request->nombres} {$request->apellidos}"),
                    'email' => strtolower(trim($request->email_institucional)),
                    'password' => Hash::make($request->password_inicial),
                    'rol_id' => 4,
                    'email_verified_at' => now(),
                ]);
                $estudiante->update(['usuario_id' => $newUser->id]);
            }
        });

        return redirect()->route('estudiantes.show', $estudiante->id)->with('success', 'Datos del estudiante actualizados correctamente.');
    }

    public function verificarRequisito(Request $request, Estudiante $estudiante, EstudianteRequisito $requisito)
    {
        if (auth()->user()->isTutor() || auth()->user()->isEstudiante()) {
            abort(403, 'Acceso denegado. Solo la coordinación académica o administración puede verificar requisitos académicos.');
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

    public function validarTodosRequisitos(Request $request, Estudiante $estudiante)
    {
        if (auth()->user()->isTutor() || auth()->user()->isEstudiante()) {
            abort(403, 'Acceso denegado. Solo la coordinación académica o administración puede validar requisitos.');
        }

        $estudiante->requisitos()->update([
            'estado' => 'cumple',
            'observacion' => 'Validado conforme por verificación de expediente para prácticas.',
            'verificado_por' => auth()->id(),
            'fecha_verificacion' => now(),
        ]);

        return back()->with('success', 'Todos los requisitos académicos obligatorios han sido validados como CUMPLE.');
    }
}

