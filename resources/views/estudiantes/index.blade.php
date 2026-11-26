@extends('layouts.app')

@section('title', 'Gestión de Estudiantes - UPDSonline')

@section('content')
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
    <div>
        <h4 class="fw-bold mb-1 text-navy" style="color: var(--upds-navy);">
            <i class="bi bi-person-lines-fill text-primary me-2"></i>Gestión de estudiantes
        </h4>
        <p class="text-muted small mb-0">Control de expedientes, requisitos cumplidos y estado de prácticas.</p>
    </div>
    <button type="button" class="btn btn-upds" data-bs-toggle="modal" data-bs-target="#modalNuevoEstudiante">
        <i class="bi bi-person-plus-fill me-1"></i> + Nuevo estudiante
    </button>
</div>

<!-- Barra de Filtros (Exacta a la Imagen 3) -->
<div class="card border-0 shadow-sm rounded-4 p-3 mb-4">
    <form action="{{ route('estudiantes.index') }}" method="GET" class="row g-2 align-items-end">
        <div class="col-12 col-md-2">
            <label class="form-label small text-muted mb-1 fw-semibold">Código (RU)</label>
            <input type="text" name="codigo" class="form-control form-control-sm" placeholder="Ej. 3200001" value="{{ request('codigo') }}" data-auto-search autocomplete="off">
        </div>
        <div class="col-12 col-md-3">
            <label class="form-label small text-muted mb-1 fw-semibold">Nombre o apellido</label>
            <input type="text" name="nombre" class="form-control form-control-sm" placeholder="Ej. Juan Pérez" value="{{ request('nombre') }}" data-auto-search autocomplete="off">
        </div>
        <div class="col-12 col-md-3">
            <label class="form-label small text-muted mb-1 fw-semibold">Carrera</label>
            <select name="carrera_id" class="form-select form-select-sm" onchange="this.form.submit()">
                <option value="">Todas las carreras</option>
                @foreach($carreras as $c)
                    <option value="{{ $c->id }}" {{ request('carrera_id') == $c->id ? 'selected' : '' }}>
                        {{ $c->nombre }}
                    </option>
                @endforeach
            </select>
        </div>
        <div class="col-6 col-md-2">
            <label class="form-label small text-muted mb-1 fw-semibold">Estado</label>
            <select name="estado" class="form-select form-select-sm" onchange="this.form.submit()">
                <option value="">Todos</option>
                <option value="activo" {{ request('estado') == 'activo' ? 'selected' : '' }}>Activo</option>
                <option value="inactivo" {{ request('estado') == 'inactivo' ? 'selected' : '' }}>Inactivo</option>
                <option value="egresado" {{ request('estado') == 'egresado' ? 'selected' : '' }}>Egresado</option>
            </select>
        </div>
        <div class="col-6 col-md-2">
            <label class="form-label small text-muted mb-1 fw-semibold">Por pág.</label>
            <select name="per_page" class="form-select form-select-sm" onchange="this.form.submit()">
                <option value="10" {{ request('per_page', 10) == 10 ? 'selected' : '' }}>10 registros</option>
                <option value="25" {{ request('per_page') == 25 ? 'selected' : '' }}>25 registros</option>
                <option value="50" {{ request('per_page') == 50 ? 'selected' : '' }}>50 registros</option>
            </select>
        </div>
    </form>
</div>

<!-- Tabla de Estudiantes -->
<div class="card border-0 shadow-sm rounded-4 overflow-hidden">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr style="font-size: 0.85rem; color: #475569;">
                    <th class="ps-4">Código (RU)</th>
                    <th>Nombre completo</th>
                    <th>Carrera</th>
                    <th>Semestre</th>
                    <th>Estado</th>
                    <th class="text-end pe-4">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse($estudiantes as $est)
                    <tr>
                        <td class="ps-4">
                            <span class="badge bg-light text-dark border fw-bold" style="font-size: 0.82rem;">{{ $est->ru }}</span>
                        </td>
                        <td>
                            <div class="fw-semibold text-dark">{{ $est->nombre_completo }}</div>
                            @if($est->user)
                                <small class="text-muted"><i class="bi bi-person-check text-success me-1"></i>{{ $est->user->email }}</small>
                            @else
                                <span class="badge bg-warning-subtle text-warning border" style="font-size: 0.7rem;">
                                    <i class="bi bi-clock-history me-1"></i>Sin cuenta vinculada
                                </span>
                            @endif
                        </td>
                        <td>
                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle">{{ $est->carrera->nombre }}</span>
                        </td>
                        <td>
                            <span class="text-muted small fw-medium">{{ $est->semestre ? $est->semestre . '° Semestre' : 'N/A' }}</span>
                        </td>
                        <td>
                            @php
                                $estadoClase = match($est->estado) {
                                    'activo' => 'badge-activo',
                                    'egresado' => 'badge-egresado',
                                    default => 'badge-inactivo'
                                };
                            @endphp
                            <span class="{{ $estadoClase }}">{{ ucfirst($est->estado) }}</span>
                        </td>
                        <td class="text-end pe-4">
                            <a href="{{ route('estudiantes.show', $est->id) }}" class="btn btn-sm btn-light border text-primary me-1" title="Ver Ficha y Requisitos">
                                <i class="bi bi-eye"></i>
                            </a>
                            <a href="{{ route('estudiantes.edit', $est->id) }}" class="btn btn-sm btn-light border text-secondary" title="Editar">
                                <i class="bi bi-pencil"></i>
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center py-4 text-muted">
                            <i class="bi bi-search fs-3 d-block mb-2"></i>
                            No se encontraron estudiantes con los criterios indicados.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($estudiantes->hasPages() || $estudiantes->total() > 0)
        <div class="card-footer bg-white border-0 py-3 px-4 d-flex flex-column flex-md-row justify-content-between align-items-center gap-2">
            <span class="small text-muted">
                Mostrando <strong>{{ $estudiantes->firstItem() ?? 0 }}</strong>–<strong>{{ $estudiantes->lastItem() ?? 0 }}</strong> de <strong>{{ $estudiantes->total() }}</strong> estudiantes
            </span>
            <div>
                {{ $estudiantes->links('pagination::bootstrap-5') }}
            </div>
        </div>
    @endif
