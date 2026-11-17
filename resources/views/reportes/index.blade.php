@extends('layouts.app')

@section('title', 'Reportes Académicos - UPDSonline')

@section('content')
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
    <div>
        <h4 class="fw-bold mb-1 text-navy" style="color: var(--upds-navy);">
            <i class="bi bi-bar-chart-line text-primary me-2"></i>Reportes de Prácticas y Vinculación
        </h4>
        <p class="text-muted small mb-0">Consultas consolidadas basadas en las 9 vistas SQL del modelo institucional UPDS.</p>
    </div>
    <button onclick="window.print()" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-printer me-1"></i> Imprimir / Exportar a PDF
    </button>
</div>

<!-- Tabs de Reportes -->
<ul class="nav nav-pills mb-4 gap-2" id="reportesTab" role="tablist">
    <li class="nav-item">
        <button class="nav-link active fw-semibold rounded-pill px-3" data-bs-toggle="pill" data-bs-target="#rep-cumplimiento">
            <i class="bi bi-pie-chart me-1"></i> Cumplimiento de Horas
        </button>
    </li>
    <li class="nav-item">
        <button class="nav-link fw-semibold rounded-pill px-3" data-bs-toggle="pill" data-bs-target="#rep-encurso">
            <i class="bi bi-play-circle me-1"></i> En Curso ({{ count($enCurso) }})
        </button>
    </li>
    <li class="nav-item">
        <button class="nav-link fw-semibold rounded-pill px-3" data-bs-toggle="pill" data-bs-target="#rep-concluidas">
            <i class="bi bi-check2-circle me-1"></i> Concluidas ({{ count($concluidas) }})
        </button>
    </li>
    <li class="nav-item">
        <button class="nav-link fw-semibold rounded-pill px-3" data-bs-toggle="pill" data-bs-target="#rep-pendientes">
            <i class="bi bi-hourglass-split me-1"></i> Solicitudes Pendientes ({{ count($pendientes) }})
        </button>
    </li>
    <li class="nav-item">
        <button class="nav-link fw-semibold rounded-pill px-3" data-bs-toggle="pill" data-bs-target="#rep-tutor">
            <i class="bi bi-person-lines-fill me-1"></i> Estudiantes por Tutor
        </button>
    </li>
    <li class="nav-item">
        <button class="nav-link fw-semibold rounded-pill px-3" data-bs-toggle="pill" data-bs-target="#rep-empresas">
            <i class="bi bi-buildings me-1"></i> Organizaciones Activas
        </button>
    </li>
</ul>

