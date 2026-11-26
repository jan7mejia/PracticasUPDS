@extends('layouts.app')

@section('title', 'Editar Usuario - Admin')

@section('content')
<div class="mb-4">
    <a href="{{ route('admin.usuarios.index') }}" class="btn btn-sm btn-outline-secondary mb-3">
        <i class="bi bi-arrow-left me-1"></i> Volver a Usuarios
    </a>
    <h4 class="fw-bold mb-1" style="color:var(--upds-navy);">
        <i class="bi bi-pencil-square me-2"></i>Editar Usuario
    </h4>
    <small class="text-muted">{{ $usuario->email }}</small>
</div>

<div class="row g-4">
    <div class="col-lg-7">
        <div class="card border-0 shadow-sm">
            <div class="card-header py-3" style="background:var(--upds-primary);border-radius:12px 12px 0 0;">
                <h6 class="mb-0 text-white fw-semibold"><i class="bi bi-person-fill me-2"></i>Datos de la Cuenta</h6>
            </div>
            <div class="card-body p-4">
                <form action="{{ route('admin.usuarios.update', $usuario) }}" method="POST">
                    @csrf @method('PUT')
                    <div class="mb-3">
                        <label class="form-label fw-semibold small">Nombre Completo *</label>
                        <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                               value="{{ old('name', $usuario->name) }}" required
                               pattern="[a-zA-ZáéíóúÁÉÍÓÚñÑüÜ\s\.\'\-]+"
                               title="Solo letras y espacios, no se permiten números"
                               oninput="this.value = this.value.replace(/[0-9]/g, '')">
                        @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        <small class="text-muted d-block mt-1" style="font-size: 0.75rem;">
                            <i class="bi bi-info-circle me-1"></i>Solo se admiten caracteres alfabéticos (sin números).
                        </small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold small">Correo Electrónico *</label>
                        <input type="email" name="email" class="form-control @error('email') is-invalid @enderror"
                               value="{{ old('email', $usuario->email) }}" required>
                        @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold small">Rol *</label>
                        <select name="rol_id" class="form-select @error('rol_id') is-invalid @enderror" required>
                            @foreach($roles as $rol)
                                <option value="{{ $rol->id }}" {{ old('rol_id', $usuario->rol_id) == $rol->id ? 'selected' : '' }}>
                                    {{ $rol->nombre }} ({{ $rol->codigo }})
                                </option>
                            @endforeach
                        </select>
                        @error('rol_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold small">Motivo de la modificación (Auditoría institucional)</label>
                        <input type="text" name="motivo" class="form-control form-control-sm"
                               placeholder="Ej: Corrección ortográfica / Cambio de rol autorizado por dirección...">
                    </div>

                    <hr class="my-3">
                    <p class="small text-muted fw-semibold mb-2">
                        <i class="bi bi-lock me-1"></i>Cambiar Contraseña (dejar en blanco para mantener la actual)
                    </p>
                    <div class="mb-3">
                        <input type="password" name="password"
                               class="form-control @error('password') is-invalid @enderror"
                               placeholder="Nueva contraseña (mínimo 8 caracteres)">
                        @error('password') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="mb-4">
                        <input type="password" name="password_confirmation"
                               class="form-control" placeholder="Confirmar nueva contraseña">
                    </div>

                    <div class="mb-4">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="activo" value="1"
                                   id="activoCheck" {{ old('activo', $usuario->activo) ? 'checked' : '' }}>
                            <label class="form-check-label fw-semibold" for="activoCheck">Usuario Activo</label>
                        </div>
                    </div>
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary py-2 fw-semibold">
                            <i class="bi bi-check2 me-1"></i> Guardar Cambios
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Panel Lateral: Expediente Académico y Auditoría -->
    <div class="col-lg-5">
        <!-- Vínculo Académico -->
        <div class="card border-0 shadow-sm rounded-4 p-4 mb-4">
            <h6 class="fw-bold mb-3 text-navy" style="color: var(--upds-navy);">
                <i class="bi bi-mortarboard text-primary me-2"></i>Expediente Vinculado
            </h6>
            @if($usuario->rol_id == 4 && $usuario->estudiante)
                <div class="p-3 bg-light rounded-3 small">
                    <div><strong>Estudiante:</strong> {{ $usuario->estudiante->nombre_completo }}</div>
                    <div><strong>RU:</strong> <code>{{ $usuario->estudiante->ru }}</code></div>
                    <div><strong>Carrera:</strong> {{ $usuario->estudiante->carrera->nombre ?? 'Sin carrera' }}</div>
                    <div><strong>Semestre:</strong> {{ $usuario->estudiante->semestre }}°</div>
                </div>
            @elseif($usuario->rol_id == 3 && $usuario->tutor)
                <div class="p-3 bg-light rounded-3 small">
                    <div><strong>Docente Tutor:</strong> {{ $usuario->tutor->nombre_completo }}</div>
                    <div><strong>Especialidad:</strong> {{ $usuario->tutor->especialidad }}</div>
                    <div><strong>Prácticas Asignadas:</strong> {{ $usuario->tutor->practicas()->count() }}</div>
                </div>
            @elseif($usuario->rol_id == 2 && $usuario->coordinador)
                <div class="p-3 bg-light rounded-3 small">
                    <div><strong>Coordinador Académico:</strong> {{ $usuario->coordinador->nombre_completo }}</div>
                    <div><strong>Teléfono:</strong> {{ $usuario->coordinador->telefono ?: 'No registrado' }}</div>
                </div>
            @else
                <div class="text-muted small py-2">
                    Cuenta administrativa o sin ficha académica vinculada.
                </div>
            @endif
        </div>

        <!-- Historial de Modificaciones de esta cuenta -->
        <div class="card border-0 shadow-sm rounded-4 p-4">
            <h6 class="fw-bold mb-3 text-navy" style="color: var(--upds-navy);">
                <i class="bi bi-clock-history text-secondary me-2"></i>Historial de Cambios
            </h6>
            <div class="list-group list-group-flush small">
                @forelse($historialAuditoria as $hAud)
                    <div class="list-group-item px-0 py-2">
                        <div class="d-flex justify-content-between text-muted" style="font-size: 0.72rem;">
                            <span>{{ $hAud->created_at->format('d/m/Y H:i') }}</span>
                            <span class="badge bg-light text-dark border">{{ $hAud->accion }}</span>
                        </div>
                        <div class="text-dark fw-semibold mt-1">{{ $hAud->motivo }}</div>
                        <div class="text-secondary" style="font-size: 0.75rem;">Por: {{ $hAud->admin->name ?? 'Admin' }}</div>
                    </div>
                @empty
                    <div class="text-muted text-center py-3">No hay registros de modificaciones para esta cuenta.</div>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection

