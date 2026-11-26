@extends('layouts.app')

@section('title', 'Organizaciones Receptoras - UPDSonline')

@section('content')
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
    <div>
        <h4 class="fw-bold mb-1 text-navy" style="color: var(--upds-navy);">
            <i class="bi bi-buildings text-primary me-2"></i>Organizaciones receptoras
        </h4>
        <p class="text-muted small mb-0">Directorio de empresas, instituciones públicas y ONGs vinculadas.</p>
    </div>
    @if(!auth()->user()->isEstudiante() && !auth()->user()->isTutor())
    <button type="button" class="btn btn-upds" data-bs-toggle="modal" data-bs-target="#modalNuevaOrg">
        <i class="bi bi-plus-lg me-1"></i> + Registrar organización
    </button>
    @endif
</div>

<!-- Estructura de 2 Columnas idéntica a la Imagen 4 -->
<div class="row g-4">
    <!-- Columna Izquierda: Buscador y Lista de Empresas -->
    <div class="col-12 col-lg-5">
        <div class="card border-0 shadow-sm rounded-4 p-3 h-100">
            <!-- Barra de búsqueda con auto-búsqueda inteligente -->
            <form action="{{ route('organizaciones.index') }}" method="GET" class="mb-3">
                <div class="row g-2">
                    <div class="col-8">
                        <div class="input-group input-group-sm">
                            <span class="input-group-text bg-light border-end-0"><i class="bi bi-search text-muted"></i></span>
                            <input type="text" name="buscar" class="form-control bg-light border-start-0" 
                                   placeholder="Buscar organización o rubro..." value="{{ $search }}" data-auto-search autocomplete="off">
                            @if($search)
                                <a href="{{ route('organizaciones.index') }}" class="btn btn-outline-secondary btn-sm" title="Limpiar"><i class="bi bi-x-lg"></i></a>
                            @endif
                        </div>
                    </div>
                    <div class="col-4">
                        <select name="per_page" class="form-select form-select-sm" onchange="this.form.submit()">
                            <option value="10" {{ request('per_page', 10) == 10 ? 'selected' : '' }}>10 / pág</option>
                            <option value="25" {{ request('per_page') == 25 ? 'selected' : '' }}>25 / pág</option>
                            <option value="50" {{ request('per_page') == 50 ? 'selected' : '' }}>50 / pág</option>
                        </select>
                    </div>
                </div>
            </form>

            <!-- Lista interactiva -->
            <div class="list-group list-group-flush rounded-3 overflow-hidden">
                @forelse($organizaciones as $org)
                    <a href="{{ route('organizaciones.index', ['selected' => $org->id, 'buscar' => $search, 'per_page' => request('per_page', 10)]) }}" 
                       class="list-group-item list-group-item-action d-flex align-items-center justify-content-between p-3 {{ $selectedOrg && $selectedOrg->id == $org->id ? 'active' : '' }}"
                       style="{{ $selectedOrg && $selectedOrg->id == $org->id ? 'background-color: #e0f2fe; border-color: #bae6fd; color: #0284c7;' : '' }}">
                        <div class="d-flex align-items-center gap-3">
                            <div class="rounded-3 p-2 d-flex align-items-center justify-content-center" 
                                 style="width: 38px; height: 38px; background: {{ $selectedOrg && $selectedOrg->id == $org->id ? '#0284c7' : '#f1f5f9' }}; color: {{ $selectedOrg && $selectedOrg->id == $org->id ? '#ffffff' : '#003b73' }};">
                                <i class="bi bi-building"></i>
                            </div>
                            <div>
                                <div class="fw-bold" style="font-size: 0.93rem; color: {{ $selectedOrg && $selectedOrg->id == $org->id ? '#0369a1' : '#1e293b' }};">
                                    {{ $org->nombre }}
                                </div>
                                <small class="{{ $selectedOrg && $selectedOrg->id == $org->id ? 'text-primary' : 'text-muted' }}" style="font-size: 0.78rem;">
                                    {{ $org->rubro ?? 'Sin rubro especificado' }}
                                </small>
                            </div>
                        </div>
                        <i class="bi bi-chevron-right fs-6 {{ $selectedOrg && $selectedOrg->id == $org->id ? 'text-primary' : 'text-muted' }}"></i>
                    </a>
                @empty
                    <div class="text-center py-4 text-muted">
                        No se encontraron organizaciones.
                    </div>
                @endforelse
            </div>

            <!-- Paginación de Organizaciones -->
            @if($organizaciones->hasPages() || $organizaciones->total() > 0)
                <div class="border-top pt-3 mt-3 d-flex flex-column justify-content-between align-items-center gap-2">
                    <span class="small text-muted" style="font-size: 0.78rem;">
                        Mostrando <strong>{{ $organizaciones->firstItem() ?? 0 }}</strong>–<strong>{{ $organizaciones->lastItem() ?? 0 }}</strong> de <strong>{{ $organizaciones->total() }}</strong> empresas
                    </span>
                    <div>
                        {{ $organizaciones->links('pagination::bootstrap-5') }}
                    </div>
                </div>
            @endif
        </div>
    </div>

    <!-- Columna Derecha: Tarjeta de Detalle de la Organización -->
    <div class="col-12 col-lg-7">
        @if($selectedOrg)
            <div class="card border-0 shadow-sm rounded-4 p-4">
                <div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-start gap-2 mb-4 pb-3 border-bottom">
                    <div>
                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill px-3 py-1 mb-2">
                            <i class="bi bi-briefcase me-1"></i> Organización Receptora
                        </span>
                        <h4 class="fw-bold text-navy mb-1" style="color: var(--upds-navy);">{{ $selectedOrg->nombre }}</h4>
                        <span class="text-muted small"><i class="bi bi-tag me-1"></i> {{ $selectedOrg->rubro ?? 'Sector Empresarial / Institucional' }}</span>
                    </div>
                    <div>
                        <span class="{{ $selectedOrg->estado === 'activa' ? 'badge-activo' : 'badge-inactiva' }} fs-6 px-3 py-2">
                            <i class="bi bi-check-circle me-1"></i> {{ ucfirst($selectedOrg->estado) }}
                        </span>
                    </div>
                </div>

                {{-- Tarjeta especial de acción para el Estudiante --}}
                @if(auth()->user()->isEstudiante())
                    <div class="p-3 mb-4 rounded-4 text-white d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-3"
                         style="background: linear-gradient(135deg, var(--upds-navy) 0%, var(--upds-primary) 100%);">
                        <div>
                            <div class="fw-bold fs-6 mb-1">
                                <i class="bi bi-stars text-warning me-1"></i> ¿Deseas realizar tus prácticas aquí?
                            </div>
                            <div class="small opacity-75">
                                Inicia tu postulación formal vinculando tus datos a esta institución.
                            </div>
                        </div>
                        <a href="{{ route('solicitudes.create', ['organizacion_id' => $selectedOrg->id]) }}" 
                           class="btn btn-warning text-dark fw-bold rounded-pill px-4 shadow-sm text-nowrap">
                            <i class="bi bi-send-fill me-1"></i> Postular / Iniciar Solicitud Aquí
                        </a>
                    </div>
                @endif

                <h6 class="fw-bold text-muted text-uppercase mb-3" style="font-size: 0.8rem; letter-spacing: 0.5px;">
                    <i class="bi bi-info-circle me-1"></i> Información Institucional y Contacto
                </h6>

                <div class="row g-3 mb-4">
                    <div class="col-sm-6">
                        <div class="p-3 bg-light rounded-3 border h-100">
                            <small class="text-muted d-block mb-1">NIT / Registro Fiscal:</small>
                            <span class="fw-semibold text-dark">{{ $selectedOrg->nit ?? 'No registrado' }}</span>
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div class="p-3 bg-light rounded-3 border h-100">
                            <small class="text-muted d-block mb-1">Sector o Rubro:</small>
                            <span class="fw-semibold text-dark">{{ $selectedOrg->rubro ?? 'General' }}</span>
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="p-3 bg-light rounded-3 border">
                            <small class="text-muted d-block mb-1"><i class="bi bi-geo-alt text-primary me-1"></i> Dirección:</small>
                            <span class="fw-semibold text-dark">{{ $selectedOrg->direccion ?? 'Dirección central no especificada' }}</span>
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div class="p-3 bg-light rounded-3 border h-100">
                            <small class="text-muted d-block mb-1"><i class="bi bi-person-badge text-primary me-1"></i> Responsable Institucional:</small>
                            <span class="fw-semibold text-dark">
                                {{ $selectedOrg->responsables->first()?->nombre_completo ?? 'Responsable de RRHH' }}
                            </span>
                            @if($selectedOrg->responsables->first()?->cargo)
                                <small class="text-muted d-block">Cargo: {{ $selectedOrg->responsables->first()->cargo }}</small>
                            @endif
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div class="p-3 bg-light rounded-3 border h-100">
                            <small class="text-muted d-block mb-1"><i class="bi bi-telephone text-primary me-1"></i> Teléfono / Celular:</small>
                            <span class="fw-semibold text-dark">{{ $selectedOrg->telefono ?? 'Sin teléfono registrado' }}</span>
                            <small class="text-muted d-block mt-1"><i class="bi bi-envelope text-primary me-1"></i> {{ $selectedOrg->correo ?? 'Sin correo' }}</small>
                        </div>
                    </div>
                </div>

                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 pt-3 border-top">
                    <div class="d-flex gap-2">
                        @if(!auth()->user()->isEstudiante() && !auth()->user()->isTutor())
                        <a href="{{ route('organizaciones.edit', $selectedOrg->id) }}" class="btn btn-outline-primary btn-sm rounded-pill px-3">
                            <i class="bi bi-pencil me-1"></i> Editar datos
                        </a>
                        @endif
                        <a href="{{ route('solicitudes.index') }}" class="btn btn-outline-secondary btn-sm rounded-pill px-3">
                            <i class="bi bi-journal-text me-1"></i> Solicitudes del sistema
                        </a>
                    </div>
                    <small class="text-muted">Registro institucional UPDS</small>
                </div>
            </div>
        @else
            <div class="card border-0 shadow-sm rounded-4 p-5 text-center text-muted">
                <i class="bi bi-building-slash fs-1 opacity-50 mb-2"></i>
                <h5 class="fw-bold text-dark mb-1">No se encontraron organizaciones</h5>
                <p class="small text-muted mb-3">
                    @if($search)
                        No existen organizaciones ni empresas que coincidan con "<strong>{{ $search }}</strong>".
                    @else
                        Selecciona una organización receptora de la lista o registra una nueva.
                    @endif
                </p>
                @if($search)
                    <div>
                        <a href="{{ route('organizaciones.index') }}" class="btn btn-sm btn-outline-primary rounded-pill px-4">
                            <i class="bi bi-arrow-counterclockwise me-1"></i> Restaurar catálogo completo
                        </a>
                    </div>
                @endif
            </div>
        @endif
    </div>
