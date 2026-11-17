@extends('layouts.app')

@section('title', 'Mis Estudiantes Asignados - UPDSonline')

@section('content')
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
    <div>
        <h4 class="fw-bold mb-1 text-navy" style="color: var(--upds-navy);">
            <i class="bi bi-people-fill text-primary me-2"></i>Mis Estudiantes Asignados
        </h4>
        <p class="text-muted small mb-0">Estudiantes de práctica preprofesional bajo tu supervisión académica.</p>
    </div>
</div>

<div class="card border-0 shadow-sm rounded-4 overflow-hidden">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead style="background-color: #f8fafc; border-bottom: 2px solid #e2e8f0;">
                <tr>
                    <th class="ps-4 py-3 text-muted small fw-semibold">ESTUDIANTE</th>
                    <th class="py-3 text-muted small fw-semibold">RU</th>
                    <th class="py-3 text-muted small fw-semibold">CARRERA / SEMESTRE</th>
                    <th class="py-3 text-muted small fw-semibold">ORGANIZACIÓN</th>
                    <th class="py-3 text-muted small fw-semibold">HORAS VALID.</th>
                    <th class="py-3 text-muted small fw-semibold">AVANCE</th>
                    <th class="py-3 text-muted small fw-semibold">ESTADO</th>
                    <th class="py-3 text-muted small fw-semibold text-end pe-4">ACCIÓN</th>
                </tr>
            </thead>
            <tbody>
                @forelse($practicas as $p)
                    <tr>
                        <td class="ps-4">
                            <div class="d-flex align-items-center gap-2">
                                <div style="width: 38px; height: 38px; border-radius: 50%; background: #e0f2fe; color: #0284c7; display: flex; align-items: center; justify-content: center; font-weight: bold;">
                                    {{ strtoupper(substr($p->solicitud->estudiante->nombres, 0, 1)) }}
                                </div>
                                <div>
                                    <div class="fw-bold text-dark">{{ $p->solicitud->estudiante->nombre_completo }}</div>
                                    <small class="text-muted">{{ $p->solicitud->estudiante->telefono ?? 'Sin teléfono' }}</small>
                                </div>
                            </div>
                        </td>
                        <td><code>{{ $p->solicitud->estudiante->ru }}</code></td>
                        <td>
                            <div>{{ $p->solicitud->estudiante->carrera?->nombre ?? '-' }}</div>
                            <small class="text-muted">{{ $p->solicitud->estudiante->semestre ? $p->solicitud->estudiante->semestre . '° Semestre' : '' }}</small>
                        </td>
                        <td>
                            <div class="fw-semibold text-primary">{{ $p->solicitud->organizacion->nombre }}</div>
                            <small class="text-muted">{{ $p->solicitud->organizacion->rubro ?? '' }}</small>
                        </td>
                        <td>
                            <strong class="text-success">{{ $p->horas_validadas }}</strong> / {{ $p->horas_objetivo }} hrs
                        </td>
                        <td style="width: 140px;">
                            <div class="progress" style="height: 8px; border-radius: 4px;">
                                <div class="progress-bar bg-success" style="width: {{ $p->porcentaje_horas }}%"></div>
                            </div>
                            <small class="text-muted fw-semibold">{{ $p->porcentaje_horas }}%</small>
                        </td>
                        <td>
                            <span class="badge-{{ $p->estado }}">{{ ucfirst(str_replace('_', ' ', $p->estado)) }}</span>
                        </td>
                        <td class="text-end pe-4">
                            <a href="{{ route('seguimiento.show', $p->id) }}" class="btn btn-sm btn-upds px-3">
                                <i class="bi bi-graph-up me-1"></i> Ver seguimiento
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="text-center py-5 text-muted">
                            <i class="bi bi-person-x fs-1 opacity-50 d-block mb-2"></i>
                            Actualmente no tienes estudiantes asignados para seguimiento.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection

