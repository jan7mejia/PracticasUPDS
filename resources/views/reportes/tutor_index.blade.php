@extends('layouts.app')

@section('title', 'Reportes de Tutoría - UPDSonline')

@section('content')
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
    <div>
        <h4 class="fw-bold mb-1 text-navy" style="color: var(--upds-navy);">
            <i class="bi bi-bar-chart-line text-primary me-2"></i>Reportes de Seguimiento y Tutoría
        </h4>
        <p class="text-muted small mb-0">
            Supervisión académica consolidada de los estudiantes asignados a <strong>{{ $tutor->nombre_completo ?? auth()->user()->name }}</strong>.
        </p>
    </div>
    <button onclick="window.print()" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-printer me-1"></i> Imprimir Reporte
    </button>
</div>

<!-- Tarjetas de Resumen KPI -->
<div class="row g-3 mb-4">
    <div class="col-sm-6 col-lg-3">
        <div class="card border-0 shadow-sm rounded-4 p-3 text-center">
            <span class="text-muted small text-uppercase fw-semibold">Estudiantes Supervisados</span>
            <div class="fs-3 fw-bold text-navy mt-1" style="color: var(--upds-navy);">{{ $practicas->count() }}</div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-3">
        <div class="card border-0 shadow-sm rounded-4 p-3 text-center">
            <span class="text-muted small text-uppercase fw-semibold">Prácticas en Curso</span>
            <div class="fs-3 fw-bold text-primary mt-1">{{ $practicas->where('estado', 'en_curso')->count() }}</div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-3">
        <div class="card border-0 shadow-sm rounded-4 p-3 text-center">
            <span class="text-muted small text-uppercase fw-semibold">Horas Validadas Totales</span>
            <div class="fs-3 fw-bold text-success mt-1">{{ number_format($practicas->sum('horas_validadas'), 1) }} h</div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-3">
        <div class="card border-0 shadow-sm rounded-4 p-3 text-center">
            <span class="text-muted small text-uppercase fw-semibold">Informes Revisados</span>
            <div class="fs-3 fw-bold text-dark mt-1">{{ $practicas->flatMap->informes->where('estado', 'revisado')->count() }}</div>
        </div>
    </div>
</div>

<!-- Tabs de Reportes del Tutor -->
<ul class="nav nav-pills mb-4 gap-2" id="reportesTutorTab" role="tablist">
    <li class="nav-item">
        <button class="nav-link active fw-semibold rounded-pill px-3" data-bs-toggle="pill" data-bs-target="#rep-avance">
            <i class="bi bi-pie-chart me-1"></i> Avance de Horas y Estado
        </button>
    </li>
    <li class="nav-item">
        <button class="nav-link fw-semibold rounded-pill px-3" data-bs-toggle="pill" data-bs-target="#rep-informes">
            <i class="bi bi-journal-text me-1"></i> Informes de Avance ({{ $practicas->flatMap->informes->count() }})
        </button>
    </li>
    <li class="nav-item">
        <button class="nav-link fw-semibold rounded-pill px-3" data-bs-toggle="pill" data-bs-target="#rep-evaluaciones">
            <i class="bi bi-award me-1"></i> Calificaciones y Evaluaciones
        </button>
    </li>
</ul>

