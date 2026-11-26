@extends('layouts.app')

@section('title', 'Registro de Auditoría Administrativa - Admin')

@section('content')
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
    <div>
        <h4 class="fw-bold mb-1" style="color:var(--upds-navy);">
            <i class="bi bi-shield-check me-2 text-primary"></i>Registro de Auditoría Administrativa
        </h4>
        <small class="text-muted">Trazabilidad inmutable de operaciones sensibles realizadas por administradores</small>
    </div>
    <div class="d-flex align-items-center gap-2">
        <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-3 py-2 rounded-pill">
            <i class="bi bi-activity me-1"></i> {{ $registros->total() }} eventos registrados
        </span>
    </div>
</div>

<!-- Filtros de Auditoría -->
<div class="card border-0 shadow-sm rounded-4 mb-4">
    <div class="card-body p-3">
        <form method="GET" action="{{ route('admin.auditoria.index') }}" class="row g-2 align-items-end">
            <div class="col-md-5">
                <label class="form-label small fw-semibold text-muted mb-1">Buscar por administrador, usuario afectado o motivo</label>
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-search"></i></span>
                    <input type="text" name="q" class="form-control form-control-sm border-start-0" placeholder="Buscar por texto o nombre..." value="{{ request('q') }}" data-auto-search autocomplete="off">
                </div>
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-semibold text-muted mb-1">Tipo de acción</label>
                <select name="accion" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">— Todas las acciones —</option>
                    @foreach($acciones as $acc)
                        <option value="{{ $acc }}" {{ request('accion') == $acc ? 'selected' : '' }}>{{ $acc }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small fw-semibold text-muted mb-1">Por página</label>
                <select name="per_page" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="10" {{ request('per_page', $perPage) == 10 ? 'selected' : '' }}>10 registros</option>
                    <option value="25" {{ request('per_page', $perPage) == 25 ? 'selected' : '' }}>25 registros</option>
                    <option value="50" {{ request('per_page', $perPage) == 50 ? 'selected' : '' }}>50 registros</option>
                </select>
            </div>
            <div class="col-md-2 text-end">
                <div class="d-flex gap-1 justify-content-end">
                    <button type="submit" class="btn btn-primary btn-sm px-3 rounded-pill">
                        <i class="bi bi-filter me-1"></i> Filtrar
                    </button>
                    <a href="{{ route('admin.auditoria.index') }}" class="btn btn-outline-secondary btn-sm px-2 rounded-pill" title="Limpiar filtros">
                        <i class="bi bi-x-lg"></i>
                    </a>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Tabla de Registros -->
<div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4">
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
                        <th class="py-3 text-muted small fw-semibold">RED / ORIGEN</th>
                        <th class="py-3 text-muted small fw-semibold text-end pe-4">DETALLES</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($registros as $reg)
                    <tr>
                        <td class="ps-4 text-muted small" style="white-space: nowrap;">
                            <i class="bi bi-calendar3 me-1 text-secondary"></i>{{ $reg->created_at->format('d/m/Y') }}
                            <small class="d-block text-muted">{{ $reg->created_at->format('H:i:s') }}</small>
                        </td>
                        <td>
                            <div class="fw-semibold text-dark">{{ $reg->admin->name ?? 'Admin #' . $reg->admin_id }}</div>
                            <small class="text-muted" style="font-size: 0.72rem;">{{ $reg->admin->email ?? '' }}</small>
                        </td>
                        <td>
                            @php
                                $badgeStyle = match($reg->accion) {
                                    'CREACION_USUARIO' => 'bg-success-subtle text-success border border-success-subtle',
                                    'CAMBIO_ROL' => 'bg-warning-subtle text-dark border border-warning-subtle',
                                    'ACTIVACION_CUENTA' => 'bg-primary-subtle text-primary border border-primary-subtle',
                                    'DESACTIVACION_CUENTA' => 'bg-danger-subtle text-danger border border-danger-subtle',
                                    'RESTABLECIMIENTO_ACCESO' => 'bg-info-subtle text-info-emphasis border border-info-subtle',
                                    'CORRECCION_NOMBRE' => 'bg-secondary-subtle text-secondary border border-secondary-subtle',
                                    'CORRECCION_CORREO' => 'bg-secondary-subtle text-secondary border border-secondary-subtle',
                                    default => 'bg-light text-dark border',
                                };
                            @endphp
                            <span class="badge {{ $badgeStyle }} rounded-pill px-2 py-1" style="font-size: 0.73rem;">
                                {{ $reg->accion }}
                            </span>
                        </td>
                        <td>
                            @if($reg->usuarioAfectado)
                                <span class="fw-semibold text-dark">{{ $reg->usuarioAfectado->name }}</span>
                                <small class="d-block text-muted" style="font-size: 0.72rem;">{{ $reg->usuarioAfectado->email }}</small>
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                        <td class="text-truncate" style="max-width: 240px;" title="{{ $reg->motivo }}">
                            {{ $reg->motivo ?: 'Sin motivo registrado' }}
                        </td>
                        <td class="small" style="white-space: nowrap;">
                            <span class="badge bg-light text-secondary border fw-normal" title="Dirección IP de conexión: {{ $reg->ip_address ?: '127.0.0.1' }}">
                                <i class="bi bi-hdd-network text-primary me-1"></i>Red Local UPDS
                            </span>
                        </td>
                        <td class="text-end pe-4">
                            @if($reg->detalles)
                                <button type="button" class="btn btn-sm btn-outline-primary rounded-circle p-1" style="width: 28px; height: 28px;" data-bs-toggle="modal" data-bs-target="#modalDetalleAudit{{ $reg->id }}" title="Ver detalles técnicos">
                                    <i class="bi bi-info-lg"></i>
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
                            No se encontraron registros de auditoría con los criterios especificados.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($registros->hasPages())
        <div class="px-4 py-3 border-top bg-light d-flex flex-column flex-md-row justify-content-between align-items-center gap-2">
            <div class="small text-muted">
                Mostrando del <strong>{{ $registros->firstItem() }}</strong> al <strong>{{ $registros->lastItem() }}</strong> de <strong>{{ $registros->total() }}</strong> eventos
            </div>
            <div>
                {{ $registros->links('pagination::bootstrap-5') }}
            </div>
        </div>
        @endif
    </div>
</div>

<!-- Modales de Detalle -->
@foreach($registros as $reg)
    @if($reg->detalles)
        @php
            $dataDetalles = json_decode($reg->detalles, true);
        @endphp
        <div class="modal fade" id="modalDetalleAudit{{ $reg->id }}" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content border-0 shadow rounded-4 overflow-hidden">
                    <div class="modal-header bg-light">
                        <h6 class="modal-title fw-bold text-navy" style="color:var(--upds-navy);">
                            <i class="bi bi-shield-check me-2 text-primary"></i>Detalle de Operación #{{ $reg->id }}
                        </h6>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body p-4">
                        <div class="mb-3 p-3 bg-light rounded-3 border">
                            <div class="row g-2 small">
                                <div class="col-6"><strong>Acción:</strong> <span class="badge bg-secondary">{{ $reg->accion }}</span></div>
                                <div class="col-6"><strong>Fecha:</strong> {{ $reg->created_at->format('d/m/Y H:i:s') }}</div>
                                <div class="col-12 mt-2"><strong>Motivo:</strong> {{ $reg->motivo ?: 'Operación administrativa regular' }}</div>
                                <div class="col-12 mt-1">
                                    <strong>Terminal / Red:</strong> 
                                    <span class="text-muted">Red Local UPDS (IP: <code>{{ $reg->ip_address ?: '127.0.0.1' }}</code>)</span>
                                </div>
                            </div>
                        </div>

                        <label class="form-label small fw-bold text-navy mb-2" style="color:var(--upds-navy);">
                            <i class="bi bi-list-check me-1"></i>Parámetros y Datos Registrados:
                        </label>
                        @if(is_array($dataDetalles))
                            <div class="table-responsive rounded-3 border">
                                <table class="table table-sm table-striped align-middle mb-0 bg-white">
                                    <thead class="table-light">
                                        <tr class="small text-muted">
                                            <th class="ps-3 py-2" style="width: 42%;">CAMPO / PARÁMETRO</th>
                                            <th class="py-2">VALOR ASIGNADO</th>
                                        </tr>
                                    </thead>
                                    <tbody class="small">
                                        @foreach($dataDetalles as $clave => $valor)
                                            <tr>
                                                <td class="ps-3 fw-semibold text-secondary text-capitalize">
                                                    {{ ucwords(str_replace('_', ' ', $clave)) }}
                                                </td>
                                                <td>
                                                    @if(is_bool($valor))
                                                        <span class="badge {{ $valor ? 'bg-success' : 'bg-secondary' }}">{{ $valor ? 'Habilitado' : 'Deshabilitado' }}</span>
                                                    @elseif(is_array($valor))
                                                        <pre class="mb-0 bg-light p-1 rounded border small">{{ json_encode($valor, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                                                    @else
                                                        <span class="fw-medium text-dark">{{ $valor }}</span>
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <div class="bg-light p-3 rounded-3 small border">{{ $reg->detalles }}</div>
                        @endif
                    </div>
                    <div class="modal-footer bg-light py-2">
                        <button type="button" class="btn btn-sm btn-secondary rounded-pill px-4" data-bs-dismiss="modal">Cerrar</button>
                    </div>
                </div>
            </div>
        </div>
    @endif
@endforeach
@endsection