</div>

<!-- Modal Nuevo Estudiante -->
<div class="modal fade" id="modalNuevoEstudiante" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow rounded-4 overflow-hidden">
            <div class="modal-header text-white" style="background-color: var(--upds-navy);">
                <h5 class="modal-title fw-bold">
                    <i class="bi bi-person-plus-fill me-2"></i>Registrar nuevo estudiante
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('estudiantes.store') }}" method="POST">
                @csrf
                <div class="modal-body p-4">
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Nombres (Solo letras) *</label>
                            <input type="text" name="nombres" id="modalInputNombres" class="form-control" required placeholder="Ej. Juan Carlos"
                                   pattern="[a-zA-ZáéíóúÁÉÍÓÚñÑüÜ\s\.\'\-]+"
                                   title="Solo letras y espacios, no se permiten números"
                                   oninput="this.value = this.value.replace(/[^a-zA-ZáéíóúÁÉÍÓÚñÑüÜ\s\.\'\-]/g, '')">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Apellidos (Solo letras) *</label>
                            <input type="text" name="apellidos" id="modalInputApellidos" class="form-control" required placeholder="Ej. Pérez Morales"
                                   pattern="[a-zA-ZáéíóúÁÉÍÓÚñÑüÜ\s\.\'\-]+"
                                   title="Solo letras y espacios, no se permiten números"
                                   oninput="this.value = this.value.replace(/[^a-zA-ZáéíóúÁÉÍÓÚñÑüÜ\s\.\'\-]/g, '')">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">RU (Solo números) *</label>
                            <input type="text" name="ru" class="form-control" required placeholder="Ej. 3200456"
                                   maxlength="10" pattern="[0-9]{6,10}"
                                   title="Solo dígitos numéricos"
                                   oninput="this.value = this.value.replace(/[^0-9]/g, '')">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Cédula de Identidad (CI)</label>
                            <input type="text" name="ci" class="form-control" placeholder="Ej. 8493021 CB" maxlength="20">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Teléfono / WhatsApp (Solo números)</label>
                            <input type="text" name="telefono" class="form-control" placeholder="Ej. 79784512"
                                   maxlength="15"
                                   title="Número telefónico válido de Bolivia"
                                   oninput="this.value = this.value.replace(/[^0-9\+\-\s\(\)]/g, '')">
                        </div>
                        <div class="col-md-8">
                            <label class="form-label small fw-semibold">Carrera UPDS *</label>
                            <select name="carrera_id" class="form-select" required>
                                <option value="">Seleccione carrera</option>
                                @foreach($carreras as $c)
                                    <option value="{{ $c->id }}">{{ $c->nombre }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Semestre *</label>
                            <select name="semestre" class="form-select">
                                <option value="7" selected>7° Semestre</option>
                                <option value="8">8° Semestre</option>
                                <option value="9">9° Semestre</option>
                                <option value="10">10° Semestre</option>
                            </select>
                        </div>
                    </div>

                    <!-- Sección Cuenta de Acceso Institucional (Habilitado para Coordinación) -->
                    <div class="card border border-primary-subtle rounded-3 p-3 mb-3 bg-light">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <h6 class="fw-bold mb-0 text-navy" style="color: var(--upds-navy);">
                                <i class="bi bi-person-lock text-primary me-2"></i>Cuenta de Acceso Institucional (UPDS)
                            </h6>
                            <span class="badge bg-primary-subtle text-primary border">Habilitación Inmediata</span>
                        </div>
                        <small class="text-muted d-block mb-3">
                            La Coordinación Académica puede crear directamente el correo institucional y contraseña para que el estudiante acceda al sistema sin esperar al Administrador.
                        </small>
                        
                        <div class="mb-3">
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="tipo_cuenta" id="modal_cuenta_nueva" value="nueva" checked onchange="toggleModalTipoCuenta()">
                                <label class="form-check-label small fw-semibold" for="modal_cuenta_nueva">Crear nueva cuenta institucional</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="tipo_cuenta" id="modal_cuenta_existente" value="existente" onchange="toggleModalTipoCuenta()">
                                <label class="form-check-label small fw-semibold" for="modal_cuenta_existente">Vincular a cuenta existente</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="tipo_cuenta" id="modal_sin_cuenta" value="ninguna" onchange="toggleModalTipoCuenta()">
                                <label class="form-check-label small text-muted" for="modal_sin_cuenta">Solo ficha (Sin cuenta)</label>
                            </div>
                        </div>

                        <!-- Campos para nueva cuenta institucional -->
                        <div id="modal_seccion_cuenta_nueva" class="row g-2">
                            <div class="col-md-7">
                                <label class="form-label small fw-semibold">Correo Institucional UPDS *</label>
                                <div class="input-group input-group-sm">
                                    <input type="email" name="email_institucional" id="modal_email_institucional" 
                                           class="form-control" 
                                           value="{{ old('email_institucional') }}" placeholder="ej. juan.perez@upds.edu.bo">
                                    <button type="button" class="btn btn-outline-primary" onclick="generarCorreoModal()" title="Generar sugerencia con nombres y apellidos">
                                        <i class="bi bi-magic me-1"></i>Sugerir
                                    </button>
                                </div>
                                <small class="text-muted" style="font-size: 0.72rem;">Dominio institucional @upds.edu.bo</small>
                            </div>
                            <div class="col-md-5">
                                <label class="form-label small fw-semibold">Contraseña Inicial *</label>
                                <input type="text" name="password_inicial" id="modal_password_inicial" 
                                       class="form-control form-control-sm" 
                                       value="12345678" placeholder="Mínimo 6 caracteres">
                                <small class="text-muted" style="font-size: 0.72rem;">Clave temporal para su primer acceso</small>
                            </div>
                        </div>

                        <!-- Vincular a cuenta existente -->
                        <div id="modal_seccion_cuenta_existente" class="row g-2 d-none">
                            <div class="col-12">
                                <label class="form-label small fw-semibold">Seleccionar Usuario Estudiante Existente *</label>
                                <select name="usuario_id" class="form-select form-select-sm">
                                    <option value="">-- Seleccionar usuario sin ficha académica asignada --</option>
                                    @foreach($cuentasEstudiantes as $cu)
                                        <option value="{{ $cu->id }}">{{ $cu->name }} ({{ $cu->email }})</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Habilitación de Requisitos Académicos -->
                    <div class="p-2 rounded bg-white border">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="cumple_requisitos" value="1" id="estCumpleReqCheck" checked>
                            <label class="form-check-label small fw-semibold text-primary" for="estCumpleReqCheck">
                                <i class="bi bi-patch-check-fill me-1"></i>Habilitar requisitos académicos de práctica
                            </label>
                            <div class="text-muted" style="font-size: 0.73rem;">
                                Valida los 4 requisitos reglamentarios (semestre cursado, materias prerrequisito, ficha) como CUMPLE para que pueda realizar su solicitud de prácticas de inmediato.
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-upds">Guardar y Habilitar Estudiante</button>
                </div>
            </form>
        </div>
    </div>
</div>

@section('scripts')
@parent
<script>
function toggleModalTipoCuenta() {
    var tipoNueva = document.getElementById('modal_cuenta_nueva')?.checked;
    var tipoExistente = document.getElementById('modal_cuenta_existente')?.checked;
    var secNueva = document.getElementById('modal_seccion_cuenta_nueva');
    var secExistente = document.getElementById('modal_seccion_cuenta_existente');

    if (secNueva) {
        if (tipoNueva) secNueva.classList.remove('d-none');
        else secNueva.classList.add('d-none');
    }
    if (secExistente) {
        if (tipoExistente) secExistente.classList.remove('d-none');
        else secExistente.classList.add('d-none');
    }
}

function generarCorreoModal() {
    var nombres = document.getElementById('modalInputNombres')?.value.trim();
    var apellidos = document.getElementById('modalInputApellidos')?.value.trim();
    if (!nombres) {
        alert('Ingresa los nombres del estudiante primero.');
        return;
    }
    var primerNombre = nombres.split(' ')[0].toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '').replace(/[^a-z]/g, '');
    var primerApellido = (apellidos ? apellidos.split(' ')[0] : 'estudiante').toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '').replace(/[^a-z]/g, '');
    var sugerido = primerNombre + '.' + primerApellido + '@upds.edu.bo';
    var inputEmail = document.getElementById('modal_email_institucional');
    if (inputEmail) inputEmail.value = sugerido;
}
</script>
@endsection
@endsection

