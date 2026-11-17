@extends('layouts.app')

@section('title', 'Catálogo de Carreras Universitarias - Admin')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-1" style="color:var(--upds-navy);">
            <i class="bi bi-journal-bookmark me-2 text-primary"></i>Catálogo de Carreras Universitarias
        </h4>
        <small class="text-muted">Administración técnica del catálogo de carreras de UPDS Cochabamba</small>
    </div>
    <button type="button" class="btn btn-primary fw-semibold" data-bs-toggle="modal" data-bs-target="#modalNuevaCarrera">
        <i class="bi bi-plus-circle-fill me-1"></i> Nueva Carrera
    </button>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead style="background:#f8fafc;border-bottom:2px solid #e2e8f0;">
                    <tr>
                        <th class="ps-4 py-3 text-muted small fw-semibold">#</th>
                        <th class="py-3 text-muted small fw-semibold">CARRERA UNIVERSITARIA</th>
                        <th class="py-3 text-muted small fw-semibold">CÓDIGO</th>
                        <th class="py-3 text-muted small fw-semibold">ESTUDIANTES ASOCIADOS</th>
                        <th class="py-3 text-muted small fw-semibold text-end pe-4">ACCIONES</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($carreras as $c)
                    <tr>
                        <td class="ps-4 text-muted small">{{ $c->id }}</td>
                        <td class="fw-bold text-dark">{{ $c->nombre }}</td>
                        <td><code>{{ $c->codigo ?: 'SIN-CODIGO' }}</code></td>
                        <td>
                            <span class="badge bg-primary-subtle text-primary border rounded-pill px-3">
                                {{ $c->estudiantes_count }} alumnos
                            </span>
                        </td>
                        <td class="text-end pe-4">
                            <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#modalEditarCarrera{{ $c->id }}" title="Editar Carrera">
                                <i class="bi bi-pencil-fill"></i> Editar
                            </button>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="text-center py-5 text-muted">
                            <i class="bi bi-journal-x fs-1 d-block mb-2 opacity-25"></i>
                            No hay carreras registradas en el catálogo.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal Nueva Carrera -->
<div class="modal fade" id="modalNuevaCarrera" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow rounded-4">
            <div class="modal-header bg-primary text-white">
                <h6 class="modal-title fw-bold"><i class="bi bi-journal-plus me-2"></i>Registrar Carrera Universitaria</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('admin.carreras.store') }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Nombre Oficial de la Carrera *</label>
                        <input type="text" name="nombre" class="form-control" required placeholder="Ej: Ingeniería Comercial">
                    </div>
                    <div class="mb-2">
                        <label class="form-label small fw-semibold">Código o Sigla (Opcional)</label>
                        <input type="text" name="codigo" class="form-control" placeholder="Ej: ING-COM" style="text-transform:uppercase">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-sm btn-light" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-sm btn-primary fw-semibold">Guardar Carrera</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modales Editar Carrera -->
@foreach($carreras as $c)
    <div class="modal fade" id="modalEditarCarrera{{ $c->id }}" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow rounded-4">
                <div class="modal-header bg-primary text-white">
                    <h6 class="modal-title fw-bold"><i class="bi bi-pencil-square me-2"></i>Editar Carrera</h6>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form action="{{ route('admin.carreras.update', $c) }}" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Nombre Oficial *</label>
                            <input type="text" name="nombre" class="form-control" value="{{ $c->nombre }}" required>
                        </div>
                        <div class="mb-2">
                            <label class="form-label small fw-semibold">Código o Sigla</label>
                            <input type="text" name="codigo" class="form-control" value="{{ $c->codigo }}" style="text-transform:uppercase">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-sm btn-light" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-sm btn-primary fw-semibold">Guardar Cambios</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endforeach
@endsection

