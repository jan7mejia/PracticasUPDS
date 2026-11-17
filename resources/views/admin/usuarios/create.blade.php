@extends('layouts.app')

@section('title', 'Nuevo Usuario - Admin')

@section('content')
<div class="mb-4">
    <a href="{{ route('admin.usuarios.index') }}" class="btn btn-sm btn-outline-secondary mb-3">
        <i class="bi bi-arrow-left me-1"></i> Volver a Usuarios
    </a>
    <h4 class="fw-bold mb-1" style="color:var(--upds-navy);">
        <i class="bi bi-person-plus-fill me-2"></i>Crear Nuevo Usuario
    </h4>
</div>

<div class="row justify-content-center">
    <div class="col-lg-7">
        <div class="card border-0 shadow-sm">
            <div class="card-header py-3" style="background:var(--upds-primary);border-radius:12px 12px 0 0;">
                <h6 class="mb-0 text-white fw-semibold"><i class="bi bi-person-fill me-2"></i>Datos del Usuario</h6>
            </div>
            <div class="card-body p-4">
                <form action="{{ route('admin.usuarios.store') }}" method="POST">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label fw-semibold small">Nombre Completo *</label>
                        <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                               value="{{ old('name') }}" placeholder="Ej: Juan Pérez Morales" required>
                        @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold small">Correo Electrónico *</label>
                        <input type="email" name="email" class="form-control @error('email') is-invalid @enderror"
                               value="{{ old('email') }}" placeholder="usuario@upds.edu.bo" required>
                        @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold small">Rol Institucional *</label>
                        <select name="rol_id" id="rolSelect" class="form-select @error('rol_id') is-invalid @enderror" required onchange="toggleAcademicLink(this.value)">
                            <option value="">— Seleccionar Rol —</option>
                            @foreach($roles as $rol)
                                <option value="{{ $rol->id }}" data-codigo="{{ $rol->codigo }}" {{ old('rol_id') == $rol->id ? 'selected' : '' }}>
                                    {{ $rol->nombre }} ({{ $rol->codigo }})
                                </option>
                            @endforeach
                        </select>
                        @error('rol_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <!-- Vinculación Ficha Estudiantil -->
                    <div id="seccionEstudiante" class="p-3 bg-light rounded-3 border mb-3" style="display: {{ old('rol_id') == 4 ? 'block' : 'none' }};">
                        <label class="form-label fw-semibold small text-primary">
                            <i class="bi bi-link-45deg me-1"></i>Vincular a Ficha Académica Existente (Opcional)
                        </label>
                        <select name="estudiante_id" class="form-select form-select-sm">
                            <option value="">-- Dejar pendiente de vinculación por Coordinación --</option>
                            @foreach($estudiantesSinCuenta as $esc)
                                <option value="{{ $esc->id }}" {{ old('estudiante_id') == $esc->id ? 'selected' : '' }}>
                                    {{ $esc->nombre_completo }} (RU: {{ $esc->ru }} &bull; {{ $esc->carrera->nombre ?? '' }})
                                </option>
                            @endforeach
                        </select>
                        <small class="text-muted d-block mt-1" style="font-size: 0.75rem;">
                            <i class="bi bi-info-circle me-1"></i>Si todavía no existe una ficha académica registrada para el alumno, la coordinación la creará posteriormente con su carrera y RU institucional.
                        </small>
                    </div>

                    <!-- Vinculación Perfil Docente Tutor -->
                    <div id="seccionTutor" class="p-3 bg-light rounded-3 border mb-3" style="display: {{ old('rol_id') == 3 ? 'block' : 'none' }};">
                        <label class="form-label fw-semibold small text-info">
                            <i class="bi bi-person-badge me-1"></i>Vincular a Ficha Docente Tutor Existente (Opcional)
                        </label>
                        <select name="tutor_id" class="form-select form-select-sm">
                            <option value="">-- Crear nueva ficha de Docente Tutor automáticamente --</option>
                            @foreach($tutoresSinCuenta as $tsc)
                                <option value="{{ $tsc->id }}" {{ old('tutor_id') == $tsc->id ? 'selected' : '' }}>
                                    {{ $tsc->nombre_completo }} ({{ $tsc->especialidad }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold small">Contraseña *</label>
                        <input type="password" name="password"
                               class="form-control @error('password') is-invalid @enderror"
                               placeholder="Mínimo 8 caracteres" required>
                        @error('password') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="mb-4">
                        <label class="form-label fw-semibold small">Confirmar Contraseña *</label>
                        <input type="password" name="password_confirmation" class="form-control"
                               placeholder="Repite la contraseña" required>
                    </div>
                    <div class="mb-4">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="activo" value="1"
                                   id="activoCheck" {{ old('activo', '1') ? 'checked' : '' }}>
                            <label class="form-check-label fw-semibold" for="activoCheck">Usuario Activo</label>
                        </div>
                    </div>
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary py-2 fw-semibold">
                            <i class="bi bi-person-check-fill me-1"></i> Crear Usuario
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<script>
function toggleAcademicLink(rolId) {
    const secEst = document.getElementById('seccionEstudiante');
    const secTut = document.getElementById('seccionTutor');
    if (secEst) secEst.style.display = (rolId == '4') ? 'block' : 'none';
    if (secTut) secTut.style.display = (rolId == '3') ? 'block' : 'none';
}
</script>
@endsection

