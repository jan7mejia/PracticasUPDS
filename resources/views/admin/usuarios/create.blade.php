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
                               value="{{ old('name') }}" placeholder="Ej: Juan Pérez Morales" required
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

                    <!-- Datos Académicos del Estudiante (Creación y vinculación directa por Admin) -->
                    <div id="seccionEstudiante" class="p-3 rounded-3 border mb-3" style="background:#f0f7ff; border-color:#cfe2ff !important; display: {{ old('rol_id') == 4 ? 'block' : 'none' }};">
                        <div class="d-flex align-items-center mb-2">
                            <i class="bi bi-mortarboard-fill text-primary me-2 fs-5"></i>
                            <h6 class="mb-0 fw-bold text-primary">Ficha Académica del Estudiante</h6>
                        </div>
                        <p class="text-muted small mb-3" style="font-size: 0.8rem;">
                            Puedes asignar directamente la carrera y semestre del estudiante para que su cuenta quede operativa y pueda tramitar sus prácticas de inmediato.
                        </p>

                        <div class="row g-2 mb-2">
                            <div class="col-md-7">
                                <label class="form-label fw-semibold small">Carrera UPDS</label>
                                <select name="carrera_id" class="form-select form-select-sm">
                                    @foreach($carreras as $c)
                                        <option value="{{ $c->id }}" {{ old('carrera_id', 1) == $c->id ? 'selected' : '' }}>
                                            {{ $c->nombre }}
                                        </option>
                                    @endforeach
                                    <option value="" {{ old('carrera_id') === '' ? 'selected' : '' }}>-- Omitir por ahora (Dejar que Coordinación asigne carrera y ficha) --</option>
                                </select>
                            </div>
                            <div class="col-md-5">
                                <label class="form-label fw-semibold small">Semestre *</label>
                                <select name="semestre" class="form-select form-select-sm">
                                    <option value="7" {{ old('semestre', '7') == '7' ? 'selected' : '' }}>7° Semestre</option>
                                    <option value="8" {{ old('semestre') == '8' ? 'selected' : '' }}>8° Semestre</option>
                                    <option value="9" {{ old('semestre') == '9' ? 'selected' : '' }}>9° Semestre</option>
                                    <option value="10" {{ old('semestre') == '10' ? 'selected' : '' }}>10° Semestre</option>
                                </select>
                            </div>
                        </div>

                        <div class="row g-2 mb-2">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold small">RU (Registro Universitario)</label>
                                <input type="text" name="ru" class="form-control form-control-sm @error('ru') is-invalid @enderror"
                                       value="{{ old('ru') }}" placeholder="Ej: 3200550 (Autogenerar si se deja vacío)"
                                       maxlength="10" pattern="[0-9]{6,10}"
                                       title="El RU debe contener únicamente números"
                                       oninput="this.value = this.value.replace(/[^0-9]/g, '')">
                                @error('ru') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold small">Cédula de Identidad (CI)</label>
                                <input type="text" name="ci" class="form-control form-control-sm @error('ci') is-invalid @enderror"
                                       value="{{ old('ci') }}" placeholder="Ej: 8945210 o 8945210-1C" maxlength="20">
                                @error('ci') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>

                        <div class="p-2 rounded bg-white border mb-2">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="cumple_requisitos" value="1" id="cumpleReqCheck" checked>
                                <label class="form-check-label small fw-semibold text-primary" for="cumpleReqCheck">
                                    <i class="bi bi-patch-check-fill me-1"></i>Habilitar requisitos académicos de prácticas
                                </label>
                                <div class="text-muted" style="font-size: 0.73rem;">
                                    Marca los requisitos académicos (7° semestre cursado, materias prerrequisito) como cumplidos para que pueda iniciar sus prácticas sin trabas.
                                </div>
                            </div>
                        </div>

                        @if($estudiantesSinCuenta->isNotEmpty())
                            <hr class="my-2 text-muted">
                            <div class="mt-2">
                                <label class="form-label fw-semibold small text-secondary">
                                    <i class="bi bi-link-45deg me-1"></i>O vincular a expediente ya existente sin usuario:
                                </label>
                                <select name="estudiante_id" class="form-select form-select-sm">
                                    <option value="">-- No vincular, crear expediente nuevo con los datos de arriba --</option>
                                    @foreach($estudiantesSinCuenta as $esc)
                                        <option value="{{ $esc->id }}" {{ old('estudiante_id') == $esc->id ? 'selected' : '' }}>
                                            {{ $esc->nombre_completo }} (RU: {{ $esc->ru }} &bull; {{ $esc->carrera->nombre ?? '' }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        @endif
                    </div>

                    <!-- Datos Académicos del Docente Tutor -->
                    <div id="seccionTutor" class="p-3 rounded-3 border mb-3" style="background:#f4fbf7; border-color:#d1e7dd !important; display: {{ old('rol_id') == 3 ? 'block' : 'none' }};">
                        <div class="d-flex align-items-center mb-2">
                            <i class="bi bi-person-badge-fill text-success me-2 fs-5"></i>
                            <h6 class="mb-0 fw-bold text-success">Perfil del Docente Tutor</h6>
                        </div>
                        <div class="mb-2">
                            <label class="form-label fw-semibold small">Especialidad / Área Académica</label>
                            <input type="text" name="especialidad" class="form-control form-control-sm"
                                   value="{{ old('especialidad', 'Desarrollo de Software y Gestión TI') }}"
                                   placeholder="Ej: Desarrollo de Software, Redes, Auditoría, etc.">
                        </div>

                        @if($tutoresSinCuenta->isNotEmpty())
                            <div class="mt-2">
                                <label class="form-label fw-semibold small text-secondary">
                                    <i class="bi bi-link-45deg me-1"></i>O vincular a docente registrado sin cuenta:
                                </label>
                                <select name="tutor_id" class="form-select form-select-sm">
                                    <option value="">-- Crear nuevo perfil docente con la especialidad indicada --</option>
                                    @foreach($tutoresSinCuenta as $tsc)
                                        <option value="{{ $tsc->id }}" {{ old('tutor_id') == $tsc->id ? 'selected' : '' }}>
                                            {{ $tsc->nombre_completo }} ({{ $tsc->especialidad }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        @endif
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

document.addEventListener('DOMContentLoaded', function() {
    const sel = document.getElementById('rolSelect');
    if (sel && sel.value) {
        toggleAcademicLink(sel.value);
    }
});
</script>
@endsection