<div class="tab-content">
    <!-- PESTAÑA 1: AVANCE DE HORAS -->
    <div class="tab-pane fade show active" id="rep-avance">
        <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
            <div class="card-header bg-white py-3 border-bottom">
                <h6 class="fw-bold mb-0 text-navy">Consolidado de Cumplimiento de Prácticas</h6>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light small">
                        <tr>
                            <th class="ps-4">Estudiante</th>
                            <th>RU</th>
                            <th>Organización Receptora</th>
                            <th>Horas Validadas</th>
                            <th>Objetivo</th>
                            <th>% Avance</th>
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
                                <td><strong class="text-success">{{ $p->horas_validadas }} h</strong></td>
                                <td>{{ $p->horas_objetivo }} h</td>
                                <td style="width: 140px;">
                                    <div class="progress" style="height: 8px;">
                                        <div class="progress-bar bg-success" style="width: {{ $p->porcentaje_horas }}%"></div>
                                    </div>
                                    <small class="text-muted">{{ $p->porcentaje_horas }}%</small>
                                </td>
                                <td><span class="badge-{{ $p->estado }}">{{ ucfirst(str_replace('_', ' ', $p->estado)) }}</span></td>
                                <td class="text-end pe-4">
                                    <a href="{{ route('seguimiento.show', $p->id) }}" class="btn btn-sm btn-outline-primary">
                                        <i class="bi bi-eye me-1"></i> Seguimiento
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center py-4 text-muted">
                                    No tienes estudiantes asignados en prácticas actualmente.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- PESTAÑA 2: INFORMES DE AVANCE -->
    <div class="tab-pane fade" id="rep-informes">
        <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
            <div class="card-header bg-white py-3 border-bottom">
                <h6 class="fw-bold mb-0 text-navy">Control de Informes de Avance Presentados</h6>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light small">
                        <tr>
                            <th class="ps-4">Estudiante</th>
                            <th>Título del Informe</th>
                            <th>Período</th>
                            <th>Fecha Envío</th>
                            <th>Estado</th>
                            <th>Fecha Revisión</th>
                            <th class="text-end pe-4">Acción</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php $totalInformes = 0; @endphp
                        @foreach($practicas as $p)
                            @foreach($p->informes as $inf)
                                @php $totalInformes++; @endphp
                                <tr>
                                    <td class="ps-4">
                                        <div class="fw-semibold text-dark">{{ $p->solicitud->estudiante->nombre_completo }}</div>
                                        <small class="text-muted">{{ $p->solicitud->estudiante->ru }}</small>
                                    </td>
                                    <td>{{ $inf->titulo }}</td>
                                    <td class="small">{{ $inf->periodo_inicio->format('d/m/Y') }} al {{ $inf->periodo_fin->format('d/m/Y') }}</td>
                                    <td class="small">{{ $inf->fecha_envio ? $inf->fecha_envio->format('d/m/Y H:i') : '-' }}</td>
                                    <td><span class="badge-{{ $inf->estado }}">{{ ucfirst($inf->estado) }}</span></td>
                                    <td class="small text-muted">{{ $inf->fecha_revision ? $inf->fecha_revision->format('d/m/Y H:i') : 'Pendiente' }}</td>
                                    <td class="text-end pe-4">
                                        <a href="{{ route('seguimiento.show', $p->id) }}" class="btn btn-sm btn-outline-primary">
                                            <i class="bi bi-box-arrow-up-right me-1"></i> Ir
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        @endforeach
                        @if($totalInformes === 0)
                            <tr>
                                <td colspan="7" class="text-center py-4 text-muted">
                                    No hay informes presentados por los estudiantes bajo tu supervisión.
                                </td>
                            </tr>
                        @endif
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- PESTAÑA 3: EVALUACIONES -->
    <div class="tab-pane fade" id="rep-evaluaciones">
        <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
            <div class="card-header bg-white py-3 border-bottom">
                <h6 class="fw-bold mb-0 text-navy">Evaluaciones de Seguimiento y Evaluación Final</h6>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light small">
                        <tr>
                            <th class="ps-4">Estudiante</th>
                            <th>Tipo de Evaluación</th>
                            <th>Período / Criterio</th>
                            <th>Fecha</th>
                            <th>Calificación</th>
                            <th>Resultado</th>
                            <th>Comentarios</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php $totalEvals = 0; @endphp
                        @foreach($practicas as $p)
                            @foreach($p->evaluacionesSeguimiento as $ev)
                                @php $totalEvals++; @endphp
                                <tr>
                                    <td class="ps-4">
                                        <div class="fw-semibold text-dark">{{ $p->solicitud->estudiante->nombre_completo }}</div>
                                        <small class="text-muted">{{ $p->solicitud->estudiante->ru }}</small>
                                    </td>
                                    <td><span class="badge bg-info-subtle text-info border">Parcial / Seguimiento</span></td>
                                    <td>{{ $ev->periodo }}</td>
                                    <td class="small">{{ $ev->fecha->format('d/m/Y') }}</td>
                                    <td><strong>{{ $ev->calificacion }} / 100</strong></td>
                                    <td><span class="badge bg-primary text-white">{{ $ev->resultado }}</span></td>
                                    <td class="small text-muted text-truncate" style="max-width: 200px;" title="{{ $ev->comentario }}">{{ $ev->comentario ?? '-' }}</td>
                                </tr>
                            @endforeach
                            @if($p->evaluacionFinal)
                                @php $totalEvals++; @endphp
                                <tr class="table-success bg-opacity-25">
                                    <td class="ps-4">
                                        <div class="fw-semibold text-dark">{{ $p->solicitud->estudiante->nombre_completo }}</div>
                                        <small class="text-muted">{{ $p->solicitud->estudiante->ru }}</small>
                                    </td>
                                    <td><span class="badge bg-success text-white">Evaluación Final</span></td>
                                    <td>Cierre de Práctica</td>
                                    <td class="small">{{ $p->evaluacionFinal->fecha->format('d/m/Y') }}</td>
                                    <td><strong class="text-success">{{ $p->evaluacionFinal->calificacion }} / 100</strong></td>
                                    <td><span class="badge bg-success text-white">{{ $p->evaluacionFinal->resultado }}</span></td>
                                    <td class="small text-muted text-truncate" style="max-width: 200px;" title="{{ $p->evaluacionFinal->comentario }}">{{ $p->evaluacionFinal->comentario ?? '-' }}</td>
                                </tr>
                            @endif
                        @endforeach
                        @if($totalEvals === 0)
                            <tr>
                                <td colspan="7" class="text-center py-4 text-muted">
                                    No hay evaluaciones parciales ni finales registradas todavía.
                                </td>
                            </tr>
                        @endif
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

