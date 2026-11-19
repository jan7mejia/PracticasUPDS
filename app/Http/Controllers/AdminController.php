<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use App\Models\User;
use App\Models\Role;
use App\Models\PeriodoAcademico;
use App\Models\Estudiante;
use App\Models\DocenteTutor;
use App\Models\Coordinador;
use App\Models\Carrera;
use App\Models\SolicitudPractica;
use App\Models\AuditoriaAdministrativa;

class AdminController extends Controller
{
    // ═══════════════════════════════════════════════
    //  GESTIÓN DE USUARIOS
    // ═══════════════════════════════════════════════

    public function usuariosIndex(Request $request)
    {
        $search    = $request->get('q');
        $rolFilter = $request->get('rol_id');
        $estadoFilter = $request->get('estado');

        $query = User::with('rol')->orderBy('name');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%$search%")
                  ->orWhere('email', 'like', "%$search%");
            });
        }
        if ($rolFilter) {
            $query->where('rol_id', $rolFilter);
        }
        if ($estadoFilter !== null && $estadoFilter !== '') {
            $query->where('activo', $estadoFilter === '1');
        }

        $perPage = in_array((int)$request->get('per_page'), [10, 25, 50]) ? (int)$request->get('per_page') : 10;
        $usuarios = $query->orderBy('id', 'desc')->paginate($perPage)->withQueryString();
        $roles    = Role::orderBy('nombre')->get();

        return view('admin.usuarios.index', compact('usuarios', 'roles', 'search', 'rolFilter', 'estadoFilter', 'perPage'));
    }

    public function usuariosShow(User $usuario)
    {
        $usuario->load(['rol', 'estudiante.carrera', 'tutor.practicas', 'coordinador']);
        $historialAuditoria = AuditoriaAdministrativa::where('usuario_afectado_id', $usuario->id)
            ->with('admin')
            ->orderBy('created_at', 'desc')
            ->take(15)
            ->get();

        return view('admin.usuarios.show', compact('usuario', 'historialAuditoria'));
    }

    public function usuariosCreate()
    {
        $roles = Role::orderBy('nombre')->get();
        $estudiantesSinCuenta = Estudiante::whereNull('usuario_id')->with('carrera')->orderBy('apellidos')->get();
        $tutoresSinCuenta = DocenteTutor::whereNull('usuario_id')->orderBy('apellidos')->get();

        return view('admin.usuarios.create', compact('roles', 'estudiantesSinCuenta', 'tutoresSinCuenta'));
    }

    public function usuariosStore(Request $request)
    {
        $request->validate([
            'name'     => 'required|string|max:160',
            'email'    => 'required|email|max:190|unique:users,email',
            'rol_id'   => 'required|exists:roles,id',
            'password' => 'required|confirmed|min:8',
            'activo'   => 'nullable|boolean',
            'estudiante_id' => 'nullable|exists:estudiantes,id',
            'tutor_id' => 'nullable|exists:docentes_tutores,id',
        ], [
            'email.unique' => 'Ya existe una cuenta registrada con este correo electrónico institucional.',
            'password.confirmed' => 'La confirmación de la contraseña no coincide.',
            'password.min' => 'La contraseña debe contener al menos 8 caracteres.',
        ]);

        $rol = Role::findOrFail($request->rol_id);

        // Control especial: Registro de cuenta de Administrador
        if ($rol->codigo === 'administrador') {
            $authAdmin = Auth::user();
            if (!$authAdmin || !$authAdmin->isAdmin()) {
                abort(403, 'Acción no autorizada. Solo un Administrador autenticado puede registrar otra cuenta administrativa.');
            }
        }

        $usuario = User::create([
            'name'     => trim($request->name),
            'email'    => strtolower(trim($request->email)),
            'rol_id'   => $request->rol_id,
            'password' => Hash::make($request->password),
            'activo'   => $request->boolean('activo', true),
        ]);

        // Vinculación de perfil académico según el rol
        if ($rol->codigo === 'estudiante') {
            if ($request->filled('estudiante_id')) {
                $estudiante = Estudiante::find($request->estudiante_id);
                if ($estudiante && is_null($estudiante->usuario_id)) {
                    $estudiante->update(['usuario_id' => $usuario->id]);
                }
            }
        } elseif ($rol->codigo === 'tutor') {
            if ($request->filled('tutor_id')) {
                $tutor = DocenteTutor::find($request->tutor_id);
                if ($tutor && is_null($tutor->usuario_id)) {
                    $tutor->update(['usuario_id' => $usuario->id]);
                }
            } else {
                // Crear perfil docente base si no existe
                $partes = explode(' ', trim($request->name), 2);
                DocenteTutor::create([
                    'usuario_id' => $usuario->id,
                    'nombres' => $partes[0] ?? $request->name,
                    'apellidos' => $partes[1] ?? 'Docente',
                    'especialidad' => 'Docencia e Investigación',
                    'activo' => 1,
                ]);
            }
        } elseif ($rol->codigo === 'coordinador') {
            $partes = explode(' ', trim($request->name), 2);
            Coordinador::create([
                'usuario_id' => $usuario->id,
                'nombres' => $partes[0] ?? $request->name,
                'apellidos' => $partes[1] ?? 'Coordinación',
                'telefono' => '',
                'activo' => 1,
            ]);
        }

        // Auditoría
        AuditoriaAdministrativa::registrar(
            'CREACION_USUARIO',
            $usuario->id,
            'Registro de nueva cuenta institucional en el sistema',
            ['rol' => $rol->nombre, 'email' => $usuario->email]
        );

        return redirect()->route('admin.usuarios.index')
            ->with('success', "Cuenta para '{$usuario->name}' ({$rol->nombre}) creada correctamente con credenciales cifradas.");
    }

    public function usuariosEdit(User $usuario)
    {
        $roles = Role::orderBy('nombre')->get();
        $usuario->load(['rol', 'estudiante.carrera', 'tutor', 'coordinador']);
        
        $historialAuditoria = AuditoriaAdministrativa::where('usuario_afectado_id', $usuario->id)
            ->with('admin')
            ->orderBy('created_at', 'desc')
            ->take(10)
            ->get();

        return view('admin.usuarios.edit', compact('usuario', 'roles', 'historialAuditoria'));
    }

    public function usuariosUpdate(Request $request, User $usuario)
    {
        $request->validate([
            'name'     => 'required|string|max:160',
            'email'    => 'required|email|max:190|unique:users,email,' . $usuario->id,
            'rol_id'   => 'required|exists:roles,id',
            'motivo'   => 'nullable|string|max:500',
        ], [
            'email.unique' => 'Este correo electrónico ya se encuentra en uso por otra cuenta institucional.',
        ]);

        $nuevoRol = Role::findOrFail($request->rol_id);
        $rolAnterior = $usuario->rol;
        $motivo = $request->motivo ?: 'Modificación administrativa autorizada';

        // Regla: No permitir degradar o despojar de rol al último administrador activo
        if ($usuario->rol_id == 1 && $nuevoRol->id != 1) {
            $adminCount = User::where('rol_id', 1)->where('activo', 1)->count();
            if ($adminCount <= 1) {
                return back()->withErrors(['rol_id' => 'Operación rechazada: No se puede cambiar el rol de la única cuenta administrativa activa del sistema.'])->withInput();
            }
        }

        // Regla: Si es docente tutor con prácticas en curso, verificar antes de cambiar rol
        if ($usuario->rol_id == 3 && $nuevoRol->id != 3) {
            $tutor = $usuario->tutor;
            if ($tutor && $tutor->practicas()->whereIn('estado', ['en_curso', 'planificada'])->exists()) {
                return back()->withErrors([
                    'rol_id' => 'No se puede cambiar el rol de este Docente Tutor: Tiene prácticas profesionales activas bajo su supervisión. La coordinación debe reasignar primero a los estudiantes.'
                ])->withInput();
            }
        }

        // Auditoría de cambios individuales
        if ($usuario->name !== trim($request->name)) {
            AuditoriaAdministrativa::registrar('CORRECCION_NOMBRE', $usuario->id, $motivo, [
                'anterior' => $usuario->name,
                'nuevo' => trim($request->name),
            ]);
        }

        if (strtolower($usuario->email) !== strtolower(trim($request->email))) {
            AuditoriaAdministrativa::registrar('CORRECCION_CORREO', $usuario->id, $motivo, [
                'anterior' => $usuario->email,
                'nuevo' => strtolower(trim($request->email)),
            ]);
        }

        if ($usuario->rol_id != $nuevoRol->id) {
            AuditoriaAdministrativa::registrar('CAMBIO_ROL', $usuario->id, $motivo, [
                'anterior' => $rolAnterior?->nombre ?? 'Sin rol',
                'nuevo' => $nuevoRol->nombre,
            ]);
        }

        $usuario->update([
            'name'     => trim($request->name),
            'email'    => strtolower(trim($request->email)),
            'rol_id'   => $request->rol_id,
            'activo'   => $request->boolean('activo', true),
        ]);

        if ($request->filled('password')) {
            $request->validate([
                'password' => 'confirmed|min:8',
            ], [
                'password.confirmed' => 'La confirmación de contraseña no coincide.',
                'password.min' => 'La nueva contraseña debe tener al menos 8 caracteres.',
            ]);

            $usuario->update(['password' => Hash::make($request->password)]);
            AuditoriaAdministrativa::registrar('CAMBIO_PASSWORD', $usuario->id, $motivo);
        }

        return redirect()->route('admin.usuarios.index')
            ->with('success', "Datos de la cuenta '{$usuario->name}' actualizados correctamente.");
    }

    public function usuariosToggle(Request $request, User $usuario)
    {
        $admin = Auth::user();

        // Regla: No desactivar al último administrador
        if ($usuario->rol_id == 1 && $usuario->activo) {
            $adminCount = User::where('rol_id', 1)->where('activo', 1)->count();
            if ($adminCount <= 1) {
                return back()->withErrors(['error' => 'Operación bloqueada: No se puede desactivar la última cuenta administrativa del sistema.']);
            }
        }

        // Regla: El administrador no debe desactivar su propia cuenta en sesión
        if ($usuario->id === $admin->id && $usuario->activo) {
            return back()->withErrors(['error' => 'No puedes desactivar tu propia cuenta activa de administrador en esta sesión.']);
        }

        // Advertencia si es tutor con prácticas activas
        $notaTutor = '';
        if ($usuario->rol_id == 3 && $usuario->activo) {
            $tutor = $usuario->tutor;
            if ($tutor && $tutor->practicas()->whereIn('estado', ['en_curso', 'planificada'])->exists()) {
                $notaTutor = ' (Advertencia: El tutor tiene prácticas activas que requieren seguimiento o reasignación por coordinación).';
            }
        }

        $nuevoEstado = !$usuario->activo;
        $usuario->update(['activo' => $nuevoEstado]);

        $accion = $nuevoEstado ? 'ACTIVACION_CUENTA' : 'DESACTIVACION_CUENTA';
        $motivo = $request->motivo ?: ($nuevoEstado ? 'Reactivación de cuenta institucional' : 'Desactivación administrativa de cuenta');

        AuditoriaAdministrativa::registrar($accion, $usuario->id, $motivo);

        $estadoStr = $nuevoEstado ? 'activada' : 'desactivada';
        return back()->with('success', "Cuenta de '{$usuario->name}' {$estadoStr} exitosamente.{$notaTutor}");
    }

    public function usuariosResetPassword(Request $request, User $usuario)
    {
        // Generar contraseña temporal segura de 10 caracteres
        $tempPassword = 'Upds' . rand(1000, 9999) . '!';
        $usuario->update(['password' => Hash::make($tempPassword)]);

        AuditoriaAdministrativa::registrar(
            'RESTABLECIMIENTO_ACCESO',
            $usuario->id,
            $request->motivo ?: 'Restablecimiento administrativo de credenciales temporales'
        );

        return back()->with('temp_password_info', [
            'usuario' => $usuario->name,
            'email' => $usuario->email,
            'temp_password' => $tempPassword,
        ])->with('success', "Proceso de recuperación iniciado: Se generó una clave temporal segura para '{$usuario->name}'.");
    }

    // ═══════════════════════════════════════════════
    //  ROLES Y PERMISOS DEL SISTEMA
    // ═══════════════════════════════════════════════

    public function rolesIndex()
    {
        $roles = Role::withCount('users')->get();
        return view('admin.roles.index', compact('roles'));
    }

    // ═══════════════════════════════════════════════
    //  GESTIÓN DE PERÍODOS ACADÉMICOS
    // ═══════════════════════════════════════════════

    public function periodosIndex(Request $request)
    {
        $perPage = in_array((int)$request->get('per_page'), [10, 25, 50]) ? (int)$request->get('per_page') : 10;
        $periodos = PeriodoAcademico::orderBy('fecha_inicio', 'desc')->paginate($perPage)->withQueryString();
        return view('admin.periodos.index', compact('periodos', 'perPage'));
    }

    public function periodosCreate()
    {
        return view('admin.periodos.create');
    }

    public function periodosStore(Request $request)
    {
        $request->validate([
            'codigo'       => 'required|string|max:40|unique:periodos_academicos,codigo',
            'nombre'       => 'required|string|max:120',
            'fecha_inicio' => 'required|date',
            'fecha_fin'    => 'required|date|after:fecha_inicio',
            'activo'       => 'boolean',
        ], [
            'codigo.unique' => 'Ya existe un período académico registrado con este código.',
            'fecha_fin.after' => 'La fecha de finalización debe ser posterior a la fecha de inicio.',
        ]);

        if ($request->boolean('activo')) {
            PeriodoAcademico::where('activo', true)->update(['activo' => false]);
        }

        $periodo = PeriodoAcademico::create([
            'codigo'       => strtoupper(trim($request->codigo)),
            'nombre'       => trim($request->nombre),
            'fecha_inicio' => $request->fecha_inicio,
            'fecha_fin'    => $request->fecha_fin,
            'activo'       => $request->boolean('activo'),
        ]);

        AuditoriaAdministrativa::registrar('CREACION_PERIODO', null, "Período {$periodo->codigo} registrado");

        return redirect()->route('admin.periodos.index')->with('success', 'Período académico creado exitosamente.');
    }

    public function periodosEdit(PeriodoAcademico $periodo)
    {
        return view('admin.periodos.edit', compact('periodo'));
    }

    public function periodosUpdate(Request $request, PeriodoAcademico $periodo)
    {
        $request->validate([
            'codigo'       => 'required|string|max:40|unique:periodos_academicos,codigo,' . $periodo->id,
            'nombre'       => 'required|string|max:120',
            'fecha_inicio' => 'required|date',
            'fecha_fin'    => 'required|date|after:fecha_inicio',
            'activo'       => 'boolean',
        ], [
            'codigo.unique' => 'Ya existe un período registrado con este código.',
            'fecha_fin.after' => 'La fecha de finalización debe ser posterior a la fecha de inicio.',
        ]);

        if ($request->boolean('activo') && !$periodo->activo) {
            PeriodoAcademico::where('activo', true)->update(['activo' => false]);
        }

        $periodo->update([
            'codigo'       => strtoupper(trim($request->codigo)),
            'nombre'       => trim($request->nombre),
            'fecha_inicio' => $request->fecha_inicio,
            'fecha_fin'    => $request->fecha_fin,
            'activo'       => $request->boolean('activo'),
        ]);

        AuditoriaAdministrativa::registrar('EDICION_PERIODO', null, "Período {$periodo->codigo} actualizado");

        return redirect()->route('admin.periodos.index')->with('success', 'Período actualizado correctamente.');
    }

    public function periodosDestroy(PeriodoAcademico $periodo)
    {
        // Regla: No eliminar períodos con solicitudes o prácticas asociadas
        if (SolicitudPractica::where('periodo_id', $periodo->id)->exists()) {
            return back()->withErrors([
                'error' => "No se puede eliminar el período '{$periodo->codigo}': Contiene solicitudes o trámites académicos asociados. Desactívalo en su lugar para mantener la integridad histórica."
            ]);
        }

        AuditoriaAdministrativa::registrar('ELIMINACION_PERIODO', null, "Período {$periodo->codigo} eliminado");
        $periodo->delete();

        return redirect()->route('admin.periodos.index')->with('success', 'Período eliminado correctamente.');
    }

    // ═══════════════════════════════════════════════
    //  CATÁLOGOS INSTITUCIONALES (CARRERAS)
    // ═══════════════════════════════════════════════

    public function carrerasIndex()
    {
        $carreras = Carrera::withCount('estudiantes')->orderBy('nombre')->get();
        return view('admin.carreras.index', compact('carreras'));
    }

    public function carrerasStore(Request $request)
    {
        $request->validate([
            'nombre' => 'required|string|max:120|unique:carreras,nombre',
            'codigo' => 'nullable|string|max:30|unique:carreras,codigo',
        ]);

        $carrera = Carrera::create([
            'nombre' => trim($request->nombre),
            'codigo' => $request->codigo ? strtoupper(trim($request->codigo)) : Str::slug($request->nombre),
        ]);

        AuditoriaAdministrativa::registrar('CATALOGO_CARRERA', null, "Carrera '{$carrera->nombre}' creada");

        return back()->with('success', "Carrera '{$carrera->nombre}' registrada en el catálogo institucional.");
    }

    public function carrerasUpdate(Request $request, Carrera $carrera)
    {
        $request->validate([
            'nombre' => 'required|string|max:120|unique:carreras,nombre,' . $carrera->id,
            'codigo' => 'nullable|string|max:30|unique:carreras,codigo,' . $carrera->id,
        ]);

        $carrera->update([
            'nombre' => trim($request->nombre),
            'codigo' => $request->codigo ? strtoupper(trim($request->codigo)) : $carrera->codigo,
        ]);

        AuditoriaAdministrativa::registrar('CATALOGO_CARRERA', null, "Carrera '{$carrera->nombre}' actualizada");

        return back()->with('success', "Carrera '{$carrera->nombre}' actualizada.");
    }

    // ═══════════════════════════════════════════════
    //  AUDITORÍA DE ACTIVIDAD ADMINISTRATIVA
    // ═══════════════════════════════════════════════

    public function auditoriaIndex(Request $request)
    {
        $query = AuditoriaAdministrativa::with(['admin', 'usuarioAfectado'])->orderBy('created_at', 'desc');

        if ($request->filled('accion')) {
            $query->where('accion', $request->accion);
        }

        if ($request->filled('q')) {
            $q = $request->q;
            $query->where(function ($sub) use ($q) {
                $sub->where('motivo', 'like', "%{$q}%")
                    ->orWhere('detalles', 'like', "%{$q}%")
                    ->orWhereHas('admin', fn($adm) => $adm->where('name', 'like', "%{$q}%"))
                    ->orWhereHas('usuarioAfectado', fn($u) => $u->where('name', 'like', "%{$q}%"));
            });
        }

        $perPage = in_array((int)$request->get('per_page'), [10, 25, 50]) ? (int)$request->get('per_page') : 10;
        $registros = $query->paginate($perPage)->withQueryString();
        $acciones = AuditoriaAdministrativa::select('accion')->distinct()->pluck('accion');

        return view('admin.auditoria.index', compact('registros', 'acciones', 'perPage'));
    }
}
