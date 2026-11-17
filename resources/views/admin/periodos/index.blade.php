@extends('layouts.app')

@section('title', 'Períodos Académicos - Admin')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-1" style="color:var(--upds-navy);">
            <i class="bi bi-calendar3 me-2 text-info"></i>Períodos Académicos
        </h4>
        <small class="text-muted">Gestiona los períodos y semestres académicos vigentes</small>
    </div>
    <a href="{{ route('admin.periodos.create') }}" class="btn btn-primary fw-semibold">
        <i class="bi bi-plus-circle-fill me-1"></i> Nuevo Período
    </a>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead style="background:#f8fafc;border-bottom:2px solid #e2e8f0;">
                    <tr>
                        <th class="ps-4 py-3 text-muted small fw-semibold">PERÍODO</th>
                        <th class="py-3 text-muted small fw-semibold">CÓDIGO</th>
                        <th class="py-3 text-muted small fw-semibold">INICIO</th>
                        <th class="py-3 text-muted small fw-semibold">FIN</th>
                        <th class="py-3 text-muted small fw-semibold">ESTADO</th>
                        <th class="py-3 text-muted small fw-semibold text-end pe-4">ACCIONES</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($periodos as $p)
                    <tr>
                        <td class="ps-4 fw-semibold">{{ $p->nombre }}</td>
                        <td><code>{{ $p->codigo }}</code></td>
                        <td class="text-muted small">{{ \Carbon\Carbon::parse($p->fecha_inicio)->format('d/m/Y') }}</td>
                        <td class="text-muted small">{{ \Carbon\Carbon::parse($p->fecha_fin)->format('d/m/Y') }}</td>
                        <td>
                            @if($p->activo)
                                <span class="badge text-bg-success rounded-pill">
                                    <i class="bi bi-circle-fill me-1" style="font-size:.5rem;"></i>Activo
                                </span>
                            @else
                                <span class="badge text-bg-secondary rounded-pill">Inactivo</span>
                            @endif
                        </td>
                        <td class="text-end pe-4">
                            <div class="d-flex justify-content-end gap-1">
                                <a href="{{ route('admin.periodos.edit', $p) }}" class="btn btn-sm btn-outline-primary" title="Editar Período">
                                    <i class="bi bi-pencil-fill"></i>
                                </a>
                                <button type="button" class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#modalEliminarPeriodo{{ $p->id }}" title="Eliminar">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="text-center py-5 text-muted">
                            <i class="bi bi-calendar-x fs-1 d-block mb-2 opacity-25"></i>
                            No hay períodos académicos registrados.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($periodos->hasPages())
        <div class="px-4 py-3 border-top">
            {{ $periodos->links() }}
        </div>
        @endif
    </div>
</div>

@foreach($periodos as $p)
    <!-- Modal Eliminar Período -->
    <div class="modal fade" id="modalEliminarPeriodo{{ $p->id }}" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow rounded-4">
                <div class="modal-header bg-danger text-white">
                    <h6 class="modal-title fw-bold"><i class="bi bi-trash me-2"></i>Eliminar Período Académico</h6>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form action="{{ route('admin.periodos.destroy', $p) }}" method="POST">
                    @csrf
                    @method('DELETE')
                    <div class="modal-body">
                        <p class="small text-muted mb-2">
                            Período: <strong>{{ $p->nombre }}</strong> (<code>{{ $p->codigo }}</code>)
                        </p>
                        <div class="alert alert-warning py-2 small mb-0">
                            <i class="bi bi-exclamation-triangle me-1"></i>
                            Si este período cuenta con solicitudes o prácticas profesionales asociadas, el sistema bloqueará la eliminación para proteger el historial académico.
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-sm btn-light" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-sm btn-danger fw-semibold">Confirmar Eliminación</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endforeach
@endsection

