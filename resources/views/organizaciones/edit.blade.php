@extends('layouts.app')

@section('title', 'Editar Organización - UPDSonline')

@section('content')
<div class="mb-4">
    <a href="{{ route('organizaciones.index') }}" class="text-decoration-none text-muted small">
        <i class="bi bi-arrow-left"></i> Volver a organizaciones
    </a>
    <h4 class="fw-bold text-navy mt-2" style="color: var(--upds-navy);">
        Editar Organización: {{ $organizacion->nombre }}
    </h4>
</div>

<div class="card border-0 shadow-sm rounded-4 p-4" style="max-width: 800px;">
    <form action="{{ route('organizaciones.update', $organizacion->id) }}" method="POST">
        @csrf
        @method('PUT')
        <div class="row g-3">
            <div class="col-md-8">
                <label class="form-label small fw-semibold">Nombre o Razón Social *</label>
                <input type="text" name="nombre" class="form-control" value="{{ old('nombre', $organizacion->nombre) }}" required>
            </div>
            <div class="col-md-4">
                <label class="form-label small fw-semibold">NIT</label>
                <input type="text" name="nit" class="form-control" value="{{ old('nit', $organizacion->nit) }}">
            </div>
            <div class="col-md-6">
                <label class="form-label small fw-semibold">Rubro</label>
                <input type="text" name="rubro" class="form-control" value="{{ old('rubro', $organizacion->rubro) }}">
            </div>
            <div class="col-md-6">
                <label class="form-label small fw-semibold">Estado</label>
                <select name="estado" class="form-select" required>
                    <option value="activa" {{ $organizacion->estado === 'activa' ? 'selected' : '' }}>Activa</option>
                    <option value="inactiva" {{ $organizacion->estado === 'inactiva' ? 'selected' : '' }}>Inactiva</option>
                </select>
            </div>
            <div class="col-12">
                <label class="form-label small fw-semibold">Dirección</label>
                <input type="text" name="direccion" class="form-control" value="{{ old('direccion', $organizacion->direccion) }}">
            </div>
            <div class="col-md-6">
                <label class="form-label small fw-semibold">Teléfono</label>
                <input type="text" name="telefono" class="form-control" value="{{ old('telefono', $organizacion->telefono) }}">
            </div>
            <div class="col-md-6">
                <label class="form-label small fw-semibold">Correo Electrónico</label>
                <input type="email" name="correo" class="form-control" value="{{ old('correo', $organizacion->correo) }}">
            </div>
        </div>

        <div class="mt-4 pt-3 border-top d-flex justify-content-end gap-2">
            <a href="{{ route('organizaciones.index') }}" class="btn btn-light">Cancelar</a>
            <button type="submit" class="btn btn-upds">Actualizar Organización</button>
        </div>
    </form>
</div>

<!-- Responsables y Contactos Institucionales -->
<div class="card border-0 shadow-sm rounded-4 p-4 mt-4" style="max-width: 800px;">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h5 class="fw-bold text-navy mb-0" style="color: var(--upds-navy);">
                <i class="bi bi-people text-primary me-2"></i>Contactos y Responsables Institucionales
            </h5>
            <small class="text-muted">Personas de contacto autorizadas de la empresa o institución receptora.</small>
        </div>
        <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#modalNuevoContacto">
            <i class="bi bi-plus-lg me-1"></i> + Agregar Contacto
        </button>
    </div>

    <div class="table-responsive">
        <table class="table align-middle table-hover small">
            <thead class="table-light">
                <tr>
                    <th>Nombre</th>
                    <th>Cargo</th>
                    <th>Teléfono</th>
                    <th>Correo</th>
                    <th>Estado</th>
                    <th class="text-end">Acción</th>
                </tr>
            </thead>
            <tbody>
                @forelse($organizacion->responsables as $resp)
                    <tr>
                        <td class="fw-semibold text-dark">{{ $resp->nombre_completo }}</td>
                        <td>{{ $resp->cargo ?? '-' }}</td>
                        <td>{{ $resp->telefono ?? '-' }}</td>
                        <td>{{ $resp->correo ?? '-' }}</td>
                        <td>
                            @if($resp->activo)
                                <span class="badge bg-success-subtle text-success border border-success-subtle">Activo</span>
                            @else
                                <span class="badge bg-secondary-subtle text-secondary border">Inactivo</span>
                            @endif
                        </td>
                        <td class="text-end">
                            <button type="button" class="btn btn-xs btn-outline-primary py-1 px-2" data-bs-toggle="modal" data-bs-target="#modalEditarContacto{{ $resp->id }}">
                                <i class="bi bi-pencil me-1"></i> Editar
                            </button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center py-4 text-muted">No hay contactos registrados para esta organización aún.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Nuevo Contacto -->
<div class="modal fade" id="modalNuevoContacto" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow rounded-4">
            <div class="modal-header text-white" style="background-color: var(--upds-navy);">
                <h6 class="modal-title fw-bold">Agregar Contacto Responsable</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('organizaciones.responsables.store', $organizacion->id) }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label small fw-semibold">Nombres *</label>
                            <input type="text" name="nombres" class="form-control" required placeholder="Ej. Roberto">
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-semibold">Apellidos</label>
                            <input type="text" name="apellidos" class="form-control" placeholder="Ej. Soliz">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Cargo / Área</label>
                        <input type="text" name="cargo" class="form-control" placeholder="Ej. Gerente de RRHH / Jefe de Sistemas">
                    </div>
                    <div class="row g-2">
                        <div class="col-6">
                            <label class="form-label small fw-semibold">Teléfono</label>
                            <input type="text" name="telefono" class="form-control" placeholder="Ej. 70712345">
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-semibold">Correo</label>
                            <input type="email" name="correo" class="form-control" placeholder="contacto@empresa.com">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-sm btn-light" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-sm btn-upds">Guardar Contacto</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modales Editar Contacto -->
@foreach($organizacion->responsables as $resp)
    <div class="modal fade" id="modalEditarContacto{{ $resp->id }}" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow rounded-4">
                <div class="modal-header text-white" style="background-color: var(--upds-navy);">
                    <h6 class="modal-title fw-bold">Editar Contacto Responsable</h6>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form action="{{ route('organizaciones.responsables.update', $resp->id) }}" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="modal-body">
                        <div class="row g-2 mb-3">
                            <div class="col-6">
                                <label class="form-label small fw-semibold">Nombres *</label>
                                <input type="text" name="nombres" class="form-control" value="{{ $resp->nombres }}" required>
                            </div>
                            <div class="col-6">
                                <label class="form-label small fw-semibold">Apellidos</label>
                                <input type="text" name="apellidos" class="form-control" value="{{ $resp->apellidos }}">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Cargo / Área</label>
                            <input type="text" name="cargo" class="form-control" value="{{ $resp->cargo }}">
                        </div>
                        <div class="row g-2 mb-3">
                            <div class="col-6">
                                <label class="form-label small fw-semibold">Teléfono</label>
                                <input type="text" name="telefono" class="form-control" value="{{ $resp->telefono }}">
                            </div>
                            <div class="col-6">
                                <label class="form-label small fw-semibold">Correo</label>
                                <input type="email" name="correo" class="form-control" value="{{ $resp->correo }}">
                            </div>
                        </div>
                        <div class="mb-2">
                            <label class="form-label small fw-semibold">Estado del Contacto</label>
                            <select name="activo" class="form-select">
                                <option value="1" {{ $resp->activo ? 'selected' : '' }}>Activo</option>
                                <option value="0" {{ !$resp->activo ? 'selected' : '' }}>Inactivo</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-sm btn-light" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-sm btn-upds">Actualizar Contacto</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endforeach
@endsection

