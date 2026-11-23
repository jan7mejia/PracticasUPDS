@extends('layouts.app')

@section('title', 'Listado de Prácticas en Seguimiento - UPDSonline')

@section('content')
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
    <div>
        <h4 class="fw-bold mb-1 text-navy" style="color: var(--upds-navy);">
            <i class="bi bi-clock-history text-primary me-2"></i>Seguimiento y evaluación de prácticas
        </h4>
        <p class="text-muted small mb-0">Supervisión académica de actividades, cómputo de horas y evaluación final.</p>
    </div>
</div>

<!-- Filtros y Búsqueda con Auto-búsqueda -->
<div class="card border-0 shadow-sm rounded-4 p-3 mb-4">
    <form action="{{ route('seguimiento.index') }}" method="GET" class="row g-2 align-items-center">
        <div class="col-12 col-md-6">
            <div class="input-group input-group-sm">
                <span class="input-group-text bg-light border-end-0"><i class="bi bi-search text-muted"></i></span>
                <input type="text" name="buscar" value="{{ request('buscar') }}" class="form-control bg-light border-start-0" 
                       placeholder="Buscar por estudiante, RU u organización..." data-auto-search autocomplete="off">
                @if(request('buscar'))
                    <a href="{{ route('seguimiento.index') }}" class="btn btn-outline-secondary btn-sm" title="Limpiar"><i class="bi bi-x-lg"></i></a>
                @endif
            </div>
        </div>
        <div class="col-6 col-md-3">
            <select name="estado" class="form-select form-select-sm" onchange="this.form.submit()">
                <option value="">Todos los estados</option>
                <option value="planificada" {{ request('estado') === 'planificada' ? 'selected' : '' }}>Planificada</option>
                <option value="en_curso" {{ request('estado') === 'en_curso' ? 'selected' : '' }}>En curso</option>
                <option value="concluida" {{ request('estado') === 'concluida' ? 'selected' : '' }}>Concluida</option>
                <option value="abandonada" {{ request('estado') === 'abandonada' ? 'selected' : '' }}>Abandonada</option>
                <option value="cancelada" {{ request('estado') === 'cancelada' ? 'selected' : '' }}>Cancelada</option>
            </select>
        </div>
        <div class="col-6 col-md-3">
            <select name="per_page" class="form-select form-select-sm" onchange="this.form.submit()">
                <option value="10" {{ request('per_page', 10) == 10 ? 'selected' : '' }}>10 por página</option>
                <option value="25" {{ request('per_page') == 25 ? 'selected' : '' }}>25 por página</option>
                <option value="50" {{ request('per_page') == 50 ? 'selected' : '' }}>50 por página</option>
            </select>
        </div>
    </form>
</div>

<div class="card border-0 shadow-sm rounded-4 overflow-hidden">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr style="font-size: 0.85rem; color: #475569;">
                    <th class="ps-4">Estudiante</th>
                    <th>RU</th>
                    <th>Organización</th>
                    <th>Tutor Asignado</th>
                    <th>Horas Validadas</th>
                    <th>Progreso</th>
                    <th>Estado</th>
                    <th class="text-end pe-4">Acción</th>
                </tr>
            </thead>
            <tbody>
                @forelse($practicas as $p)
                    <tr>
                        <td class="ps-4">
                            <div class="fw-semibold text-dark">{{ $p->solicitud->estudiante->nombre_completo }}</div>
                            <small class="text-muted">{{ $p->solicitud->estudiante->carrera->nombre }}</small>
                        </td>
                        <td><code>{{ $p->solicitud->estudiante->ru }}</code></td>
                        <td>{{ $p->solicitud->organizacion->nombre }}</td>
                        <td>
                            @if($p->tutor)
                                <span class="fw-medium text-dark"><i class="bi bi-person-badge text-primary me-1"></i> {{ $p->tutor->nombre_completo }}</span>
                            @else
                                <span class="badge bg-warning-subtle text-warning border border-warning-subtle">Sin Tutor Asignado</span>
                            @endif
                        </td>
                        @php
                            $hvali = (float) $p->horas_validadas;
                            $hpend = (float) $p->registrosHoras->where('estado','pendiente')->sum('horas');
                            $hobj  = (float) $p->horas_objetivo ?: 1;
                            $pctV  = min(100, round(($hvali / $hobj) * 100, 1));
                            $pctP  = min(100 - $pctV, round(($hpend / $hobj) * 100, 1));
                        @endphp
                        <td>
                            <div class="fw-semibold text-success">{{ number_format($hvali,1) }}h</div>
                            @if($hpend > 0)
                                <small class="text-warning d-block" style="font-size:0.75rem;">
                                    <i class="bi bi-clock me-1"></i>+{{ number_format($hpend,1) }}h en revisión
                                </small>
                            @endif
                            <small class="text-muted" style="font-size:0.72rem;">/ {{ number_format($hobj,0) }} hrs requeridas</small>
                        </td>
                        <td style="width: 160px;">
                            <div class="progress mb-1" style="height: 10px; border-radius:5px; background:#e9ecef;">
                                <div class="progress-bar bg-success" style="width: {{ $pctV }}%" title="{{ $hvali }}h validadas"></div>
                                <div class="progress-bar bg-warning" style="width: {{ $pctP }}%" title="{{ $hpend }}h en revisión"></div>
                            </div>
                            <small class="text-muted" style="font-size:0.72rem;">
                                <span class="text-success fw-semibold">{{ $pctV }}%</span>
                                @if($pctP > 0)
                                    <span class="text-warning"> +{{ $pctP }}%</span>
                                @endif
                            </small>
                        </td>
                        <td><span class="badge-{{ $p->estado }}">{{ ucfirst(str_replace('_', ' ', $p->estado)) }}</span></td>
                        <td class="text-end pe-4">
                            <a href="{{ route('seguimiento.show', $p->id) }}" class="btn btn-sm btn-upds">
                                <i class="bi bi-eye me-1"></i> Ver seguimiento
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="text-center py-4 text-muted">
                            No hay prácticas registradas para seguimiento actualmente.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Paginación de Prácticas -->
    @if($practicas->hasPages() || $practicas->total() > 0)
        <div class="card-footer bg-white border-0 py-3 px-4 d-flex flex-column flex-md-row justify-content-between align-items-center gap-2">
            <span class="small text-muted">
                Mostrando <strong>{{ $practicas->firstItem() ?? 0 }}</strong>–<strong>{{ $practicas->lastItem() ?? 0 }}</strong> de <strong>{{ $practicas->total() }}</strong> prácticas registradas
            </span>
            <div>
                {{ $practicas->links('pagination::bootstrap-5') }}
            </div>
        </div>
    @endif
</div>
@endsection

