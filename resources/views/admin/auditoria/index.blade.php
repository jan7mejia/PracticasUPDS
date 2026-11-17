@extends('layouts.app')

@section('title', 'Registro de Auditoría Administrativa - Admin')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-1" style="color:var(--upds-navy);">
            <i class="bi bi-journal-text me-2 text-danger"></i>Registro de Auditoría Administrativa
        </h4>
        <small class="text-muted">Trazabilidad inmutable de operaciones sensibles realizadas por administradores</small>
    </div>
</div>

<!-- Filtros de Auditoría -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body p-3">
        <form method="GET" action="{{ route('admin.auditoria.index') }}" class="row g-2 align-items-end">
            <div class="col-md-5">
                <label class="form-label small fw-semibold text-muted mb-1">Buscar por administrador, usuario afectado o motivo</label>
                <input type="text" name="q" class="form-control form-control-sm" placeholder="Texto de búsqueda..." value="{{ request('q') }}">
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-semibold text-muted mb-1">Tipo de acción</label>
                <select name="accion" class="form-select form-select-sm">
                    <option value="">— Todas las acciones —</option>
                    @foreach($acciones as $acc)
                        <option value="{{ $acc }}" {{ request('accion') == $acc ? 'selected' : '' }}>{{ $acc }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-auto">
                <button type="submit" class="btn btn-outline-primary btn-sm px-3">
                    <i class="bi bi-search me-1"></i> Filtrar
                </button>
                <a href="{{ route('admin.auditoria.index') }}" class="btn btn-outline-secondary btn-sm px-3 ms-1">
                    <i class="bi bi-x"></i> Limpiar
                </a>
            </div>
        </form>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead style="background:#f8fafc;border-bottom:2px solid #e2e8f0;">
                    <tr>
                        <th class="ps-4 py-3 text-muted small fw-semibold">FECHA / HORA</th>
                        <th class="py-3 text-muted small fw-semibold">ADMINISTRADOR</th>
                        <th class="py-3 text-muted small fw-semibold">ACCIÓN</th>
                        <th class="py-3 text-muted small fw-semibold">USUARIO AFECTADO</th>
                        <th class="py-3 text-muted small fw-semibold">MOTIVO</th>
                        <th class="py-3 text-muted small fw-semibold">IP</th>
                        <th class="py-3 text-muted small fw-semibold text-end pe-4">DETALLES</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($registros as $reg)
                    <tr>
                        <td class="ps-4 text-muted small">{{ $reg->created_at->format('d/m/Y H:i:s') }}</td>
                        <td>
                            <div class="fw-semibold text-dark">{{ $reg->admin->name ?? 'Admin #' . $reg->admin_id }}</div>
                            <small class="text-muted" style="font-size: 0.72rem;">{{ $reg->admin->email ?? '' }}</small>
                        </td>
                        <td>
                            @php
                                $badgeClass = match($reg->accion) {
                                    'CREACION_USUARIO' => 'bg-success text-white',
                                    'CAMBIO_ROL' => 'bg-warning text-dark',
                                    'ACTIVACION_CUENTA' => 'bg-primary text-white',
                                    'DESACTIVACION_CUENTA' => 'bg-danger text-white',
                                    'RESTABLECIMIENTO_ACCESO' => 'bg-info text-dark',
                                    default => 'bg-secondary text-white',
                                };
                            @endphp
                            <span class="badge {{ $badgeClass }} rounded-pill">{{ $reg->accion }}</span>
                        </td>
                        <td>
                            @if($reg->usuarioAfectado)
                                <span class="fw-semibold">{{ $reg->usuarioAfectado->name }}</span>
                                <small class="d-block text-muted" style="font-size: 0.72rem;">{{ $reg->usuarioAfectado->email }}</small>
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                        <td class="text-truncate" style="max-width: 220px;" title="{{ $reg->motivo }}">
                            {{ $reg->motivo ?: 'Sin motivo registrado' }}
                        </td>
                        <td class="text-muted small"><code>{{ $reg->ip_address ?: '127.0.0.1' }}</code></td>
                        <td class="text-end pe-4">
                            @if($reg->detalles)
                                <button type="button" class="btn btn-sm btn-outline-secondary p-1 px-2" data-bs-toggle="modal" data-bs-target="#modalDetalleAudit{{ $reg->id }}" title="Ver detalles técnicos">
                                    <i class="bi bi-info-circle"></i>
                                </button>
                            @else
                                <span class="text-muted" style="font-size: 0.75rem;">—</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="text-center py-5 text-muted">
                            <i class="bi bi-journal-x fs-1 d-block mb-2 opacity-25"></i>
                            No se encontraron registros de auditoría.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($registros->hasPages())
        <div class="px-4 py-3 border-top">
            {{ $registros->links() }}
        </div>
        @endif
    </div>
</div>

@foreach($registros as $reg)
    @if($reg->detalles)
        <div class="modal fade" id="modalDetalleAudit{{ $reg->id }}" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content border-0 shadow rounded-4">
                    <div class="modal-header bg-light">
                        <h6 class="modal-title fw-bold"><i class="bi bi-code-slash me-2"></i>Detalle de Operación #{{ $reg->id }}</h6>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="small mb-2"><strong>Acción:</strong> {{ $reg->accion }}</div>
                        <div class="small mb-2"><strong>Fecha:</strong> {{ $reg->created_at->format('d/m/Y H:i:s') }}</div>
                        <div class="small mb-3"><strong>Motivo:</strong> {{ $reg->motivo }}</div>
                        <label class="form-label small fw-semibold">Carga de Datos / Cambios:</label>
                        <pre class="bg-light p-3 rounded-3 small border mb-0" style="max-height: 250px; overflow-y: auto;">{{ $reg->detalles }}</pre>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                    </div>
                </div>
            </div>
        </div>
    @endif
@endforeach
@endsection

