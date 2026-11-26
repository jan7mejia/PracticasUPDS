@extends('layouts.app')

@section('title', 'Nuevo Estudiante - UPDSonline')

@section('content')
<div class="mb-4">
    <a href="{{ route('estudiantes.index') }}" class="btn btn-sm btn-outline-secondary mb-3">
        <i class="bi bi-arrow-left me-1"></i> Volver a Estudiantes
    </a>
    <h4 class="fw-bold mb-1" style="color:var(--upds-navy);">
        <i class="bi bi-person-plus-fill me-2 text-primary"></i>Registrar Nuevo Estudiante
    </h4>
    <small class="text-muted">Crea el perfil del estudiante y su cuenta de acceso al sistema con asignación automática de requisitos académicos.</small>
</div>

<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-header py-3" style="background:var(--upds-primary);border-radius:16px 16px 0 0;">
                <h6 class="mb-0 text-white fw-semibold"><i class="bi bi-person-badge me-2"></i>Datos Personales y Académicos</h6>
            </div>
            <div class="card-body p-4">
                <form action="{{ route('estudiantes.store') }}" method="POST">
                    @csrf
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small">Nombres *</label>
                            <input type="text" name="nombres" class="form-control @error('nombres') is-invalid @enderror"
                                   value="{{ old('nombres') }}" placeholder="Ej: Ana María" required
                                   pattern="[a-zA-ZáéíóúÁÉÍÓÚñÑüÜ\s\.\'\-]+"
                                   title="Solo letras y espacios, no se permiten números"
                                   oninput="this.value = this.value.replace(/[0-9]/g, '')">
                            @error('nombres') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small">Apellidos *</label>
                            <input type="text" name="apellidos" class="form-control @error('apellidos') is-invalid @enderror"
                                   value="{{ old('apellidos') }}" placeholder="Ej: Gómez Rojas" required
                                   pattern="[a-zA-ZáéíóúÁÉÍÓÚñÑüÜ\s\.\'\-]+]+"
                                   title="Solo letras y espacios, no se permiten números"
                                   oninput="this.value = this.value.replace(/[0-9]/g, '')">
                            @error('apellidos') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <label class="form-label fw-semibold small">Registro Universitario (RU) *</label>
                            <input type="text" name="ru" class="form-control @error('ru') is-invalid @enderror"
                                   value="{{ old('ru') }}" placeholder="Ej: 3200480" required maxlength="10"
                                   pattern="[0-9]{6,10}" title="Solo dígitos numéricos"
                                   oninput="this.value = this.value.replace(/[^0-9]/g, '')">
                            @error('ru') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold small">Cédula de Identidad (CI)</label>
                            <input type="text" name="ci" class="form-control @error('ci') is-invalid @enderror"
                                   value="{{ old('ci') }}" placeholder="Ej: 8945123 CB" maxlength="20">
                            @error('ci') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold small">Teléfono / Celular (Cochabamba)</label>
                            <input type="text" name="telefono" class="form-control @error('telefono') is-invalid @enderror"
                                   value="{{ old('telefono') }}" placeholder="Ej: 71234567 o 4412345" maxlength="20">
                            @error('telefono') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>

                    <!-- Sección Cuenta de Acceso Institucional (Habilitado para Coordinación) -->
                    <div class="card border border-primary-subtle rounded-3 p-3 mb-3 bg-light">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <h6 class="fw-bold mb-0 text-navy" style="color: var(--upds-navy);">
                                <i class="bi bi-person-lock text-primary me-2"></i>Cuenta de Acceso Institucional (UPDS)
                            </h6>
                            <span class="badge bg-primary-subtle text-primary border">Coordinación Académica</span>
                        </div>
                        <small class="text-muted d-block mb-3">La coordinación académica puede habilitar directamente la cuenta institucional del estudiante sin depender del administrador.</small>
                        
                        <div class="mb-3">
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="tipo_cuenta" id="cuenta_nueva" value="nueva" checked onchange="toggleTipoCuenta()">
                                <label class="form-check-label small fw-semibold" for="cuenta_nueva">Crear nueva cuenta institucional</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="tipo_cuenta" id="cuenta_existente" value="existente" onchange="toggleTipoCuenta()">
                                <label class="form-check-label small fw-semibold" for="cuenta_existente">Vincular a cuenta existente</label>
                            </div>
                        </div>

                        <!-- Campos para nueva cuenta institucional -->
                        <div id="seccion_cuenta_nueva" class="row g-2">
                            <div class="col-md-7">
                                <label class="form-label small fw-semibold">Correo Institucional UPDS *</label>
                                <div class="input-group input-group-sm">
                                    <input type="email" name="email_institucional" id="email_institucional" 
                                           class="form-control @error('email_institucional') is-invalid @enderror" 
                                           value="{{ old('email_institucional') }}" placeholder="ej. ana.gomez@upds.edu.bo">
                                    <button type="button" class="btn btn-outline-primary" onclick="generarCorreoSugerido()" title="Generar sugerencia con nombres y apellidos">
                                        <i class="bi bi-magic me-1"></i>Sugerir correo
                                    </button>
                                </div>
                                @error('email_institucional') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-5">
                                <label class="form-label small fw-semibold">Contraseña Inicial *</label>
                                <input type="text" name="password_inicial" class="form-control form-control-sm @error('password_inicial') is-invalid @enderror" 
                                       value="{{ old('password_inicial', '12345678') }}" required>
                                <small class="text-muted">Por defecto: 12345678</small>
                                @error('password_inicial') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                            </div>
                        </div>

                        <!-- Selector de cuenta existente -->
                        <div id="seccion_cuenta_existente" class="d-none mt-2">
                            <label class="form-label small fw-semibold">Seleccionar cuenta de usuario:</label>
                            <select name="usuario_id" class="form-select form-select-sm @error('usuario_id') is-invalid @enderror">
                                <option value="">— Seleccionar usuario registrado previamente —</option>
                                @foreach($cuentasEstudiantes as $cu)
                                    <option value="{{ $cu->id }}" {{ old('usuario_id') == $cu->id ? 'selected' : '' }}>
                                        {{ $cu->name }} ({{ $cu->email }})
                                    </option>
                                @endforeach
                            </select>
                            @error('usuario_id') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-12">
                            <label class="form-label fw-semibold small">Teléfono / Celular de Contacto</label>
                            <input type="text" name="telefono" class="form-control @error('telefono') is-invalid @enderror"
                                   value="{{ old('telefono') }}" placeholder="Ej: 77912345">
                            @error('telefono') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>

                    <div class="row g-3 mb-4">
                        <div class="col-md-8">
                            <label class="form-label fw-semibold small">Carrera Universitaria *</label>
                            <select name="carrera_id" class="form-select @error('carrera_id') is-invalid @enderror" required>
                                <option value="">— Seleccionar carrera —</option>
                                @foreach($carreras as $c)
                                    <option value="{{ $c->id }}" {{ old('carrera_id') == $c->id ? 'selected' : '' }}>
                                        {{ $c->nombre }}
                                    </option>
                                @endforeach
                            </select>
                            @error('carrera_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold small">Semestre Actual</label>
                            <select name="semestre" class="form-select @error('semestre') is-invalid @enderror">
                                <option value="">— Semestre —</option>
                                @for($i = 1; $i <= 10; $i++)
                                    <option value="{{ $i }}" {{ old('semestre') == $i ? 'selected' : '' }}>{{ $i }}° Semestre</option>
                                @endfor
                            </select>
                            @error('semestre') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>

                    <div class="d-flex justify-content-end gap-2 pt-2 border-top">
                        <a href="{{ route('estudiantes.index') }}" class="btn btn-outline-secondary px-4">Cancelar</a>
                        <button type="submit" class="btn btn-primary px-4 fw-semibold">
                            <i class="bi bi-check-circle me-1"></i> Registrar Estudiante
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
function toggleTipoCuenta() {
    var esNueva = document.getElementById('cuenta_nueva').checked;
    var divNueva = document.getElementById('seccion_cuenta_nueva');
    var divExistente = document.getElementById('seccion_cuenta_existente');
    var inputEmail = document.getElementById('email_institucional');

    if (esNueva) {
        divNueva.classList.remove('d-none');
        divExistente.classList.add('d-none');
        if (inputEmail) inputEmail.setAttribute('required', 'required');
    } else {
        divNueva.classList.add('d-none');
        divExistente.classList.remove('d-none');
        if (inputEmail) inputEmail.removeAttribute('required');
    }
}

function generarCorreoSugerido() {
    var nombres = document.querySelector('input[name="nombres"]').value.trim().toLowerCase();
    var apellidos = document.querySelector('input[name="apellidos"]').value.trim().toLowerCase();

    if (!nombres || !apellidos) {
        alert('Por favor ingresa primero los nombres y apellidos del estudiante.');
        return;
    }

    // Tomar primer nombre y primer apellido
    var pNombre = nombres.split(/\s+/)[0].normalize("NFD").replace(/[\u0300-\u036f]/g, "");
    var pApellido = apellidos.split(/\s+/)[0].normalize("NFD").replace(/[\u0300-\u036f]/g, "");
    
    // Quitar caracteres no alfanuméricos
    pNombre = pNombre.replace(/[^a-z0-9]/g, '');
    pApellido = pApellido.replace(/[^a-z0-9]/g, '');

    var emailSugerido = pNombre + '.' + pApellido + '@upds.edu.bo';
    document.getElementById('email_institucional').value = emailSugerido;
}
</script>
@endsection