</div>

<!-- Modal Nueva Organización -->
<div class="modal fade" id="modalNuevaOrg" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow rounded-4">
            <div class="modal-header text-white" style="background-color: var(--upds-navy);">
                <h5 class="modal-title fw-bold">
                    <i class="bi bi-building-add me-2"></i>Registrar Organización Receptora
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('organizaciones.store') }}" method="POST">
                @csrf
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-md-8">
                            <label class="form-label small fw-semibold">Nombre o Razón Social *</label>
                            <input type="text" name="nombre" class="form-control" required placeholder="Ej. Empresa ABC S.R.L.">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">NIT (Solo números)</label>
                            <input type="text" name="nit" class="form-control" placeholder="Ej. 1234567890123"
                                   maxlength="15" pattern="[0-9]{7,15}"
                                   title="Solo dígitos numéricos"
                                   oninput="this.value = this.value.replace(/[^0-9]/g, '')">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Rubro o Actividad</label>
                            <input type="text" name="rubro" class="form-control" placeholder="Ej. Tecnología / Banca / Salud"
                                   oninput="this.value = this.value.replace(/[^a-zA-ZáéíóúÁÉÍÓÚñÑüÜ\s\/\,\.\-]/g, '')">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Dirección</label>
                            <input type="text" name="direccion" class="form-control" placeholder="Av. Principal N° 123">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Teléfono de la Empresa (Solo números)</label>
                            <input type="text" name="telefono" class="form-control" placeholder="Ej. 4412345 ó 77012345"
                                   maxlength="15"
                                   title="Número telefónico válido de Bolivia"
                                   oninput="this.value = this.value.replace(/[^0-9\+\-\s\(\)]/g, '')">
                            <small class="text-muted" style="font-size: 0.72rem;">Fijo Cochabamba (4XXXXXX) o Celular Bolivia (6/7XXXXXXX)</small>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Correo Electrónico Institucional</label>
                            <input type="email" name="correo" class="form-control" placeholder="contacto@empresa.com">
                        </div>

                        <div class="col-12 mt-3 pt-3 border-top">
                            <h6 class="fw-bold text-primary small text-uppercase">Datos del Responsable de Contacto (Tutor Empresarial)</h6>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Nombres (Solo letras)</label>
                            <input type="text" name="responsable_nombres" class="form-control" placeholder="Ej. Carlos"
                                   pattern="[a-zA-ZáéíóúÁÉÍÓÚñÑüÜ\s\.\'\-]+"
                                   title="Solo letras y espacios, no se permiten números"
                                   oninput="this.value = this.value.replace(/[^a-zA-ZáéíóúÁÉÍÓÚñÑüÜ\s\.\'\-]/g, '')">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Apellidos (Solo letras)</label>
                            <input type="text" name="responsable_apellidos" class="form-control" placeholder="Ej. Méndez"
                                   pattern="[a-zA-ZáéíóúÁÉÍÓÚñÑüÜ\s\.\'\-]+"
                                   title="Solo letras y espacios, no se permiten números"
                                   oninput="this.value = this.value.replace(/[^a-zA-ZáéíóúÁÉÍÓÚñÑüÜ\s\.\'\-]/g, '')">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Cargo</label>
                            <input type="text" name="responsable_cargo" class="form-control" placeholder="Ej. Gerente de Tecnología"
                                   oninput="this.value = this.value.replace(/[^a-zA-ZáéíóúÁÉÍÓÚñÑüÜ\s\.\,\/\-]/g, '')">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Teléfono Directo (Solo números)</label>
                            <input type="text" name="responsable_telefono" class="form-control" placeholder="Ej. 77012345"
                                   maxlength="15"
                                   title="Número telefónico válido de Bolivia"
                                   oninput="this.value = this.value.replace(/[^0-9\+\-\s\(\)]/g, '')">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Correo del Responsable</label>
                            <input type="email" name="responsable_correo" class="form-control" placeholder="cmendez@empresa.com">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-upds">Guardar Organización</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

