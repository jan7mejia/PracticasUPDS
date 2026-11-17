@extends('layouts.app')

@section('title', 'Mi Perfil - UPDSonline')

@section('content')
<div class="d-flex align-items-center mb-4 gap-3">
    <div style="width:52px;height:52px;border-radius:50%;background:var(--upds-primary);display:flex;align-items:center;justify-content:center;color:#fff;font-size:1.5rem;font-weight:700;box-shadow:0 4px 12px rgba(0,59,115,0.3);">
        {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
    </div>
    <div>
        <h4 class="mb-0 fw-bold" style="color:var(--upds-navy);">Mi Perfil</h4>
        <small class="text-muted">{{ auth()->user()->rol->nombre ?? 'Usuario' }}</small>
    </div>
</div>

<div class="row g-4">
    <!-- Datos Personales -->
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm">
            <div class="card-header py-3" style="background:var(--upds-primary);border-radius:12px 12px 0 0;">
                <h6 class="mb-0 text-white fw-semibold">
                    <i class="bi bi-person-fill me-2"></i>Información Personal
                </h6>
            </div>
            <div class="card-body p-4">
                <form action="{{ route('perfil.update') }}" method="POST">
                    @csrf @method('PUT')
                    
                    @if(auth()->user()->isEstudiante())
                        <!-- Vista Académica Protegida para Estudiante -->
                        <div class="mb-3">
                            <label class="form-label fw-semibold small text-muted">Nombre Completo (Registrado)</label>
                            <input type="text" class="form-control bg-light" value="{{ $user->estudiante ? $user->estudiante->nombre_completo : $user->name }}" readonly disabled>
                            <div class="form-text text-muted">Solo lectura. Modificaciones se solicitan en Registro Académico.</div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold small text-muted">Correo Institucional UPDS</label>
                            <input type="email" class="form-control bg-light" value="{{ $user->email }}" readonly disabled>
                            <div class="form-text text-muted">Cuenta oficial UPDS.</div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold small text-muted">Teléfono / Celular de Contacto *</label>
                            <input type="text" name="telefono" class="form-control @error('telefono') is-invalid @enderror"
                                   value="{{ old('telefono', $user->estudiante?->telefono) }}" placeholder="Ej: 77912345">
                            @error('telefono') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            <div class="form-text text-muted">Dato de contacto autorizado para notificaciones de seguimiento.</div>
                        </div>
                        <div class="mb-4 p-3 rounded-3" style="background:#f8fafc;border:1px solid #e2e8f0;">
                            <div class="row g-2 text-muted small">
                                <div class="col-6">
                                    <i class="bi bi-mortarboard text-primary me-1"></i>
                                    <strong>RU:</strong> {{ $user->estudiante?->ru ?? 'Sin asignar' }}
                                </div>
                                <div class="col-6">
                                    <i class="bi bi-book text-primary me-1"></i>
                                    <strong>Carrera:</strong> {{ $user->estudiante?->carrera?->nombre ?? '-' }}
                                </div>
                                <div class="col-6 mt-1">
                                    <i class="bi bi-layers text-primary me-1"></i>
                                    <strong>Semestre:</strong> {{ $user->estudiante?->semestre ? $user->estudiante->semestre . '°' : '-' }}
                                </div>
                                <div class="col-6 mt-1">
                                    <i class="bi bi-circle-fill me-1 {{ $user->activo ? 'text-success' : 'text-danger' }}" style="font-size:.5rem;"></i>
                                    Estado: <strong>{{ ucfirst($user->estudiante?->estado ?? ($user->activo ? 'Activo' : 'Inactivo')) }}</strong>
                                </div>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary w-100 py-2 fw-semibold">
                            <i class="bi bi-check2 me-1"></i> Actualizar Teléfono de Contacto
                        </button>
                    @elseif(auth()->user()->isTutor())
                        <!-- Vista Académica Protegida para Docente Tutor -->
                        <div class="mb-3">
                            <label class="form-label fw-semibold small text-muted">Nombre Completo (Registrado)</label>
                            <input type="text" class="form-control bg-light" value="{{ $user->tutor ? ($user->tutor->nombres . ' ' . $user->tutor->apellidos) : $user->name }}" readonly disabled>
                            <div class="form-text text-muted">Solo lectura. Correcciones institucionales corresponden al Administrador.</div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold small text-muted">Correo Institucional UPDS</label>
                            <input type="email" class="form-control bg-light" value="{{ $user->email }}" readonly disabled>
                            <div class="form-text text-muted">Cuenta oficial UPDS.</div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold small text-muted">Especialidad / Área Académica</label>
                            <input type="text" class="form-control bg-light" value="{{ $user->tutor?->especialidad ?? 'Docente Tutor de Prácticas' }}" readonly disabled>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold small text-muted">Teléfono / Celular de Contacto *</label>
                            <input type="text" name="telefono" class="form-control @error('telefono') is-invalid @enderror"
                                   value="{{ old('telefono', $user->tutor?->telefono) }}" placeholder="Ej: 70788990">
                            @error('telefono') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            <div class="form-text text-muted">Información de contacto para estudiantes y coordinación.</div>
                        </div>
                        <div class="mb-4 p-3 rounded-3" style="background:#f8fafc;border:1px solid #e2e8f0;">
                            <div class="row g-2 text-muted small">
                                <div class="col-6">
                                    <i class="bi bi-shield-check text-primary me-1"></i>
                                    <strong>Rol:</strong> Docente Tutor
                                </div>
                                <div class="col-6">
                                    <i class="bi bi-calendar3 text-primary me-1"></i>
                                    <strong>Registrado:</strong> {{ $user->created_at->format('d/m/Y') }}
                                </div>
                                <div class="col-6 mt-1">
                                    <i class="bi bi-person-badge text-primary me-1"></i>
                                    <strong>ID Tutor:</strong> TUT-{{ str_pad($user->tutor?->id ?? 1, 3, '0', STR_PAD_LEFT) }}
                                </div>
                                <div class="col-6 mt-1">
                                    <i class="bi bi-circle-fill me-1 {{ ($user->tutor?->activo ?? $user->activo) ? 'text-success' : 'text-danger' }}" style="font-size:.5rem;"></i>
                                    Estado: <strong>{{ ($user->tutor?->activo ?? $user->activo) ? 'Activo' : 'Inactivo' }}</strong>
                                </div>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary w-100 py-2 fw-semibold">
                            <i class="bi bi-check2 me-1"></i> Actualizar Teléfono de Contacto
                        </button>
                    @elseif(auth()->user()->hasRole('coordinador'))
                        <!-- Vista Académica Protegida para Coordinador Académico -->
                        <div class="mb-3">
                            <label class="form-label fw-semibold small text-muted">Nombre Completo (Registrado)</label>
                            <input type="text" class="form-control bg-light" value="{{ $user->coordinador ? ($user->coordinador->nombres . ' ' . $user->coordinador->apellidos) : $user->name }}" readonly disabled>
                            <div class="form-text text-muted">Solo lectura. Correcciones institucionales corresponden al Administrador del Sistema.</div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold small text-muted">Correo Institucional UPDS</label>
                            <input type="email" class="form-control bg-light" value="{{ $user->email }}" readonly disabled>
                            <div class="form-text text-muted">Cuenta oficial UPDS.</div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold small text-muted">Área Institucional</label>
                            <input type="text" class="form-control bg-light" value="Coordinación Académica de Prácticas Profesionales" readonly disabled>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold small text-muted">Teléfono / Celular de Contacto *</label>
                            <input type="text" name="telefono" class="form-control @error('telefono') is-invalid @enderror"
                                   value="{{ old('telefono', $user->coordinador?->telefono) }}" placeholder="Ej: 70711223">
                            @error('telefono') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            <div class="form-text text-muted">Número de contacto oficial para comunicación académica.</div>
                        </div>
                        <div class="mb-4 p-3 rounded-3" style="background:#f8fafc;border:1px solid #e2e8f0;">
                            <div class="row g-2 text-muted small">
                                <div class="col-6">
                                    <i class="bi bi-shield-check text-primary me-1"></i>
                                    <strong>Rol:</strong> Coordinador Académico
                                </div>
                                <div class="col-6">
                                    <i class="bi bi-calendar3 text-primary me-1"></i>
                                    <strong>Registrado:</strong> {{ $user->created_at->format('d/m/Y') }}
                                </div>
                                <div class="col-6 mt-1">
                                    <i class="bi bi-person-badge text-primary me-1"></i>
                                    <strong>ID Coord:</strong> COORD-{{ str_pad($user->coordinador?->id ?? 1, 3, '0', STR_PAD_LEFT) }}
                                </div>
                                <div class="col-6 mt-1">
                                    <i class="bi bi-circle-fill me-1 {{ ($user->coordinador?->activo ?? $user->activo) ? 'text-success' : 'text-danger' }}" style="font-size:.5rem;"></i>
                                    Estado: <strong>{{ ($user->coordinador?->activo ?? $user->activo) ? 'Activo' : 'Inactivo' }}</strong>
                                </div>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary w-100 py-2 fw-semibold">
                            <i class="bi bi-check2 me-1"></i> Actualizar Teléfono de Contacto
                        </button>
                    @else
                        <!-- Vista Institucional Protegida para Administrador del Sistema -->
                        <div class="mb-3">
                            <label class="form-label fw-semibold small text-muted">Nombre Completo Institucional</label>
                            <input type="text" class="form-control bg-light" value="{{ $user->name }}" readonly disabled>
                            <div class="form-text text-muted">Dato institucional protegido. Las correcciones se realizan con auditoría en Gestión de Usuarios.</div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold small text-muted">Correo Electrónico Institucional</label>
                            <input type="email" class="form-control bg-light" value="{{ $user->email }}" readonly disabled>
                            <div class="form-text text-muted">Cuenta oficial de administración técnica del sistema.</div>
                        </div>
                        <div class="mb-4 p-3 rounded-3" style="background:#f8fafc;border:1px solid #e2e8f0;">
                            <div class="row g-2 text-muted small">
                                <div class="col-6">
                                    <i class="bi bi-shield-lock-fill text-danger me-1"></i>
                                    <strong>Rol:</strong> Administrador del Sistema
                                </div>
                                <div class="col-6">
                                    <i class="bi bi-calendar3 text-primary me-1"></i>
                                    <strong>Registrado:</strong> {{ $user->created_at->format('d/m/Y') }}
                                </div>
                                <div class="col-6 mt-1">
                                    <i class="bi bi-person-badge text-primary me-1"></i>
                                    <strong>ID Admin:</strong> #{{ $user->id }}
                                </div>
                                <div class="col-6 mt-1">
                                    <i class="bi bi-circle-fill me-1 {{ $user->activo ? 'text-success' : 'text-danger' }}" style="font-size:.5rem;"></i>
                                    Estado: <strong>{{ $user->activo ? 'Activo' : 'Inactivo' }}</strong>
                                </div>
                            </div>
                        </div>
                        <div class="alert alert-info py-2 small mb-0">
                            <i class="bi bi-info-circle me-1"></i>
                            Para administrar cuentas, roles institucionales o auditoría, ingresa a <a href="{{ route('admin.usuarios.index') }}" class="alert-link">Gestión de Usuarios</a>.
                        </div>
                    @endif
                </form>
            </div>
        </div>
    </div>

    <!-- Cambiar Contraseña -->
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm">
            <div class="card-header py-3" style="background:#1e293b;border-radius:12px 12px 0 0;">
                <h6 class="mb-0 text-white fw-semibold">
                    <i class="bi bi-key-fill me-2"></i>Cambiar Contraseña
                </h6>
            </div>
            <div class="card-body p-4">
                <form action="{{ route('perfil.password') }}" method="POST">
                    @csrf @method('PUT')
                    <div class="mb-3">
                        <label class="form-label fw-semibold small text-muted">Contraseña Actual</label>
                        <input type="password" name="current_password"
                               class="form-control @error('current_password') is-invalid @enderror"
                               placeholder="••••••••" required>
                        @error('current_password') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold small text-muted">Nueva Contraseña</label>
                        <input type="password" name="password"
                               class="form-control @error('password') is-invalid @enderror"
                               placeholder="Mínimo 8 caracteres" required>
                        @error('password') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="mb-4">
                        <label class="form-label fw-semibold small text-muted">Confirmar Nueva Contraseña</label>
                        <input type="password" name="password_confirmation"
                               class="form-control" placeholder="Repite la nueva contraseña" required>
                    </div>
                    <div class="alert alert-info py-2 px-3 small mb-3" style="border-radius:10px;">
                        <i class="bi bi-info-circle me-1"></i>
                        La contraseña debe tener al menos 8 caracteres.
                    </div>
                    <button type="submit" class="btn btn-dark w-100 py-2 fw-semibold">
                        <i class="bi bi-lock-fill me-1"></i> Cambiar Contraseña
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

