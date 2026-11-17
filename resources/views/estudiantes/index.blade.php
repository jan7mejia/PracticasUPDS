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
        <div class="col-12 col-md-3">
            <label class="form-label small text-muted mb-1 fw-semibold">Código (RU)</label>
            <input type="text" name="codigo" class="form-control form-control-sm" placeholder="Ej. 3200001" value="{{ request('codigo') }}">
        </div>
        <div class="col-12 col-md-3">
            <label class="form-label small text-muted mb-1 fw-semibold">Nombre</label>
            <input type="text" name="nombre" class="form-control form-control-sm" placeholder="Ej. Juan Pérez" value="{{ request('nombre') }}">
        </div>
        <div class="col-12 col-md-3">
            <label class="form-label small text-muted mb-1 fw-semibold">Carrera</label>
            <select name="carrera_id" class="form-select form-select-sm">
                <option value="">Todas las carreras</option>
                @foreach($carreras as $c)
                    <option value="{{ $c->id }}" {{ request('carrera_id') == $c->id ? 'selected' : '' }}>
                        {{ $c->nombre }}
                    </option>
                @endforeach
            </select>
        </div>
        <div class="col-12 col-md-2">
            <label class="form-label small text-muted mb-1 fw-semibold">Estado</label>
            <select name="estado" class="form-select form-select-sm">
                <option value="">Todos</option>
                <option value="activo" {{ request('estado') == 'activo' ? 'selected' : '' }}>Activo</option>
                <option value="inactivo" {{ request('estado') == 'inactivo' ? 'selected' : '' }}>Inactivo</option>
                <option value="egresado" {{ request('estado') == 'egresado' ? 'selected' : '' }}>Egresado</option>
            </select>
        </div>
        <div class="col-12 col-md-1">
            <button type="submit" class="btn btn-sm btn-primary w-100" style="background-color: var(--upds-blue);">
                <i class="bi bi-search"></i> Buscar
            </button>
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

    @if($estudiantes->hasPages())
        <div class="p-3 border-top d-flex justify-content-end">
            {{ $estudiantes->links() }}
        </div>
    @endif
</div>

<!-- Modal Nuevo Estudiante -->
<div class="modal fade" id="modalNuevoEstudiante" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow rounded-4">
            <div class="modal-header text-white" style="background-color: var(--upds-navy);">
                <h5 class="modal-title fw-bold">
                    <i class="bi bi-person-plus-fill me-2"></i>Registrar nuevo estudiante
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('estudiantes.store') }}" method="POST">
                @csrf
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Nombres *</label>
                            <input type="text" name="nombres" class="form-control" required placeholder="Ej. Juan">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Apellidos *</label>
                            <input type="text" name="apellidos" class="form-control" required placeholder="Ej. Pérez Morales">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">RU (Registro Universitario) *</label>
                            <input type="text" name="ru" class="form-control" required placeholder="Ej. 3200456">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Cédula de Identidad (CI)</label>
                            <input type="text" name="ci" class="form-control" placeholder="Ej. 8493021 CB">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Teléfono / WhatsApp</label>
                            <input type="text" name="telefono" class="form-control" placeholder="Ej. 79784512">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Cuenta de usuario estudiante (Opcional)</label>
                            <select name="usuario_id" class="form-select">
                                <option value="">— Sin cuenta vinculada (Admin creará la cuenta) —</option>
                                @foreach($cuentasEstudiantes as $cu)
                                    <option value="{{ $cu->id }}">{{ $cu->name }} ({{ $cu->email }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Carrera *</label>
                            <select name="carrera_id" class="form-select" required>
                                <option value="">Seleccione carrera</option>
                                @foreach($carreras as $c)
                                    <option value="{{ $c->id }}">{{ $c->nombre }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small fw-semibold">Semestre</label>
                            <input type="number" name="semestre" class="form-control" min="1" max="15" value="7">
                        </div>
                    </div>
                    <div class="alert alert-light border mt-3 py-2 px-3 small text-muted">
                        <i class="bi bi-info-circle text-primary me-1"></i> Administración de Ficha Académica. Las credenciales de acceso son administradas por el Administrador General.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-upds">Guardar Estudiante</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

