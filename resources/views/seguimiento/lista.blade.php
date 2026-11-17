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
                        <td>
                            <strong>{{ $p->horas_validadas }}</strong> / {{ $p->horas_objetivo }} hrs
                        </td>
                        <td style="width: 150px;">
                            <div class="progress" style="height: 8px;">
                                <div class="progress-bar bg-success" style="width: {{ $p->porcentaje_horas }}%"></div>
                            </div>
                            <small class="text-muted">{{ $p->porcentaje_horas }}%</small>
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
</div>
@endsection