<div class="tab-content">
    <!-- 1. Cumplimiento -->
    <div class="tab-pane fade show active" id="rep-cumplimiento">
        <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
            <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                <h6 class="fw-bold mb-0 text-navy">Cómputo de Horas y Cumplimiento General (Vista SQL: vw_reporte_cumplimiento)</h6>
                <a href="{{ route('reportes.exportar', ['tipo' => 'cumplimiento']) }}" class="btn btn-sm btn-outline-success">
                    <i class="bi bi-file-earmark-spreadsheet me-1"></i> Exportar CSV/Excel
                </a>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light small">
                        <tr>
                            <th>RU</th>
                            <th>Estudiante</th>
                            <th>Tutor</th>
                            <th>Organización</th>
                            <th>Horas Validadas</th>
                            <th>Objetivo</th>
                            <th>% Avance</th>
                            <th>Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($cumplimiento as $c)
                            <tr>
                                <td><code>{{ $c->ru }}</code></td>
                                <td class="fw-semibold">{{ $c->estudiante }}</td>
                                <td>{{ $c->tutor ?? 'Sin tutor' }}</td>
                                <td>{{ $c->organizacion }}</td>
                                <td><strong>{{ $c->horas_validadas }} h</strong></td>
                                <td>{{ $c->horas_objetivo }} h</td>
                                <td style="width: 140px;">
                                    <div class="progress" style="height: 8px;">
                                        <div class="progress-bar bg-success" style="width: {{ $c->porcentaje_horas }}%"></div>
                                    </div>
                                    <small class="text-muted">{{ $c->porcentaje_horas }}%</small>
                                </td>
                                <td><span class="badge-{{ $c->estado }}">{{ ucfirst(str_replace('_', ' ', $c->estado)) }}</span></td>
                            </tr>
                        @empty
                            <tr><td colspan="8" class="text-center py-4 text-muted">No hay registros de cumplimiento.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- 2. En Curso -->
    <div class="tab-pane fade" id="rep-encurso">
        <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
            <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                <h6 class="fw-bold mb-0 text-navy">Prácticas en Curso (Vista SQL: vw_reporte_en_curso)</h6>
                <a href="{{ route('reportes.exportar', ['tipo' => 'encurso']) }}" class="btn btn-sm btn-outline-success">
                    <i class="bi bi-file-earmark-spreadsheet me-1"></i> Exportar CSV/Excel
                </a>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light small">
                        <tr>
                            <th>Estudiante</th>
                            <th>RU</th>
                            <th>Organización</th>
                            <th>Docente Tutor</th>
                            <th>Fecha Inicio</th>
                            <th>Horas Registradas</th>
                            <th>Progreso</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($enCurso as $ec)
                            <tr>
                                <td class="fw-semibold">{{ $ec->estudiante }}</td>
                                <td><code>{{ $ec->ru }}</code></td>
                                <td>{{ $ec->organizacion }}</td>
                                <td>{{ $ec->tutor }}</td>
                                <td>{{ date('d/m/Y', strtotime($ec->fecha_inicio)) }}</td>
                                <td>{{ $ec->horas_validadas }} / {{ $ec->horas_objetivo }} hrs</td>
                                <td><span class="badge-en_curso">{{ $ec->porcentaje_horas }}%</span></td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="text-center py-4 text-muted">No hay prácticas en curso.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- 3. Concluidas -->
    <div class="tab-pane fade" id="rep-concluidas">
        <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
            <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                <h6 class="fw-bold mb-0 text-navy">Prácticas Profesionales Concluidas (Vista SQL: vw_reporte_concluidas)</h6>
                <a href="{{ route('reportes.exportar', ['tipo' => 'concluidas']) }}" class="btn btn-sm btn-outline-success">
                    <i class="bi bi-file-earmark-spreadsheet me-1"></i> Exportar CSV/Excel
                </a>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light small">
                        <tr>
                            <th>Estudiante</th>
                            <th>RU</th>
                            <th>Organización</th>
                            <th>Tutor</th>
                            <th>Fecha Cierre</th>
                            <th>Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($concluidas as $co)
                            <tr>
                                <td class="fw-semibold">{{ $co->estudiante }}</td>
                                <td><code>{{ $co->ru }}</code></td>
                                <td>{{ $co->organizacion }}</td>
                                <td>{{ $co->tutor }}</td>
                                <td>{{ $co->fecha_fin_real ? date('d/m/Y', strtotime($co->fecha_fin_real)) : 'Finalizada' }}</td>
                                <td><span class="badge-activo">Concluida</span></td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-center py-4 text-muted">No hay prácticas concluidas.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- 4. Solicitudes Pendientes -->
    <div class="tab-pane fade" id="rep-pendientes">
        <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
            <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                <h6 class="fw-bold mb-0 text-navy">Solicitudes Pendientes de Aprobación (Vista SQL: vw_reporte_solicitudes_pendientes)</h6>
                <a href="{{ route('reportes.exportar', ['tipo' => 'pendientes']) }}" class="btn btn-sm btn-outline-success">
                    <i class="bi bi-file-earmark-spreadsheet me-1"></i> Exportar CSV/Excel
                </a>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light small">
                        <tr>
                            <th>N° Trámite</th>
                            <th>Estudiante</th>
                            <th>RU</th>
                            <th>Organización</th>
                            <th>Fecha Solicitud</th>
                            <th>Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($pendientes as $pe)
                            <tr>
                                <td><strong>S-00{{ $pe->solicitud_id }}</strong></td>
                                <td class="fw-semibold">{{ $pe->estudiante }}</td>
                                <td><code>{{ $pe->ru }}</code></td>
                                <td>{{ $pe->organizacion }}</td>
                                <td>{{ date('d/m/Y', strtotime($pe->fecha_solicitud)) }}</td>
                                <td><span class="badge-{{ $pe->estado }}">{{ ucfirst(str_replace('_', ' ', $pe->estado)) }}</span></td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-center py-4 text-muted">No hay solicitudes pendientes.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- 5. Estudiantes por Tutor -->
    <div class="tab-pane fade" id="rep-tutor">
        <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
            <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                <h6 class="fw-bold mb-0 text-navy">Distribución de Estudiantes por Docente Tutor (Vista SQL: vw_reporte_estudiantes_por_tutor)</h6>
                <a href="{{ route('reportes.exportar', ['tipo' => 'tutor']) }}" class="btn btn-sm btn-outline-success">
                    <i class="bi bi-file-earmark-spreadsheet me-1"></i> Exportar CSV/Excel
                </a>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light small">
                        <tr>
                            <th>Docente Tutor</th>
                            <th>Estudiante Asignado</th>
                            <th>RU</th>
                            <th>Organización</th>
                            <th>Estado Práctica</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($porTutor as $pt)
                            <tr>
                                <td class="fw-bold text-primary">{{ $pt->tutor }}</td>
                                <td class="fw-semibold">{{ $pt->estudiante }}</td>
                                <td><code>{{ $pt->ru }}</code></td>
                                <td>{{ $pt->organizacion }}</td>
                                <td><span class="badge-{{ $pt->estado }}">{{ ucfirst(str_replace('_', ' ', $pt->estado)) }}</span></td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-center py-4 text-muted">No hay asignaciones registradas.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- 6. Organizaciones Activas -->
    <div class="tab-pane fade" id="rep-empresas">
        <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
            <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                <h6 class="fw-bold mb-0 text-navy">Organizaciones Receptoras Activas (Vista SQL: vw_reporte_organizaciones_activas)</h6>
                <a href="{{ route('reportes.exportar', ['tipo' => 'organizaciones']) }}" class="btn btn-sm btn-outline-success">
                    <i class="bi bi-file-earmark-spreadsheet me-1"></i> Exportar CSV/Excel
                </a>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light small">
                        <tr>
                            <th>Nombre o Razón Social</th>
                            <th>Rubro</th>
                            <th>Dirección</th>
                            <th>Teléfono</th>
                            <th>Correo</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($organizaciones as $org)
                            <tr>
                                <td class="fw-bold text-dark">{{ $org->nombre }}</td>
                                <td><span class="badge bg-light text-dark border">{{ $org->rubro }}</span></td>
                                <td>{{ $org->direccion ?? 'N/A' }}</td>
                                <td>{{ $org->telefono ?? 'N/A' }}</td>
                                <td>{{ $org->correo ?? 'N/A' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-center py-4 text-muted">No hay organizaciones activas.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

