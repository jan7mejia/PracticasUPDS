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
            <!-- Barra de búsqueda -->
            <form action="{{ route('organizaciones.index') }}" method="GET" class="mb-3">
                <div class="input-group">
                    <input type="text" name="buscar" class="form-control form-control-sm" placeholder="Buscar organización..." value="{{ $search }}">
                    <button class="btn btn-sm btn-outline-secondary" type="submit">
                        <i class="bi bi-search"></i>
                    </button>
                </div>
            </form>

            <!-- Lista interactiva -->
            <div class="list-group list-group-flush rounded-3 overflow-hidden">
                @forelse($organizaciones as $org)
                    <a href="{{ route('organizaciones.index', ['selected' => $org->id, 'buscar' => $search]) }}" 
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
        </div>
    </div>

    <!-- Columna Derecha: Tarjeta de Detalle de la Organización -->
    <div class="col-12 col-lg-7">
        @if($selectedOrg)
            <div class="card border-0 shadow-sm rounded-4 p-4">
                <div class="d-flex justify-content-between align-items-start mb-4 pb-2 border-bottom">
                    <div>
                        <h4 class="fw-bold text-navy mb-1" style="color: var(--upds-navy);">{{ $selectedOrg->nombre }}</h4>
                        <span class="text-muted small"><i class="bi bi-tag me-1"></i> {{ $selectedOrg->rubro }}</span>
                    </div>
                    <span class="{{ $selectedOrg->estado === 'activa' ? 'badge-activo' : 'badge-inactiva' }}">
                        {{ ucfirst($selectedOrg->estado) }}
                    </span>
                </div>

                <h6 class="fw-bold text-muted text-uppercase mb-3" style="font-size: 0.8rem; letter-spacing: 0.5px;">
                    Datos de la organización
                </h6>

                <div class="row g-3 mb-4">
                    <div class="col-sm-6">
                        <div class="text-muted small">NIT / RUC:</div>
                        <div class="fw-semibold text-dark">{{ $selectedOrg->nit ?? 'No registrado' }}</div>
                    </div>
                    <div class="col-sm-6">
                        <div class="text-muted small">Sector / Rubro:</div>
                        <div class="fw-semibold text-dark">{{ $selectedOrg->rubro ?? 'No especificado' }}</div>
                    </div>
                    <div class="col-12">
                        <div class="text-muted small">Dirección:</div>
                        <div class="fw-semibold text-dark">{{ $selectedOrg->direccion ?? 'No registrada' }}</div>
                    </div>
                    <div class="col-sm-6">
                        <div class="text-muted small">Responsable de Contacto:</div>
                        <div class="fw-semibold text-dark">
                            {{ $selectedOrg->responsables->first()?->nombre_completo ?? 'No asignado' }}
                            @if($selectedOrg->responsables->first()?->cargo)
                                <small class="text-muted d-block">({{ $selectedOrg->responsables->first()->cargo }})</small>
                            @endif
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div class="text-muted small">Teléfono / Celular:</div>
                        <div class="fw-semibold text-dark">{{ $selectedOrg->telefono ?? 'No registrado' }}</div>
                    </div>
                    <div class="col-12">
                        <div class="text-muted small">Correo Electrónico:</div>
                        <div class="fw-semibold text-dark">{{ $selectedOrg->correo ?? 'No registrado' }}</div>
                    </div>
                </div>

                <div class="d-flex flex-wrap gap-2 pt-3 border-top">
                    @if(!auth()->user()->isEstudiante() && !auth()->user()->isTutor())
                    <a href="{{ route('organizaciones.edit', $selectedOrg->id) }}" class="btn btn-outline-primary btn-sm px-3">
                        <i class="bi bi-pencil me-1"></i> Editar datos
                    </a>
                    @endif
                    @if(auth()->user()->isEstudiante())
                    <a href="{{ route('solicitudes.create', ['organizacion_id' => $selectedOrg->id]) }}" class="btn btn-primary btn-sm px-3">
                        <i class="bi bi-file-earmark-plus me-1"></i> Postular / Iniciar Solicitud Aquí
                    </a>
                    @endif
                    <a href="{{ route('solicitudes.index') }}" class="btn btn-outline-secondary btn-sm px-3">
                        <i class="bi bi-journal-check me-1"></i> Ver solicitudes vinculadas
                    </a>
                </div>
            </div>
        @else
            <div class="card border-0 shadow-sm rounded-4 p-5 text-center text-muted">
                <i class="bi bi-building-slash fs-1 opacity-50 mb-2"></i>
                <p>Selecciona una organización receptora de la lista o registra una nueva.</p>
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
                            <label class="form-label small fw-semibold">NIT</label>
                            <input type="text" name="nit" class="form-control" placeholder="Ej. 1234567890123">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Rubro o Actividad</label>
                            <input type="text" name="rubro" class="form-control" placeholder="Ej. Tecnología / Banca / Salud">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Dirección</label>
                            <input type="text" name="direccion" class="form-control" placeholder="Av. Principal N° 123">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Teléfono de la Empresa</label>
                            <input type="text" name="telefono" class="form-control" placeholder="77012345">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Correo Electrónico Institucional</label>
                            <input type="email" name="correo" class="form-control" placeholder="contacto@empresa.com">
                        </div>

                        <div class="col-12 mt-3 pt-3 border-top">
                            <h6 class="fw-bold text-primary small text-uppercase">Datos del Responsable de Contacto (Tutor Empresarial)</h6>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Nombres</label>
                            <input type="text" name="responsable_nombres" class="form-control" placeholder="Ej. Carlos">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Apellidos</label>
                            <input type="text" name="responsable_apellidos" class="form-control" placeholder="Ej. Méndez">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Cargo</label>
                            <input type="text" name="responsable_cargo" class="form-control" placeholder="Ej. Gerente de Tecnología">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Teléfono Directo</label>
                            <input type="text" name="responsable_telefono" class="form-control" placeholder="Ej. 77012345">
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

