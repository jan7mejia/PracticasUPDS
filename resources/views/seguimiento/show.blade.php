@extends('layouts.app')

@section('title', 'Seguimiento y Evaluación de Práctica - UPDSonline')

@section('content')
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
    <div>
        <h4 class="fw-bold mb-1 text-navy" style="color: var(--upds-navy);">
            <i class="bi bi-graph-up-arrow text-primary me-2"></i>Seguimiento y evaluación de práctica
        </h4>
        <p class="text-muted small mb-0">Control de bitácora de horas, informes de avance, observaciones y evaluación final.</p>
    </div>
    <div class="d-flex align-items-center gap-2">
        <span class="badge-{{ $practica->estado }} fs-6 px-3 py-2">
            {{ ucfirst(str_replace('_', ' ', $practica->estado)) }}
        </span>
    </div>
</div>

<!-- ══════════════════════════════════════════════════════════════════════════════ -->
<!-- SECCIÓN 1: RESUMEN GENERAL Y ESTADO DE LA PRÁCTICA -->
<!-- ══════════════════════════════════════════════════════════════════════════════ -->
<div class="row g-4 mb-4">
    <!-- Ficha de Asignación e Información Institucional -->
    <div class="col-12 col-lg-7">
        <div class="card border-0 shadow-sm rounded-4 p-4 h-100">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="fw-bold text-navy mb-0" style="color: var(--upds-navy);">
                    <i class="bi bi-person-badge text-primary me-2"></i>Asignación y Datos Institucionales
                </h6>
                @if(auth()->user()->isCoordinador())
                    <button class="btn btn-sm btn-outline-primary py-1 px-3 rounded-pill fw-semibold" data-bs-toggle="modal" data-bs-target="#modalAsignarTutor">
                        <i class="bi bi-pencil me-1"></i> Editar asignación
                    </button>
                @endif
            </div>

            <div class="p-3 bg-light rounded-3 small">
                <div class="row g-2">
                    <div class="col-sm-4 text-muted">Estudiante:</div>
                    <div class="col-sm-8 fw-bold text-dark">{{ $practica->solicitud->estudiante->nombre_completo }}</div>

                    <div class="col-sm-4 text-muted">RU / Carrera:</div>
                    <div class="col-sm-8 fw-semibold"><code>{{ $practica->solicitud->estudiante->ru }}</code> &bull; {{ $practica->solicitud->estudiante->carrera->nombre }}</div>

                    <div class="col-sm-4 text-muted">Organización Receptora:</div>
                    <div class="col-sm-8 fw-semibold text-primary">{{ $practica->solicitud->organizacion->nombre }}</div>

                    <div class="col-sm-4 text-muted">Docente Tutor:</div>
                    <div class="col-sm-8 fw-bold text-dark">
                        {{ $practica->tutor ? $practica->tutor->nombre_completo : 'Pendiente de asignación' }}
                        @if($practica->tutor)
                            <small class="text-muted d-block">({{ $practica->tutor->especialidad }})</small>
                        @endif
                    </div>

                    <div class="col-sm-4 text-muted">Período Académico:</div>
                    <div class="col-sm-8 text-secondary">
                        {{ $practica->solicitud->periodo->codigo ?? '2026-I' }} &bull; 
                        {{ $practica->fecha_inicio ? $practica->fecha_inicio->format('d/m/Y') : 'Por definir' }} al 
                        {{ $practica->fecha_fin_prevista ? $practica->fecha_fin_prevista->format('d/m/Y') : 'Por definir' }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Progreso y Métricas de Horas -->
    <div class="col-12 col-lg-5">
        <div class="card border-0 shadow-sm rounded-4 p-4 h-100 d-flex flex-column justify-content-between">
            <div>
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="fw-bold text-navy mb-0" style="color: var(--upds-navy);">
                        <i class="bi bi-speedometer2 text-primary me-2"></i>Avance de Práctica
                    </h6>
                    <span class="badge-{{ $practica->estado }} fs-6 px-3 py-1">
                        {{ ucfirst(str_replace('_', ' ', $practica->estado)) }}
                    </span>
                </div>

                @php
                    $horasPendientesRevision = $practica->registrosHoras->where('estado', 'pendiente')->sum('horas');
                    $horasRestantes = max(0, $practica->horas_objetivo - $practica->horas_validadas - $horasPendientesRevision);
                @endphp
                <div class="row g-2 mb-3 small text-center">
                    <div class="col-4">
                        <div class="p-2 bg-light rounded-3">
                            <span class="text-muted d-block" style="font-size: 0.75rem;">Requeridas</span>
                            <strong class="fs-6 text-dark">{{ $practica->horas_objetivo }}h</strong>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="p-2 bg-success-subtle rounded-3">
                            <span class="text-success d-block" style="font-size: 0.75rem;">Validadas</span>
                            <strong class="fs-6 text-success">{{ $practica->horas_validadas }}h</strong>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="p-2 bg-warning-subtle rounded-3">
                            <span class="text-dark d-block" style="font-size: 0.75rem;">En revisión</span>
                            <strong class="fs-6 text-warning">{{ $horasPendientesRevision }}h</strong>
                        </div>
                    </div>
                </div>
                @if($horasRestantes > 0)
                <div class="alert alert-light border py-1 px-2 small mb-2 d-flex align-items-center gap-1">
                    <i class="bi bi-hourglass-split text-primary"></i>
                    <span>Faltan <strong>{{ $horasRestantes }}h</strong> para completar las horas requeridas.</span>
                </div>
                @endif

                <!-- Métricas de Asistencia y Excepciones (P0) -->
                <div class="row g-2 mb-3 small text-center">
                    <div class="col-6">
                        <div class="p-2 border rounded-3 bg-light">
                            <span class="text-muted d-block" style="font-size: 0.72rem;">Faltas Justificadas</span>
                            <strong class="{{ $practica->faltas_justificadas > 2 ? 'text-danger' : 'text-primary' }}">
                                {{ $practica->faltas_justificadas }} / 2 permitidas
                            </strong>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="p-2 border rounded-3 bg-light">
                            <span class="text-muted d-block" style="font-size: 0.72rem;">Jornadas Incompletas</span>
                            <strong class="{{ $practica->incompletas_justificadas > 2 ? 'text-danger' : 'text-primary' }}">
                                {{ $practica->incompletas_justificadas }} / 2 permitidas
                            </strong>
                        </div>
                    </div>
                </div>

                @if($practica->incidencias->where('resuelta', false)->count() > 0)
                    <div class="alert alert-danger py-1 px-2 small mb-2 d-flex align-items-center justify-content-between">
                        <span><i class="bi bi-exclamation-octagon-fill me-1"></i> <strong>{{ $practica->incidencias->where('resuelta', false)->count() }} incidencia(s) impeditiva(s)</strong></span>
                        <button class="btn btn-xs btn-outline-danger py-0 px-2" data-bs-toggle="modal" data-bs-target="#modalIncidenciasPractica">Ver</button>
                    </div>
                @endif

                <!-- Barra de Avance -->
                @php
                    $pctValidadas = min(100, $practica->porcentaje_horas);
                    $pctRevision = min(100 - $pctValidadas, round(($horasPendientesRevision / max(1, $practica->horas_objetivo)) * 100, 1));
                    $pctTotal = min(100, $pctValidadas + $pctRevision);
                    $notaMin = $practica->esHistorica() ? 100 : 90;
                @endphp
                <div class="mb-2">
                    <div class="d-flex justify-content-between small text-muted mb-1">
                        <span>Avance de horas <span class="text-muted">(mínimo requerido: {{ $notaMin }}%)</span></span>
                        <strong class="text-dark">{{ $pctValidadas }}% <span class="text-warning fw-normal">+{{ $pctRevision }}% revisión</span></strong>
                    </div>
                    <div class="progress" style="height: 14px; border-radius: 7px; background-color: #e9ecef;">
                        <div class="progress-bar bg-success" role="progressbar" 
                             style="width: {{ $pctValidadas }}%" title="{{ $practica->horas_validadas }}h validadas"></div>
                        <div class="progress-bar bg-warning" role="progressbar"
                             style="width: {{ $pctRevision }}%" title="{{ $horasPendientesRevision }}h en revisión"></div>
                    </div>
                    <div class="d-flex justify-content-between mt-1" style="font-size: 0.72rem; color: #6c757d;">
                        <span><span class="text-success">■</span> {{ $practica->horas_validadas }}h validadas &nbsp; <span class="text-warning">■</span> {{ $horasPendientesRevision }}h en revisión</span>
                        <span>{{ $horasRestantes }}h restantes</span>
                    </div>
                    @if($notaMin > 0)
                    <div class="mt-1">
                        <div style="position: relative; height: 2px; background: transparent;">
                            <div style="position: absolute; left: {{ $notaMin }}%; width: 2px; height: 10px; top: -4px; background: #dc3545;" title="Mínimo requerido {{ $notaMin }}%"></div>
                        </div>
                    </div>
                    @endif
                </div>
            </div>

            <div class="small text-muted mt-2 pt-2 border-top d-flex justify-content-between align-items-center">
                <span>Duración estimada: <strong>{{ $practica->duracion_meses ?? 3 }} meses</strong> ({{ $practica->version_reglamento ?? 'v2_automatizado' }})</span>
                <span class="text-success fw-semibold"><i class="bi bi-shield-check me-1"></i>Reglamento UPDS</span>
            </div>
        </div>
    </div>
</div>

<!-- ══════════════════════════════════════════════════════════════════════════════ -->
<!-- SECCIÓN 2: HORAS Y ACTIVIDADES (BITÁCORA Y CRONOGRAMA) -->
<!-- ══════════════════════════════════════════════════════════════════════════════ -->
<div class="row g-4 mb-4">
    <!-- Bitácora de Horas Registradas -->
    <div class="col-12 col-lg-7">
        <div class="card border-0 shadow-sm rounded-4 p-4 h-100" id="registroHoras">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="fw-bold text-navy mb-0" style="color: var(--upds-navy);">
                    <i class="bi bi-clock-history text-primary me-2"></i>Bitácora de Horas Registradas
                </h6>
                <div class="d-flex align-items-center gap-1">
                    @if(in_array($practica->estado, ['en_curso', 'planificada']) && auth()->user()->isEstudiante())
                        @if($practica->esHistorica() || $practica->cronograma_aprobado)
                            <button class="btn btn-sm btn-upds py-1 px-3 rounded-pill fw-semibold" data-bs-toggle="modal" data-bs-target="#modalRegistrarHoras">
                                <i class="bi bi-plus-lg me-1"></i> Registrar horas
                            </button>
                        @else
                            <button class="btn btn-sm btn-secondary py-1 px-3 rounded-pill fw-semibold" disabled title="El cronograma debe ser aprobado por Coordinación">
                                <i class="bi bi-lock me-1"></i> Horas bloqueadas
                            </button>
                        @endif
                        <button class="btn btn-sm btn-outline-warning py-1 px-2 rounded-pill fw-semibold" data-bs-toggle="modal" data-bs-target="#modalJustificarAsistencia">
                            <i class="bi bi-shield-exclamation me-1"></i> Justificar
                        </button>
                    @endif
                    @if(auth()->user()->isCoordinador() || auth()->user()->isTutor())
                        @php $horasPend = $practica->registrosHoras->where('estado','pendiente')->count(); @endphp
                        @if($horasPend > 0 && auth()->user()->isTutor())
                            <span class="badge bg-warning text-dark fw-semibold">
                                <i class="bi bi-exclamation-circle me-1"></i>{{ $horasPend }} pendiente(s)
                            </span>
                        @endif
                        <button class="btn btn-sm btn-outline-secondary py-1 px-2 rounded-pill fw-semibold" data-bs-toggle="modal" data-bs-target="#modalGestionJustificaciones">
                            <i class="bi bi-list-check me-1"></i> Justificaciones ({{ $practica->justificaciones->count() }})
                        </button>
                    @endif
                </div>
            </div>

            <div class="table-responsive" style="max-height: 320px; overflow-y: auto;">
                <table class="table table-hover table-sm align-middle small mb-0">
                    <thead class="table-light sticky-top">
                        <tr>
                            <th>Fecha</th>
                            <th>Tiempo</th>
                            <th>Descripción</th>
                            <th>Estado</th>
                            @if(auth()->user()->isTutor())
                                <th class="text-end">Validar</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($practica->registrosHoras as $h)
                            @php
                                $hEnteras = floor($h->horas);
                                $minutos = round(($h->horas - $hEnteras) * 60);
                                $tiempoTexto = ($hEnteras > 0 ? "{$hEnteras}h" : '') . ($minutos > 0 ? " {$minutos}m" : ($hEnteras == 0 ? '0m' : ''));
                            @endphp
                            <tr>
                                <td class="text-nowrap">{{ $h->fecha->format('d/m/Y') }}</td>
                                <td class="text-nowrap">
                                    <strong class="text-dark">{{ $tiempoTexto }}</strong>
                                    <small class="text-muted d-block" style="font-size: 0.72rem;">({{ $h->horas }} hrs)</small>
                                </td>
                                <td class="text-truncate" style="max-width: 220px;" title="{{ $h->descripcion }}">{{ $h->descripcion }}</td>
                                <td>
                                    <span class="badge-{{ $h->estado }}">{{ ucfirst($h->estado) }}</span>
                                    <button type="button" class="btn btn-link p-0 ms-1 text-primary align-baseline"
                                            data-bs-toggle="modal" data-bs-target="#modalDetalleHora{{ $h->id }}"
                                            title="Consultar detalle completo de la jornada">
                                        <i class="bi bi-info-circle-fill"></i>
                                    </button>
                                </td>
                                @if(auth()->user()->isTutor())
                                    <td class="text-end text-nowrap">
                                        @if($h->estado === 'pendiente')
                                            <form action="{{ route('seguimiento.horas.validar', $h->id) }}" method="POST" class="d-inline">
                                                @csrf
                                                <input type="hidden" name="estado" value="validada">
                                                <button type="submit" class="btn btn-xs btn-outline-success p-1" title="Aprobar"><i class="bi bi-check-lg"></i></button>
                                            </form>
                                            <button type="button" class="btn btn-xs btn-outline-danger p-1" data-bs-toggle="modal" data-bs-target="#modalRechazarHora{{ $h->id }}" title="Rechazar"><i class="bi bi-x-lg"></i></button>
                                        @else
                                            <span class="text-muted" style="font-size: 0.7rem;">Revisado</span>
                                        @endif
                                    </td>
                                @endif
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center py-4 text-muted">
                                    <i class="bi bi-clock-slash d-block fs-3 mb-1 opacity-50"></i>
                                    No se han registrado jornadas de práctica todavía.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Cronograma de Actividades Planificadas -->
    <div class="col-12 col-lg-5">
        <div class="card border-0 shadow-sm rounded-4 p-4 h-100">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <h6 class="fw-bold text-navy mb-0" style="color: var(--upds-navy);">
                        <i class="bi bi-calendar-check text-primary me-2"></i>Cronograma de Actividades
                    </h6>
                    <small class="d-block mt-1">
                        @if($practica->cronograma_aprobado)
                            <span class="badge bg-success-subtle text-success border border-success-subtle"><i class="bi bi-check2-all me-1"></i>Aprobado</span>
                        @else
                            <span class="badge bg-warning-subtle text-dark border border-warning-subtle"><i class="bi bi-hourglass-split me-1"></i>Pendiente Aprobación</span>
                        @endif
                    </small>
                </div>
                <div class="d-flex align-items-center gap-1">
                    @if(auth()->user()->isCoordinador() && !$practica->cronograma_aprobado && $practica->actividades->count() > 0)
                        <form action="{{ route('seguimiento.aprobar-cronograma', $practica->id) }}" method="POST" class="d-inline">
                            @csrf
                            <button type="submit" class="btn btn-sm btn-success py-1 px-2 rounded-pill fw-semibold" title="Aprobar formalmente el cronograma">
                                <i class="bi bi-check-circle me-1"></i> Aprobar
                            </button>
                        </form>
                    @endif
                    @if(auth()->user()->isCoordinador() || (auth()->user()->isTutor() && $practica->tutor_id === auth()->user()->tutor?->id))
                        <button class="btn btn-sm btn-outline-primary py-1 px-3 rounded-pill fw-semibold" data-bs-toggle="modal" data-bs-target="#modalNuevaActividad">
                            <i class="bi bi-calendar-plus me-1"></i> Planificar
                        </button>
                    @endif
                </div>
            </div>

            <div class="list-group list-group-flush small" style="max-height: 320px; overflow-y: auto;">
                @forelse($practica->actividades as $act)
                    @php
                        $hoy = now()->toDateString();
                        $actVencida = $act->fecha_fin && $act->fecha_fin->format('Y-m-d') < $hoy && $act->estado !== 'completada';
                        $actEnPlazo = $act->fecha_fin && $act->fecha_fin->format('Y-m-d') >= $hoy;
                        $diasRestantes = $act->fecha_fin ? now()->diffInDays($act->fecha_fin, false) : null;
                    @endphp
                    <div class="list-group-item p-3 border rounded-3 mb-2 {{ $actVencida ? 'border-danger bg-danger-subtle' : 'bg-light' }}">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <strong class="text-dark">{{ $act->titulo }}</strong>
                            <div class="d-flex align-items-center gap-1">
                                <span class="badge-{{ $act->estado }}">{{ ucfirst(str_replace('_', ' ', $act->estado)) }}</span>
                                @if(auth()->user()->isCoordinador() || (auth()->user()->isTutor() && $practica->tutor_id === auth()->user()->tutor?->id))
                                    <button type="button" class="btn btn-xs btn-light border py-0 px-2" data-bs-toggle="modal" data-bs-target="#modalEditarActividad{{ $act->id }}" title="Editar Actividad">
                                        <i class="bi bi-pencil text-secondary"></i>
                                    </button>
                                @endif
                            </div>
                        </div>
                        @if($act->objetivo)
                            <div class="text-muted mb-1">{{ $act->objetivo }}</div>
                        @endif
                        @if($act->entregable_esperado)
                            <div class="text-primary small mb-1"><i class="bi bi-box-seam me-1"></i><strong>Entregable:</strong> {{ $act->entregable_esperado }}</div>
                        @endif

                        {{-- Fechas y estado temporal --}}
                        <div class="small mb-1" style="font-size: 0.75rem;">
                            <i class="bi bi-calendar-range me-1 text-secondary"></i>
                            <span class="text-secondary">{{ $act->fecha_inicio ? $act->fecha_inicio->format('d/m/Y') : '?' }} al </span>
                            <span class="{{ $actVencida ? 'text-danger fw-bold' : 'text-secondary' }}">
                                {{ $act->fecha_fin ? $act->fecha_fin->format('d/m/Y') : '?' }}
                            </span>
                            @if($actVencida)
                                <span class="badge bg-danger ms-1" style="font-size: 0.65rem;">Plazo vencido</span>
                            @elseif($diasRestantes !== null && $diasRestantes <= 14 && $act->estado !== 'completada')
                                <span class="badge bg-warning text-dark ms-1" style="font-size: 0.65rem;">
                                    {{ $diasRestantes > 0 ? $diasRestantes . ' días restantes' : 'Vence hoy' }}
                                </span>
                            @endif
                        </div>

                        {{-- Seguimiento Automatizado de Entrega de Informe --}}
                        @php
                            $estadoEntrega = $act->estado_entrega;
                            $infVinculado = $act->ultimo_informe;
                        @endphp
                        <div class="d-flex align-items-center justify-content-between small my-1 p-1 px-2 rounded-2" style="font-size: 0.72rem; background: rgba(0,0,0,0.03);">
                            <span class="text-muted"><i class="bi bi-file-earmark-text me-1"></i>Informe:</span>
                            @if($estadoEntrega === 'aprobado')
                                <span class="badge bg-success-subtle text-success border border-success-subtle">
                                    <i class="bi bi-check-circle me-1"></i>Entregado y Aprobado
                                </span>
                            @elseif($estadoEntrega === 'en_revision')
                                <span class="badge bg-primary-subtle text-primary border border-primary-subtle">
                                    <i class="bi bi-arrow-up-circle me-1"></i>Entregado (En revisión)
                                </span>
                            @elseif($estadoEntrega === 'observado')
                                <span class="badge bg-warning-subtle text-dark border border-warning-subtle">
                                    <i class="bi bi-exclamation-triangle me-1"></i>Observado (Subsanar)
                                </span>
                            @elseif($estadoEntrega === 'vencido')
                                <span class="badge bg-danger-subtle text-danger border border-danger-subtle fw-bold">
                                    <i class="bi bi-x-circle me-1"></i>Sin entrega (Plazo vencido)
                                </span>
                            @else
                                <span class="badge bg-light text-secondary border">
                                    <i class="bi bi-clock me-1"></i>Pendiente de entrega
                                </span>
                            @endif
                        </div>

                        {{-- Rúbrica y Evaluación de Actividad --}}
                        <div class="d-flex justify-content-between align-items-center mt-2 pt-2 border-top text-secondary" style="font-size: 0.75rem;">
                            <span><i class="bi bi-clock me-1"></i>{{ (int)$act->horas_planificadas }}h planificadas</span>
                            <div class="d-flex align-items-center gap-1">
                                @if($act->calcularNota() !== null)
                                    <span class="badge bg-success-subtle text-success border">Nota: {{ $act->calcularNota() }}/100</span>
                                @elseif($act->rubrica)
                                    <span class="badge bg-info-subtle text-info border">Rúbrica lista</span>
                                @elseif(auth()->user()->isTutor())
                                    <span class="badge bg-secondary-subtle text-secondary border">Sin rúbrica</span>
                                @endif

                                @if(auth()->user()->isTutor() && $practica->tutor_id === auth()->user()->tutor?->id)
                                    <button type="button" class="btn btn-xs btn-outline-primary py-0 px-2" data-bs-toggle="modal" data-bs-target="#modalRubrica{{ $act->id }}" title="Definir rúbrica">
                                        <i class="bi bi-list-check"></i> Rúbrica
                                    </button>
                                    @if($act->rubrica)
                                        <button type="button" class="btn btn-xs btn-primary py-0 px-2" data-bs-toggle="modal" data-bs-target="#modalEvaluarRubrica{{ $act->id }}" title="Evaluar criterios">
                                            <i class="bi bi-award"></i> Calificar
                                        </button>
                                    @endif
                                @endif
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="text-center text-muted small py-4">
                        <i class="bi bi-calendar-x d-block fs-3 mb-1 opacity-50"></i>
                        No hay actividades planificadas en el cronograma todavía.
                    </div>
                @endforelse
            </div>
        </div>
    </div>
</div>

<!-- ══════════════════════════════════════════════════════════════════════════════ -->
<!-- SECCIÓN 3: INFORMES Y OBSERVACIONES DEL TUTOR -->
<!-- ══════════════════════════════════════════════════════════════════════════════ -->
<div class="row g-4 mb-4">
    <!-- Reportes de Seguimiento -->
    <div class="col-12 col-lg-6">
        <div class="card border-0 shadow-sm rounded-4 p-4 h-100">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="fw-bold text-navy mb-0" style="color: var(--upds-navy);">
                    <i class="bi bi-journal-check text-primary me-2"></i>Reportes de Seguimiento (Informes)
                </h6>
                @if(auth()->user()->isEstudiante())
                    <button class="btn btn-sm btn-outline-primary py-1 px-3 rounded-pill fw-semibold" data-bs-toggle="modal" data-bs-target="#modalSubirInforme">
                        <i class="bi bi-plus-lg me-1"></i> Presentar reporte
                    </button>
                @endif
            </div>

            <div class="list-group list-group-flush" style="max-height: 380px; overflow-y: auto;">
                @forelse($practica->informes as $inf)
                    <div class="list-group-item px-3 py-2 border rounded-3 mb-2 bg-light">
                        <div class="d-flex justify-content-between align-items-start mb-1">
                            <div>
                                <div class="d-flex align-items-center gap-1 flex-wrap mb-1">
                                    <span class="badge bg-primary-subtle text-primary border" style="font-size: 0.72rem;">
                                        <i class="bi bi-calendar2-check me-1"></i>{{ $inf->actividad ? $inf->actividad->titulo : 'Actividad General' }}
                                    </span>
                                    <span class="badge-{{ $inf->estado }}">{{ ucfirst($inf->estado) }}</span>
                                    @if($inf->version > 1)
                                        <span class="badge bg-secondary-subtle text-dark border" style="font-size: 0.7rem;">v{{ $inf->version }}</span>
                                    @endif
                                    @if($inf->calificacion !== null)
                                        <span class="badge bg-success-subtle text-success border" style="font-size: 0.72rem;">Nota: {{ $inf->calificacion }}/100</span>
                                    @endif
                                </div>
                                <div class="fw-semibold text-dark small">{{ $inf->titulo }}</div>
                                <small class="text-muted" style="font-size: 0.75rem;">
                                    Período: {{ $inf->periodo_inicio->format('d/m/Y') }} al {{ $inf->periodo_fin->format('d/m/Y') }}
                                    @if($inf->actividad && $inf->actividad->fecha_fin)
                                        &bull; <span class="text-secondary">Fecha límite: {{ $inf->actividad->fecha_fin->format('d/m/Y') }}</span>
                                    @endif
                                </small>
                            </div>
                            <div class="d-flex align-items-center gap-1">
                                <a href="{{ route('seguimiento.informes.descargar', $inf->id) }}" class="btn btn-sm btn-light border text-primary" title="Descargar Reporte">
                                    <i class="bi bi-download"></i>
                                </a>
                                @if((auth()->user()->isTutor() || auth()->user()->isCoordinador()) && in_array($inf->estado, ['enviado', 'observado']))
                                    <form action="{{ route('seguimiento.informes.revisar', $inf->id) }}" method="POST" class="d-inline">
                                        @csrf
                                        <input type="hidden" name="estado" value="revisado">
                                        <button type="submit" class="btn btn-sm btn-success py-0 px-2" title="Marcar como Revisado y Conforme">
                                            <i class="bi bi-check-circle me-1"></i>Revisar
                                        </button>
                                    </form>
                                    <button type="button" class="btn btn-sm btn-warning text-dark py-0 px-2" data-bs-toggle="modal" data-bs-target="#modalObservarInforme{{ $inf->id }}" title="Observar Reporte">
                                        <i class="bi bi-exclamation-triangle me-1"></i>Observar
                                    </button>
                                @endif
                            </div>
                        </div>

                        @if($inf->estado === 'observado')
                            <div class="alert alert-warning py-2 px-3 small my-2 border border-warning rounded-3">
                                <div class="fw-semibold text-warning-emphasis mb-1">
                                    <i class="bi bi-exclamation-triangle-fill text-warning me-1"></i>Observación del Docente Tutor:
                                </div>
                                <div class="text-dark">{{ $inf->observaciones_tutor ?? 'Se requiere subsanar observaciones previas para la aprobación.' }}</div>
                                @if(auth()->user()->isEstudiante())
                                    <div class="mt-2 text-end">
                                        <button type="button" class="btn btn-sm btn-warning text-dark py-1 px-3 rounded-pill fw-semibold"
                                                data-bs-toggle="modal" data-bs-target="#modalSubirInforme"
                                                onclick="prepararCorreccionInforme({{ $inf->actividad_id ?? 'null' }}, '{{ addslashes($inf->titulo) }}')">
                                            <i class="bi bi-arrow-repeat me-1"></i>Subir Corrección (v{{ $inf->version + 1 }})
                                        </button>
                                    </div>
                                @endif
                            </div>
                        @endif

                        @if($inf->resumen)
                            <div class="text-muted small fst-italic mt-1" style="font-size: 0.8rem;">
                                {{ Str::limit($inf->resumen, 120) }}
                            </div>
                        @endif
                    </div>
                @empty
                    <div class="text-center text-muted small py-4">
                        <i class="bi bi-journal-x d-block fs-3 mb-1 opacity-50"></i>
                        No hay reportes de avance registrados aún.
                    </div>
                @endforelse
            </div>
        </div>
    </div>

    <!-- Observaciones del Tutor -->
    <div class="col-12 col-lg-6">
        <div class="card border-0 shadow-sm rounded-4 p-4 h-100">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="fw-bold text-navy mb-0" style="color: var(--upds-navy);">
                    <i class="bi bi-chat-left-dots text-primary me-2"></i>Observaciones y Sugerencias del Tutor
                </h6>
                @if(auth()->user()->isTutor() || auth()->user()->isCoordinador())
                    <button class="btn btn-sm btn-outline-secondary py-1 px-3 rounded-pill fw-semibold" data-bs-toggle="collapse" data-bs-target="#formNuevaObs">
                        <i class="bi bi-chat-left-text me-1"></i> Nueva nota
                    </button>
                @endif
            </div>

            <!-- Formulario colapsable para tutor -->
            <div class="collapse mb-3" id="formNuevaObs">
                <form action="{{ route('seguimiento.observaciones.store', $practica->id) }}" method="POST" class="p-3 bg-light rounded-3 border">
                    @csrf
                    <label class="form-label small fw-semibold">Detalle de la observación:</label>
                    <textarea name="detalle" class="form-control form-control-sm mb-2" rows="2" required placeholder="Escribe aquí tu observación o retroalimentación académica..."></textarea>
                    <div class="text-end">
                        <button type="submit" class="btn btn-sm btn-upds">Guardar Observación</button>
                    </div>
                </form>
            </div>

            <div class="list-group list-group-flush" style="max-height: 320px; overflow-y: auto;">
                @forelse($practica->observaciones as $obs)
                    <div class="list-group-item p-3 border rounded-3 mb-2 bg-light small">
                        <div class="text-dark mb-1">{{ $obs->detalle }}</div>
                        <div class="text-muted d-flex justify-content-between" style="font-size: 0.75rem;">
                            <span><i class="bi bi-person me-1"></i> {{ $obs->tutor->nombre_completo ?? 'Docente Tutor' }}</span>
                            <span>{{ $obs->fecha->format('d/m/Y H:i') }}</span>
                        </div>
                    </div>
                @empty
                    <div class="p-4 rounded-3 bg-light text-muted small text-center">
                        <i class="bi bi-chat-square-text d-block fs-3 mb-1 opacity-50"></i>
                        El estudiante aún no tiene observaciones registradas.
                    </div>
                @endforelse
            </div>
        </div>
    </div>
</div>

<!-- ══════════════════════════════════════════════════════════════════════════════ -->
<!-- SECCIÓN 4: EVALUACIONES (PARCIALES, VALORACIÓN ORGANIZACIÓN Y EVALUACIÓN FINAL) -->
<!-- ══════════════════════════════════════════════════════════════════════════════ -->
<div class="row g-4 mb-4">
    <!-- Evaluaciones Parciales y Valoración de la Organización -->
    <div class="col-12 col-lg-6">
        <!-- Evaluaciones Parciales -->
        <div class="card border-0 shadow-sm rounded-4 p-4 mb-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="fw-bold text-navy mb-0" style="color: var(--upds-navy);">
                    <i class="bi bi-clipboard2-check text-primary me-2"></i>Evaluaciones de Seguimiento (Parciales)
                </h6>
                @php
                    $esTutorAsignado = auth()->user()->isTutor() && $practica->tutor_id && $practica->tutor_id === auth()->user()->tutor?->id;
                @endphp
                @if($esTutorAsignado && !in_array($practica->estado, ['concluida', 'cancelada', 'abandonada']))
                    <button class="btn btn-sm btn-outline-primary py-1 px-3 rounded-pill fw-semibold" data-bs-toggle="modal" data-bs-target="#modalEvaluacionParcial">
                        <i class="bi bi-pencil-square me-1"></i> Calificar Parcial
                    </button>
                @endif
            </div>

            <div class="list-group list-group-flush">
                @forelse($practica->evaluacionesSeguimiento as $evP)
                    <div class="list-group-item p-3 border rounded-3 mb-2 bg-light small">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <strong class="text-dark">{{ $evP->periodo }}</strong>
                            <span class="badge bg-primary text-white">{{ $evP->calificacion }} / 100 &bull; {{ $evP->resultado }}</span>
                        </div>
                        <div class="text-muted" style="font-size: 0.75rem;">
                            Fecha: {{ $evP->fecha->format('d/m/Y') }} &bull; Docente: {{ $evP->tutor->nombre_completo ?? 'Tutor' }}
                        </div>
                        @if($evP->comentario)
                            <div class="mt-2 text-secondary fst-italic" style="font-size: 0.8rem;">
                                "{{ $evP->comentario }}"
                            </div>
                        @endif
                    </div>
                @empty
                    <div class="text-center text-muted small py-3">No se han registrado evaluaciones de seguimiento aún.</div>
                @endforelse
            </div>
        </div>

        <!-- Valoración de la Organización Receptora -->
        <div class="card border-0 shadow-sm rounded-4 p-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="fw-bold text-navy mb-0" style="color: var(--upds-navy);">
                    <i class="bi bi-buildings text-primary me-2"></i>Valoración de la Organización Receptora
                </h6>
                @if(auth()->user()->isCoordinador())
                    <button class="btn btn-sm btn-outline-primary py-1 px-3 rounded-pill fw-semibold" data-bs-toggle="modal" data-bs-target="#modalValoracionOrganizacion">
                        <i class="bi bi-pencil-square me-1"></i> {{ $practica->evaluacionOrganizacion ? 'Actualizar Valoración' : '+ Registrar Valoración' }}
                    </button>
                @endif
            </div>

            @if($practica->evaluacionOrganizacion)
                <div class="p-3 bg-light rounded-3 small">
                    <div class="row g-2">
                        <div class="col-sm-4 text-muted">Responsable Empresa:</div>
                        <div class="col-sm-8 fw-bold text-dark">{{ $practica->evaluacionOrganizacion->responsable?->nombre_completo ?? 'Responsable institucional' }}</div>

                        <div class="col-sm-4 text-muted">Fecha de Emisión:</div>
                        <div class="col-sm-8">{{ $practica->evaluacionOrganizacion->fecha ? $practica->evaluacionOrganizacion->fecha->format('d/m/Y') : '-' }}</div>

                        <div class="col-sm-4 text-muted">Desempeño / Cumplimiento:</div>
                        <div class="col-sm-8">
                            <span class="badge bg-success-subtle text-success border">{{ $practica->evaluacionOrganizacion->desempeno }}</span> &bull;
                            <span class="badge bg-primary-subtle text-primary border">{{ $practica->evaluacionOrganizacion->cumplimiento }}</span>
                        </div>

                        <div class="col-sm-4 text-muted">Comentarios Empresa:</div>
                        <div class="col-sm-8 fst-italic text-secondary">{{ $practica->evaluacionOrganizacion->comentario }}</div>

                        @if($practica->evaluacionOrganizacion->recomendacion)
                            <div class="col-sm-4 text-muted">Recomendación:</div>
                            <div class="col-sm-8 text-dark">{{ $practica->evaluacionOrganizacion->recomendacion }}</div>
                        @endif
                    </div>
                </div>
            @else
                <div class="text-center text-muted small py-3 bg-light rounded-3">
                    Aún no se ha registrado la valoración del responsable de la organización receptora.
                </div>
            @endif
        </div>
    </div>

    <!-- Evaluación Final del Docente Tutor -->
    <div class="col-12 col-lg-6">
        <div class="card border-0 shadow-sm rounded-4 p-4 h-100">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="fw-bold text-navy mb-0" style="color: var(--upds-navy);">
                    <i class="bi bi-award text-primary me-2"></i>Evaluación Final del Tutor
                </h6>
                @if($practica->evaluacionFinal)
                    <span class="badge {{ $practica->evaluacionFinal->resultado === 'Aprobado' ? 'bg-success' : 'bg-danger' }}">
                        {{ $practica->evaluacionFinal->resultado }}
                    </span>
                @endif
            </div>

            @if($practica->evaluacionFinal)
                <div class="p-3 bg-light rounded-3 small mb-3">
                    <div class="row g-2">
                        <div class="col-sm-4 text-muted">Calificación Final:</div>
                        <div class="col-sm-8">
                            <strong class="{{ (float)$practica->evaluacionFinal->calificacion >= 51 ? 'text-success' : 'text-danger' }} fs-5">
                                {{ $practica->evaluacionFinal->calificacion }}
                            </strong>
                            <span class="text-muted">/ 100</span>
                            <div class="progress mt-1" style="height:6px;">
                                <div class="progress-bar {{ (float)$practica->evaluacionFinal->calificacion >= 51 ? 'bg-success' : 'bg-danger' }}"
                                     style="width:{{ min(100,$practica->evaluacionFinal->calificacion) }}%"></div>
                            </div>
                        </div>

                        <div class="col-sm-4 text-muted">Resultado:</div>
                        <div class="col-sm-8">
                            <span class="badge {{ $practica->evaluacionFinal->resultado === 'Aprobado' ? 'bg-success' : 'bg-danger' }} fw-semibold">
                                <i class="bi {{ $practica->evaluacionFinal->resultado === 'Aprobado' ? 'bi-check-circle' : 'bi-x-circle' }} me-1"></i>
                                {{ $practica->evaluacionFinal->resultado }}
                            </span>
                            <small class="text-muted ms-1">(Nota mínima aprobatoria: 51/100)</small>
                        </div>

                        <div class="col-sm-4 text-muted">Fecha:</div>
                        <div class="col-sm-8">{{ $practica->evaluacionFinal->fecha ? $practica->evaluacionFinal->fecha->format('d/m/Y') : '-' }}</div>

                        @if($practica->evaluacionFinal->comentario)
                            <div class="col-12 mt-2 pt-2 border-top">
                                <div class="text-muted small mb-1">Dictamen del Docente Tutor:</div>
                                <div class="fst-italic text-secondary">{{ $practica->evaluacionFinal->comentario }}</div>
                            </div>
                        @endif
                    </div>
                </div>
                @if(auth()->user()->isTutor() && $practica->estado !== 'concluida')
                    <button type="button" class="btn btn-outline-primary w-100 rounded-pill py-2 fw-semibold" data-bs-toggle="modal" data-bs-target="#modalEvaluacionFinal">
                        <i class="bi bi-pencil me-1"></i> Actualizar Evaluación Final
                    </button>
                @endif
            @else
                @php
                    $actividadesEvaluadas = $practica->actividades->filter(fn($a) => $a->calcularNota() !== null)->count();
                    $totalActividades = $practica->actividades->count();
                    $pctActividades = $totalActividades > 0 ? round($actividadesEvaluadas * 100 / $totalActividades) : 0;
                @endphp
                <div class="p-3 bg-light rounded-3 small mb-3">
                    <div class="fw-bold text-dark mb-2"><i class="bi bi-hourglass-split text-warning me-1"></i>Evaluación Final Pendiente</div>
                    <p class="small text-muted mb-2">El tutor registrará la calificación final una vez evaluadas las actividades del cronograma mediante rúbrica.</p>
                    <div class="mb-1 d-flex justify-content-between">
                        <small class="text-muted">Actividades evaluadas por rúbrica:</small>
                        <small class="fw-semibold">{{ $actividadesEvaluadas }} / {{ $totalActividades }}</small>
                    </div>
                    <div class="progress" style="height: 8px;">
                        <div class="progress-bar {{ $pctActividades >= 100 ? 'bg-success' : 'bg-primary' }}" style="width: {{ $pctActividades }}%"></div>
                    </div>
                    @if($pctActividades < 100)
                        <small class="text-muted d-block mt-1">Evalúa todas las actividades del cronograma antes de emitir la evaluación final.</small>
                    @else
                        <small class="text-success d-block mt-1"><i class="bi bi-check-circle me-1"></i>Todas las actividades evaluadas. Puedes registrar la evaluación final.</small>
                    @endif
                </div>
                @if(auth()->user()->isTutor() && $practica->estado !== 'concluida')
                    <button type="button" class="btn btn-upds w-100 rounded-pill py-2 fw-semibold mt-auto" data-bs-toggle="modal" data-bs-target="#modalEvaluacionFinal">
                        <i class="bi bi-check2-circle me-1"></i> Registrar Evaluación Final del Tutor
                    </button>
                @endif
            @endif
        </div>
    </div>
</div>

<!-- ══════════════════════════════════════════════════════════════════════════════ -->
<!-- SECCIÓN 5: RESOLUCIÓN Y CIERRE DE PRÁCTICA (COORDINACIÓN ACADÉMICA) -->
<!-- ══════════════════════════════════════════════════════════════════════════════ -->
@if(auth()->user()->isCoordinador() && !in_array($practica->estado, ['concluida', 'cancelada', 'abandonada']))
<div class="row g-4 mb-4">
    <div class="col-12">
        <div class="card border-0 shadow-sm rounded-4 p-4" style="background: linear-gradient(to right, #ffffff, #f8fafc); border-left: 4px solid var(--upds-primary) !important;">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                <div>
                    <h6 class="fw-bold text-navy mb-1" style="color: var(--upds-navy);">
                        <i class="bi bi-shield-check text-primary me-2"></i>Resolución y Cierre de Práctica (Coordinación Académica)
                    </h6>
                    <small class="text-muted">Dictamen resolutivo final del trámite una vez revisado el cómputo de horas, informes y evaluaciones.</small>
                </div>
                <div class="d-flex gap-2 flex-wrap">
                    <button type="button" class="btn btn-sm btn-success rounded-pill px-4 fw-semibold shadow-sm" data-bs-toggle="modal" data-bs-target="#modalConcluirPractica">
                        <i class="bi bi-award me-1"></i> Finalizar y Concluir Práctica
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-warning text-dark rounded-pill px-3" data-bs-toggle="modal" data-bs-target="#modalAbandonarPractica">
                        <i class="bi bi-slash-circle me-1"></i> Declarar Abandono
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-danger rounded-pill px-3" data-bs-toggle="modal" data-bs-target="#modalCancelarPractica">
                        <i class="bi bi-x-circle me-1"></i> Cancelar Práctica
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
@endif
<!-- Modal Asignar Tutor -->
<div class="modal fade" id="modalAsignarTutor" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow rounded-4">
            <div class="modal-header text-white" style="background-color: var(--upds-navy);">
                <h6 class="modal-title fw-bold">Asignar Docente Tutor</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('seguimiento.asignar-tutor', $practica->id) }}" method="POST">
                @csrf
                <div class="modal-body">
                    <label class="form-label small fw-semibold">Seleccionar Docente Tutor *</label>
                    <select name="tutor_id" class="form-select" required>
                        <option value="">-- Elige un tutor --</option>
                        @php $listaTutores = $tutores ?? \App\Models\DocenteTutor::where('activo', true)->get(); @endphp
                        @foreach($listaTutores as $t)
                            <option value="{{ $t->id }}" {{ $practica->tutor_id == $t->id ? 'selected' : '' }}>
                                {{ $t->nombre_completo }} ({{ $t->especialidad }})
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-sm btn-light" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-sm btn-upds">Guardar Asignación</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Registrar Horas con Selector Claro de Horas y Minutos -->
<div class="modal fade" id="modalRegistrarHoras" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow rounded-4">
            <div class="modal-header text-white" style="background-color: var(--upds-navy);">
                <h6 class="modal-title fw-bold">
                    <i class="bi bi-clock-history me-2"></i>Registrar Jornada de Práctica
                </h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('seguimiento.horas.store', $practica->id) }}" method="POST">
                @csrf
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Fecha de la jornada *</label>
                        <input type="date" name="fecha" class="form-control" value="{{ date('Y-m-d') }}" max="{{ date('Y-m-d') }}" required>
                        <small class="text-muted">No se permiten fechas futuras ni anteriores al inicio de la práctica.</small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold d-block">Duración de la jornada *</label>
                        <div class="row g-2">
                            <div class="col-7">
                                <div class="input-group">
                                    <span class="input-group-text bg-light text-muted small"><i class="bi bi-hourglass-split me-1"></i> Horas</span>
                                    <select name="duracion_horas" id="selectHorasJornada" class="form-select" onchange="actualizarCalculoHoras()">
                                        @php $maxConfig = (int)config('practicas.max_horas_diarias', 4); @endphp
                                        @for($i = 0; $i <= $maxConfig; $i++)
                                            <option value="{{ $i }}" {{ $i == 4 ? 'selected' : '' }}>{{ $i }} {{ $i == 1 ? 'hora' : 'horas' }}</option>
                                        @endfor
                                    </select>
                                </div>
                            </div>
                            <div class="col-5">
                                <div class="input-group">
                                    <span class="input-group-text bg-light text-muted small">Min</span>
                                    <select name="duracion_minutos" id="selectMinutosJornada" class="form-select" onchange="actualizarCalculoHoras()">
                                        <option value="0" selected>00 min</option>
                                        <option value="15">15 min</option>
                                        <option value="30">30 min (media hora)</option>
                                        <option value="45">45 min</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- Valor decimal real transmitido al servidor -->
                        <input type="hidden" name="horas" id="inputHorasDecimal" value="4.0">

                        <!-- Explicación visual en vivo para que el estudiante nunca se confunda -->
                        <div class="alert alert-info py-2 px-3 small mt-2 mb-2 d-flex align-items-center justify-content-between">
                            <div>
                                <i class="bi bi-info-circle me-1 text-primary"></i>
                                Total a registrar: <strong id="textoHorasResumen" class="text-primary">4 horas (4.0 hrs académicas)</strong>
                            </div>
                            <span class="badge bg-primary text-white" id="badgeHorasDecimal">4.0 h</span>
                        </div>

                        <!-- Atajos rápidos -->
                        <div class="d-flex align-items-center gap-1 flex-wrap">
                            <span class="text-muted small me-1">Atajos rápidos:</span>
                            <button type="button" class="btn btn-xs btn-outline-secondary py-0 px-2 rounded-pill small" onclick="setDuracion(1, 0)">1h</button>
                            <button type="button" class="btn btn-xs btn-outline-secondary py-0 px-2 rounded-pill small" onclick="setDuracion(2, 0)">2h</button>
                            <button type="button" class="btn btn-xs btn-outline-secondary py-0 px-2 rounded-pill small" onclick="setDuracion(3, 0)">3h</button>
                            <button type="button" class="btn btn-xs btn-outline-secondary py-0 px-2 rounded-pill small" onclick="setDuracion(4, 0)">4h (Máx diario)</button>
                        </div>
                        <small class="text-muted d-block mt-1">Configuración del proyecto: máximo 4 horas por jornada diaria.</small>
                    </div>

                    @if($practica->actividades->count() > 0)
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Actividad del Cronograma Asociada</label>
                        <select name="actividad_id" class="form-select form-select-sm">
                            <option value="">-- Sin vincular a actividad específica --</option>
                            @foreach($practica->actividades->whereIn('estado', ['pendiente','en_proceso']) as $actH)
                                <option value="{{ $actH->id }}" {{ $actH->estado === 'en_proceso' ? 'selected' : '' }}>
                                    {{ $actH->titulo }} ({{ ucfirst(str_replace('_',' ',$actH->estado)) }})
                                </option>
                            @endforeach
                        </select>
                        <small class="text-muted">Vincula esta jornada a una actividad del cronograma para mejor trazabilidad.</small>
                    </div>
                    @endif
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Descripción detallada de actividades *</label>
                        <textarea name="descripcion" class="form-control" rows="3" required placeholder="Describe las tareas, funciones o proyectos desarrollados durante la jornada..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-sm btn-light rounded-pill px-3" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-sm btn-upds rounded-pill px-4">
                        <i class="bi bi-save me-1"></i> Registrar Jornada
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Subir Informe de Avance -->
<div class="modal fade" id="modalSubirInforme" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow rounded-4">
            <div class="modal-header text-white" style="background-color: var(--upds-navy);">
                <h6 class="modal-title fw-bold"><i class="bi bi-file-earmark-arrow-up me-2"></i>Presentar Informe de Avance</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('seguimiento.informes.store', $practica->id) }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Actividad del Cronograma Asociada *</label>
                        <select name="actividad_id" id="selectActividadInforme" class="form-select" required onchange="actualizarFechasPorActividad(this)">
                            <option value="">-- Seleccionar actividad del cronograma --</option>
                            @foreach($practica->actividades as $act)
                                @php
                                    $hoy = now()->toDateString();
                                    $vencida = $act->fecha_fin && $act->fecha_fin->format('Y-m-d') < $hoy;
                                    $ultimoInf = $act->informes()->orderBy('version', 'desc')->first();
                                    $esObservado = $ultimoInf && $ultimoInf->estado === 'observado';
                                    $yaAprobado = $ultimoInf && $ultimoInf->estado === 'revisado';
                                    $enRevision = $ultimoInf && $ultimoInf->estado === 'enviado';
                                    $bloqueada = ($vencida && !$esObservado) || $yaAprobado || $enRevision;
                                @endphp
                                <option value="{{ $act->id }}"
                                        {{ $bloqueada ? 'disabled' : '' }}
                                        data-inicio="{{ $act->fecha_inicio ? $act->fecha_inicio->format('Y-m-d') : '' }}"
                                        data-fin="{{ $act->fecha_fin ? $act->fecha_fin->format('Y-m-d') : '' }}"
                                        data-titulo="{{ $act->titulo }}"
                                        data-entregable="{{ $act->entregable_esperado }}">
                                    {{ $act->titulo }}
                                    @if($yaAprobado)
                                        [REVISADO - APROBADO]
                                    @elseif($enRevision)
                                        [EN REVISIÓN DEL TUTOR]
                                    @elseif($esObservado)
                                        [OBSERVADO - SUBIR CORRECCIÓN v{{ $ultimoInf->version + 1 }}]
                                    @elseif($vencida)
                                        [PLAZO VENCIDO: {{ $act->fecha_fin->format('d/m/Y') }} - BLOQUEADO]
                                    @else
                                        (Fecha límite: {{ $act->fecha_fin ? $act->fecha_fin->format('d/m/Y') : 'Sin fecha' }})
                                    @endif
                                </option>
                            @endforeach
                        </select>
                        <small class="text-muted">Cada informe debe corresponder a una actividad planificada con su respectiva fecha límite.</small>
                    </div>

                    <div id="infoActividadSeleccionada" class="alert alert-info py-2 small mb-3 d-none">
                        <i class="bi bi-calendar-event me-1"></i>
                        <span id="textoInfoActividad"></span>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Título del Informe *</label>
                        <input type="text" name="titulo" id="inputTituloInforme" class="form-control" required placeholder="Ej. Informe Técnico N° 1 - Análisis y Modelado">
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label small fw-semibold">Período Inicio *</label>
                            <input type="date" name="periodo_inicio" id="inputPeriodoInicio" class="form-control" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-semibold">Fecha Fin (Límite de Entrega) *</label>
                            <input type="date" name="periodo_fin" id="inputPeriodoFin" class="form-control" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Resumen de Actividades y Logros *</label>
                        <textarea name="resumen" class="form-control" rows="3" required placeholder="Sintetiza las tareas ejecutadas, entregables desarrollados y resultados obtenidos..."></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Archivo Adjunto (Obligatorio - PDF / DOC / DOCX - Máx. 10 MB) *</label>
                        <input type="file" name="archivo" accept=".pdf,.doc,.docx" class="form-control" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-sm btn-light" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-sm btn-upds">Enviar Informe</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Registrar Evaluación Final (Automatizada por Rúbricas de Actividades) -->
@php
    $totalActs = $practica->actividades->count();
    $actividadesConNota = $practica->actividades->filter(fn($a) => $a->calcularNota() !== null);
    $actsConNotaCount = $actividadesConNota->count();
    $todasEvaluadas = ($totalActs > 0 && $actsConNotaCount === $totalActs);
    $promedioFinalCalc = $actividadesConNota->isNotEmpty() ? round($actividadesConNota->avg(fn($a) => $a->calcularNota()), 2) : (optional($practica->evaluacionFinal)->calificacion ?? 0.0);
    $resultadoFinalCalc = ($promedioFinalCalc >= 51.0) ? 'Aprobado' : 'Reprobado';
@endphp
<div class="modal fade" id="modalEvaluacionFinal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow rounded-4">
            <div class="modal-header bg-primary text-white">
                <h6 class="modal-title fw-bold"><i class="bi bi-award me-2"></i>Evaluación Final del Docente Tutor (Automatizada)</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('seguimiento.evaluacion.store', $practica->id) }}" method="POST">
                @csrf
                <div class="modal-body p-4">
                    <div class="alert alert-info py-2 small mb-3">
                        <i class="bi bi-info-circle me-1"></i>
                        La calificación y el resultado final se calculan automáticamente del promedio de las actividades del cronograma evaluadas mediante rúbrica técnica.
                    </div>

                    @if(!$todasEvaluadas)
                        <div class="alert alert-warning py-2 px-3 small mb-3 border border-warning rounded-3">
                            <i class="bi bi-exclamation-triangle-fill text-warning me-1"></i>
                            <strong>Atención - Actividades pendientes:</strong> Existen actividades del cronograma aún sin calificar ({{ $actsConNotaCount }} de {{ $totalActs }} evaluadas). Debes calificar cada actividad con su respectiva rúbrica para habilitar la confirmación de la evaluación final.
                        </div>
                    @endif

                    <!-- Resumen de Actividades Evaluadas -->
                    <h6 class="fw-bold text-navy mb-2" style="color: var(--upds-navy);"><i class="bi bi-journal-check me-1"></i>Desglose de Actividades del Cronograma</h6>
                    <div class="table-responsive mb-3">
                        <table class="table table-sm table-bordered align-middle small mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Actividad Planificada</th>
                                    <th>Período</th>
                                    <th>Horas</th>
                                    <th class="text-center">Nota Rúbrica</th>
                                    <th class="text-center">Estado</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($practica->actividades as $act)
                                    @php $notaAct = $act->calcularNota(); @endphp
                                    <tr>
                                        <td><strong>{{ $act->titulo }}</strong></td>
                                        <td>{{ $act->fecha_inicio ? $act->fecha_inicio->format('d/m/Y') : '-' }} al {{ $act->fecha_fin ? $act->fecha_fin->format('d/m/Y') : '-' }}</td>
                                        <td>{{ (int)$act->horas_planificadas }}h</td>
                                        <td class="text-center fw-bold">
                                            @if($notaAct !== null)
                                                <span class="badge bg-success-subtle text-success border">{{ $notaAct }} / 100</span>
                                            @else
                                                <span class="badge bg-warning-subtle text-dark border"><i class="bi bi-hourglass-split me-1"></i>Pendiente</span>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            <span class="badge-{{ $act->estado }}">{{ ucfirst(str_replace('_', ' ', $act->estado)) }}</span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="5" class="text-center text-muted">Sin actividades en cronograma.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="row g-3 mb-3 bg-light p-3 rounded-3 border">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold text-dark">
                                Calificación Final Automatizada
                            </label>
                            <div class="input-group">
                                @if($todasEvaluadas)
                                    <input type="text" name="calificacion" class="form-control fw-bold text-success fs-5 bg-white" value="{{ $promedioFinalCalc }}" readonly>
                                    <span class="input-group-text bg-white">/ 100</span>
                                @else
                                    <input type="text" name="calificacion" class="form-control fw-bold text-muted fs-6 bg-white" value="Pendiente de evaluación completa" readonly>
                                    <span class="input-group-text bg-light text-muted">-- / 100</span>
                                @endif
                            </div>
                            <small class="text-muted" style="font-size: 0.75rem;">
                                @if($todasEvaluadas)
                                    Promedio consolidado de las {{ $totalActs }} actividades del cronograma.
                                @else
                                    Requiere calificar las {{ $totalActs }} actividades (actualmente {{ $actsConNotaCount }} evaluada(s)).
                                @endif
                            </small>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold text-dark">Resultado Académico Final</label>
                            @if($todasEvaluadas)
                                <input type="hidden" name="resultado" value="{{ $resultadoFinalCalc }}">
                                <div class="form-control bg-white d-flex align-items-center">
                                    <span class="badge {{ $resultadoFinalCalc === 'Aprobado' ? 'bg-success' : 'bg-danger' }} fs-6">
                                        <i class="bi {{ $resultadoFinalCalc === 'Aprobado' ? 'bi-check-circle' : 'bi-x-circle' }} me-1"></i>
                                        {{ $resultadoFinalCalc }}
                                    </span>
                                    <span class="ms-2 small text-muted">({{ $resultadoFinalCalc === 'Aprobado' ? 'Nota reglamentaria >= 51 pts' : 'Nota < 51 pts' }})</span>
                                </div>
                                <small class="text-muted" style="font-size: 0.75rem;">Determinación reglamentaria automática.</small>
                            @else
                                <input type="hidden" name="resultado" value="Pendiente">
                                <div class="form-control bg-white d-flex align-items-center">
                                    <span class="badge bg-warning text-dark fs-6">
                                        <i class="bi bi-hourglass-split me-1"></i>
                                        Pendiente
                                    </span>
                                    <span class="ms-2 small text-muted">(Actividades en curso)</span>
                                </div>
                                <small class="text-muted" style="font-size: 0.75rem;">El resultado se generará al calificar todas las actividades.</small>
                            @endif
                        </div>
                    </div>

                    <div class="mb-2">
                        <label class="form-label small fw-semibold">Dictamen / Opinión Evaluativa del Docente Tutor *</label>
                        <textarea name="comentario" class="form-control" rows="3" required placeholder="Dictamen académico final del tutor sobre el desempeño profesional y competencias demostradas...">{{ optional($practica->evaluacionFinal)->comentario ?? 'El practicante demostró solvencia técnica, responsabilidad y cumplimiento de los objetivos planteados.' }}</textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-sm btn-light rounded-pill px-3" data-bs-dismiss="modal">Cancelar</button>
                    @if($todasEvaluadas)
                        <button type="submit" class="btn btn-sm btn-primary rounded-pill px-4">
                            <i class="bi bi-award me-1"></i> Confirmar Evaluación Final
                        </button>
                    @else
                        <button type="button" class="btn btn-sm btn-secondary rounded-pill px-4" disabled title="Debes evaluar todas las actividades del cronograma primero">
                            <i class="bi bi-lock me-1"></i> Calificar Actividades Primero
                        </button>
                    @endif
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Registrar Evaluación Parcial mediante Rúbrica -->
<div class="modal fade" id="modalEvaluacionParcial" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow rounded-4">
            <div class="modal-header text-white" style="background-color: var(--upds-navy);">
                <h6 class="modal-title fw-bold"><i class="bi bi-clipboard2-check me-2"></i>Registrar Evaluación Parcial / Rúbrica de Actividad</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('seguimiento.evaluacion-parcial.store', $practica->id) }}" method="POST">
                @csrf
                <div class="modal-body p-4">
                    <div class="alert alert-info py-2 small mb-3">
                        <i class="bi bi-info-circle me-1"></i>
                        Selecciona la actividad a evaluar. Las notas se calculan automáticamente aplicando las ponderaciones (%) de la rúbrica técnica.
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Actividad del Cronograma a Evaluar *</label>
                        <select name="actividad_id" id="selectActividadParcial" class="form-select" required onchange="cambiarActividadParcial(this)">
                            <option value="">-- Seleccionar actividad del cronograma --</option>
                            @foreach($practica->actividades as $act)
                                <option value="{{ $act->id }}"
                                        data-rubrica-id="{{ $act->rubrica ? $act->rubrica->id : '' }}"
                                        data-tiene-rubrica="{{ $act->rubrica ? '1' : '0' }}"
                                        data-titulo="{{ $act->titulo }}">
                                    {{ $act->titulo }} ({{ $act->rubrica ? 'Rúbrica: ' . $act->rubrica->criterios->count() . ' criterios' : 'Sin rúbrica' }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <input type="hidden" name="rubrica_id" id="inputRubricaIdParcial" value="">

                    <!-- Criterios dinámicos por actividad -->
                    @foreach($practica->actividades as $act)
                        @if($act->rubrica)
                            <div class="seccion-criterios-actividad d-none" id="criteriosActividad{{ $act->id }}">
                                <h6 class="fw-bold text-navy mb-2" style="color: var(--upds-navy);">
                                    <i class="bi bi-card-checklist me-1"></i>Criterios de Evaluación: {{ $act->rubrica->titulo }}
                                </h6>
                                <div class="mb-3">
                                    <label class="form-label small fw-semibold">Informe de Avance Vinculado (Opcional)</label>
                                    <select name="informe_avance_id" class="form-select form-select-sm">
                                        <option value="">-- Sin vincular a informe --</option>
                                        @foreach($act->informes as $inf)
                                            <option value="{{ $inf->id }}">{{ $inf->titulo }} ({{ $inf->periodo_inicio->format('d/m/Y') }})</option>
                                        @endforeach
                                    </select>
                                </div>

                                @foreach($act->rubrica->criterios as $crit)
                                    <div class="p-3 mb-2 border rounded-3 bg-light">
                                        <div class="d-flex justify-content-between align-items-center mb-1">
                                            <strong class="text-dark small">{{ $crit->nombre }}</strong>
                                            <span class="badge bg-primary-subtle text-primary border">{{ (int)$crit->ponderacion }}% ponderación</span>
                                        </div>
                                        @if($crit->descripcion)
                                            <div class="text-muted small mb-2" style="font-size: 0.75rem;">{{ $crit->descripcion }}</div>
                                        @endif
                                        <div class="row g-2 align-items-center">
                                            <div class="col-sm-5">
                                                <div class="input-group input-group-sm">
                                                    <span class="input-group-text">Calificación</span>
                                                    <input type="number" step="0.5" min="0" max="100" maxlength="5"
                                                           name="puntuaciones[{{ $crit->id }}]"
                                                           class="form-control input-puntuacion-parcial input-act-{{ $act->id }} input-calificacion-rubrica"
                                                           data-pond="{{ $crit->ponderacion }}"
                                                           data-act-id="{{ $act->id }}"
                                                           placeholder="0 a 100"
                                                           oninput="validarNotaInput(this); recalcularNotaParcial({{ $act->id }})">
                                                </div>
                                            </div>
                                            <div class="col-sm-7">
                                                <input type="text" name="comentarios[{{ $crit->id }}]" class="form-control form-control-sm" placeholder="Comentario u observación sobre el criterio...">
                                            </div>
                                        </div>
                                    </div>
                                @endforeach

                                <!-- Visualizador de nota calculada en vivo -->
                                <div class="p-3 mt-3 rounded-3 border bg-white shadow-sm">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <span class="small text-muted d-block">Nota Ponderada Calculada:</span>
                                            <strong class="fs-4 text-primary" id="lblNotaParcial{{ $act->id }}">0.00 / 100</strong>
                                        </div>
                                        <div class="text-end">
                                            <span class="small text-muted d-block">Resultado Automático:</span>
                                            <span id="badgeResultadoParcial{{ $act->id }}" class="badge bg-secondary fs-6">Pendiente</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endif
                    @endforeach

                    <div id="avisoSinRubrica" class="alert alert-warning small py-2 d-none">
                        <i class="bi bi-exclamation-triangle me-1"></i>
                        Esta actividad no tiene rúbrica configurada aún. Puedes definir su rúbrica desde la lista de actividades del cronograma.
                    </div>

                    <div class="row g-2 mt-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Período / Título de Evaluación *</label>
                            <input type="text" name="periodo" id="inputPeriodoParcial" class="form-control form-control-sm" required placeholder="Ej. 1er Parcial - Análisis y Diseño BD">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Fecha de Evaluación *</label>
                            <input type="date" name="fecha" class="form-control form-control-sm" value="{{ date('Y-m-d') }}" required>
                        </div>
                    </div>

                    <div class="mt-3">
                        <label class="form-label small fw-semibold">Retroalimentación / Comentario General</label>
                        <textarea name="comentario" class="form-control form-control-sm" rows="2" placeholder="Observaciones pedagógicas para el estudiante..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-sm btn-light rounded-pill px-3" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-sm btn-upds rounded-pill px-4">
                        <i class="bi bi-check2-circle me-1"></i> Guardar Evaluación Parcial
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modales para Observar Reportes de Avance -->
@foreach($practica->informes as $inf)
    <div class="modal fade" id="modalObservarInforme{{ $inf->id }}" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow rounded-4">
                <div class="modal-header bg-warning text-dark">
                    <h6 class="modal-title fw-bold">Observar Reporte de Avance</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form action="{{ route('seguimiento.informes.revisar', $inf->id) }}" method="POST">
                    @csrf
                    <input type="hidden" name="estado" value="observado">
                    <div class="modal-body">
                        <p class="small text-muted mb-2">Informe: <strong>{{ $inf->titulo }}</strong></p>
                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Observación Detallada (Requerida) *</label>
                            <textarea name="observacion" class="form-control" rows="4" required placeholder="Indica detalladamente los puntos a corregir o completar por el estudiante..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-sm btn-light" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-sm btn-warning text-dark fw-semibold">Registrar Observación</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endforeach

<!-- Modales de Detalle Informativo para cada Registro de Horas (RF-11) -->
@foreach($practica->registrosHoras as $h)
    @php
        $hEnterasDet = floor($h->horas);
        $minutosDet = round(($h->horas - $hEnterasDet) * 60);
        $tiempoTextoDet = ($hEnterasDet > 0 ? "{$hEnterasDet}h" : '') . ($minutosDet > 0 ? " {$minutosDet}m" : ($hEnterasDet == 0 ? '0m' : ''));
    @endphp
    <div class="modal fade" id="modalDetalleHora{{ $h->id }}" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow rounded-4">
                <div class="modal-header text-white" style="background-color: var(--upds-navy);">
                    <h6 class="modal-title fw-bold">
                        <i class="bi bi-info-circle me-2"></i>Detalle de Jornada de Práctica
                    </h6>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4 small">
                    <div class="p-3 bg-light rounded-3 mb-3">
                        <div class="row g-2">
                            <div class="col-sm-4 text-muted">Fecha de jornada:</div>
                            <div class="col-sm-8 fw-bold text-dark">{{ $h->fecha->format('d/m/Y') }}</div>

                            <div class="col-sm-4 text-muted">Duración registrada:</div>
                            <div class="col-sm-8 fw-semibold text-primary">
                                {{ $tiempoTextoDet }} <span class="text-muted fw-normal">({{ $h->horas }} hrs académicas)</span>
                            </div>

                            <div class="col-sm-4 text-muted">Estado actual:</div>
                            <div class="col-sm-8">
                                <span class="badge-{{ $h->estado }}">{{ ucfirst($h->estado) }}</span>
                            </div>

                            <div class="col-sm-4 text-muted">Actividad cronograma:</div>
                            <div class="col-sm-8 text-dark">
                                {{ $h->actividad?->titulo ?? 'Sin actividad vinculada' }}
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="text-muted fw-semibold d-block mb-1">Descripción de tareas realizadas:</label>
                        <div class="p-2 border rounded-2 bg-white text-secondary">
                            {{ $h->descripcion }}
                        </div>
                    </div>

                    <div class="p-3 border rounded-3 bg-white">
                        <h6 class="fw-bold text-navy mb-2" style="font-size: 0.85rem;">
                            <i class="bi bi-person-check text-primary me-1"></i>Información de Revisión y Validación
                        </h6>
                        <div class="row g-2">
                            <div class="col-sm-4 text-muted">Docente revisor:</div>
                            <div class="col-sm-8 fw-semibold text-dark">
                                {{ $h->revisor?->nombre_completo ?? 'Pendiente de revisión por el docente tutor' }}
                            </div>

                            <div class="col-sm-4 text-muted">Fecha de revisión:</div>
                            <div class="col-sm-8 text-dark">
                                {{ $h->fecha_revision ? $h->fecha_revision->format('d/m/Y H:i') : 'Pendiente' }}
                            </div>

                            <div class="col-sm-4 text-muted">Observación o motivo:</div>
                            <div class="col-sm-8 fst-italic {{ $h->estado === 'rechazada' ? 'text-danger fw-semibold' : 'text-secondary' }}">
                                {{ $h->observacion_revision ?: 'Sin observaciones registradas' }}
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-sm btn-light rounded-pill px-3" data-bs-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>
@endforeach

<!-- Modales para Rechazar Registros de Horas -->
@foreach($practica->registrosHoras as $h)
    @if($h->estado === 'pendiente')
        <div class="modal fade" id="modalRechazarHora{{ $h->id }}" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content border-0 shadow rounded-4">
                    <div class="modal-header bg-danger text-white">
                        <h6 class="modal-title fw-bold">Rechazar Registro de Horas</h6>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <form action="{{ route('seguimiento.horas.validar', $h->id) }}" method="POST">
                        @csrf
                        <input type="hidden" name="estado" value="rechazada">
                        <div class="modal-body">
                            <div class="p-2 mb-3 bg-light rounded small">
                                <div><strong>Fecha:</strong> {{ $h->fecha->format('d/m/Y') }} &bull; <strong>Horas:</strong> {{ $h->horas }} h</div>
                                <div class="text-muted text-truncate mt-1"><strong>Descripción:</strong> {{ $h->descripcion }}</div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label small fw-semibold">Motivo del Rechazo (Obligatorio) *</label>
                                <textarea name="observacion_revision" class="form-control" rows="3" required placeholder="Explica la razón del rechazo de estas horas..."></textarea>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-sm btn-light" data-bs-dismiss="modal">Cancelar</button>
                            <button type="submit" class="btn btn-sm btn-danger">Confirmar Rechazo</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
@endforeach
@if(auth()->user()->isCoordinador() || (auth()->user()->isTutor() && $practica->tutor_id === auth()->user()->tutor?->id))
    <!-- Modal Planificar Nueva Actividad -->
    <div class="modal fade" id="modalNuevaActividad" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow rounded-4">
                <div class="modal-header text-white" style="background-color: var(--upds-navy);">
                    <h6 class="modal-title fw-bold"><i class="bi bi-calendar-plus me-2"></i>Planificar Actividad en Cronograma</h6>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form action="{{ route('seguimiento.actividades.store', $practica->id) }}" method="POST">
                    @csrf
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Título de la actividad *</label>
                            <input type="text" name="titulo" class="form-control" required placeholder="Ej. Levantamiento de requerimientos y diseño de base de datos">
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Objetivo formativo</label>
                            <input type="text" name="objetivo" class="form-control" placeholder="Ej. Modelar la arquitectura relacional del sistema">
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Entregable formal esperado</label>
                            <input type="text" name="entregable_esperado" class="form-control" placeholder="Ej. Documento de especificación técnica y scripts DDL">
                        </div>
                        <div class="row g-2 mb-3">
                            <div class="col-6">
                                <label class="form-label small fw-semibold">Fecha inicio</label>
                                <input type="date" name="fecha_inicio" class="form-control" value="{{ $practica->fecha_inicio ? $practica->fecha_inicio->format('Y-m-d') : date('Y-m-d') }}">
                            </div>
                            <div class="col-6">
                                <label class="form-label small fw-semibold">Fecha estimada fin</label>
                                <input type="date" name="fecha_fin" class="form-control" value="{{ $practica->fecha_fin_prevista ? $practica->fecha_fin_prevista->format('Y-m-d') : '' }}">
                            </div>
                        </div>
                        <div class="row g-2 mb-3">
                            <div class="col-6">
                                <label class="form-label small fw-semibold">Horas planificadas *</label>
                                <input type="number" step="0.5" name="horas_planificadas" class="form-control" min="1" value="40" required>
                            </div>
                            <div class="col-6">
                                <label class="form-label small fw-semibold">Estado inicial</label>
                                <select name="estado" class="form-select">
                                    <option value="pendiente">Pendiente</option>
                                    <option value="en_progreso">En progreso</option>
                                    <option value="completada">Completada</option>
                                </select>
                            </div>
                        </div>
                        <div class="mb-2">
                            <label class="form-label small fw-semibold">Descripción detallada</label>
                            <textarea name="descripcion" class="form-control" rows="2" placeholder="Detalle de tareas o alcance..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-sm btn-light" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-sm btn-upds">Guardar Actividad</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modales para Editar Actividades -->
    @foreach($practica->actividades as $act)
        <div class="modal fade" id="modalEditarActividad{{ $act->id }}" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content border-0 shadow rounded-4">
                    <div class="modal-header text-white" style="background-color: var(--upds-navy);">
                        <h6 class="modal-title fw-bold">Actualizar Actividad</h6>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <form action="{{ route('seguimiento.actividades.update', $act->id) }}" method="POST">
                        @csrf
                        @method('PUT')
                        <div class="modal-body">
                            <div class="mb-3">
                                <label class="form-label small fw-semibold">Título *</label>
                                <input type="text" name="titulo" class="form-control" value="{{ $act->titulo }}" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label small fw-semibold">Objetivo</label>
                                <input type="text" name="objetivo" class="form-control" value="{{ $act->objetivo }}">
                            </div>
                            <div class="row g-2 mb-3">
                                <div class="col-6">
                                    <label class="form-label small fw-semibold">Fecha inicio</label>
                                    <input type="date" name="fecha_inicio" class="form-control" value="{{ $act->fecha_inicio ? $act->fecha_inicio->format('Y-m-d') : '' }}">
                                </div>
                                <div class="col-6">
                                    <label class="form-label small fw-semibold">Fecha fin</label>
                                    <input type="date" name="fecha_fin" class="form-control" value="{{ $act->fecha_fin ? $act->fecha_fin->format('Y-m-d') : '' }}">
                                </div>
                            </div>
                            <div class="row g-2 mb-3">
                                <div class="col-6">
                                    <label class="form-label small fw-semibold">Horas planificadas *</label>
                                    <input type="number" step="0.5" name="horas_planificadas" class="form-control" min="1" value="{{ $act->horas_planificadas }}" required>
                                </div>
                                <div class="col-6">
                                    <label class="form-label small fw-semibold">Estado de la Actividad</label>
                                    <div class="py-1">
                                        <span class="badge-{{ $act->estado }} px-3 py-1 fs-6">{{ ucfirst(str_replace('_', ' ', $act->estado)) }}</span>
                                    </div>
                                    <small class="text-muted" style="font-size: 0.72rem;">Automático según entrega de informes y evaluación de rúbricas.</small>
                                </div>
                            </div>
                            <div class="mb-2">
                                <label class="form-label small fw-semibold">Observación / Retroalimentación</label>
                                <textarea name="observacion" class="form-control" rows="2" placeholder="Observaciones sobre el avance...">{{ $act->observacion }}</textarea>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-sm btn-light" data-bs-dismiss="modal">Cancelar</button>
                            <button type="submit" class="btn btn-sm btn-upds">Guardar Cambios</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endforeach

    <!-- Modal Valoración Organización Receptora -->
    <div class="modal fade" id="modalValoracionOrganizacion" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow rounded-4">
                <div class="modal-header text-white" style="background-color: var(--upds-navy);">
                    <h6 class="modal-title fw-bold"><i class="bi bi-buildings me-2"></i>Valoración de la Organización Receptora</h6>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form action="{{ route('seguimiento.evaluacion-organizacion.store', $practica->id) }}" method="POST">
                    @csrf
                    <div class="modal-body">
                        <div class="p-2 bg-light rounded-3 small mb-3">
                            <span class="text-muted">Organización:</span> <strong>{{ $practica->solicitud->organizacion->nombre }}</strong>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Responsable / Evaluador de la Empresa *</label>
                            <select name="responsable_id" class="form-select" required>
                                <option value="">-- Seleccionar Responsable --</option>
                                @foreach($practica->solicitud->organizacion->responsables as $resp)
                                    <option value="{{ $resp->id }}" {{ optional($practica->evaluacionOrganizacion)->responsable_id == $resp->id ? 'selected' : '' }}>
                                        {{ $resp->nombre_completo }} ({{ $resp->cargo }})
                                    </option>
                                @endforeach
                            </select>
                            @if($practica->solicitud->organizacion->responsables->isEmpty())
                                <small class="text-danger d-block mt-1">Nota: La organización no tiene responsables registrados. Agrega uno en el módulo de organizaciones.</small>
                            @endif
                        </div>
                        <div class="row g-2 mb-3">
                            <div class="col-6">
                                <label class="form-label small fw-semibold">Fecha de Emisión *</label>
                                <input type="date" name="fecha" class="form-control" value="{{ optional($practica->evaluacionOrganizacion)->fecha ? $practica->evaluacionOrganizacion->fecha->format('Y-m-d') : date('Y-m-d') }}" required>
                            </div>
                            <div class="col-6">
                                <label class="form-label small fw-semibold">Desempeño *</label>
                                <select name="desempeno" class="form-select" required>
                                    @php $des = optional($practica->evaluacionOrganizacion)->desempeno ?? 'Excelente'; @endphp
                                    <option value="Excelente" {{ $des == 'Excelente' ? 'selected' : '' }}>Excelente</option>
                                    <option value="Muy Bueno" {{ $des == 'Muy Bueno' ? 'selected' : '' }}>Muy Bueno</option>
                                    <option value="Bueno" {{ $des == 'Bueno' ? 'selected' : '' }}>Bueno</option>
                                    <option value="Regular" {{ $des == 'Regular' ? 'selected' : '' }}>Regular</option>
                                    <option value="Deficiente" {{ $des == 'Deficiente' ? 'selected' : '' }}>Deficiente</option>
                                </select>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Cumplimiento del Plan *</label>
                            @php $cum = optional($practica->evaluacionOrganizacion)->cumplimiento ?? 'Completo'; @endphp
                            <select name="cumplimiento" class="form-select" required>
                                <option value="Completo" {{ $cum == 'Completo' ? 'selected' : '' }}>Completo (100% de actividades)</option>
                                <option value="Parcial" {{ $cum == 'Parcial' ? 'selected' : '' }}>Parcial</option>
                                <option value="No Cumplió" {{ $cum == 'No Cumplió' ? 'selected' : '' }}>No Cumplió</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Comentarios / Observaciones de la Empresa *</label>
                            <textarea name="comentario" class="form-control" rows="3" required placeholder="Concepto emitido por la empresa receptora sobre la disciplina, aportes y competencias del estudiante...">{{ optional($practica->evaluacionOrganizacion)->comentario }}</textarea>
                        </div>
                        <div class="mb-2">
                            <label class="form-label small fw-semibold">Recomendaciones para el practicante</label>
                            <textarea name="recomendacion" class="form-control" rows="2" placeholder="Sugerencias de mejora profesional...">{{ optional($practica->evaluacionOrganizacion)->recomendacion }}</textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-sm btn-light" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-sm btn-upds">Guardar Valoración</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Concluir Práctica -->
    <div class="modal fade" id="modalConcluirPractica" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow rounded-4">
                <div class="modal-header bg-success text-white">
                    <h6 class="modal-title fw-bold"><i class="bi bi-award me-2"></i>Concluir y Aprobar Práctica Profesional</h6>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form action="{{ route('seguimiento.cierre', $practica->id) }}" method="POST">
                    @csrf
                    <input type="hidden" name="nuevo_estado" value="concluida">
                    <div class="modal-body">
                        <div class="alert alert-info py-2 small mb-3">
                            <i class="bi bi-info-circle me-1"></i>
                            El cierre formal de práctica requiere que el Docente Tutor haya emitido la evaluación final con resultado aprobatorio.
                        </div>
                        <div class="mb-2">
                            <label class="form-label small fw-semibold">Resolución institucional de Coordinación Académica *</label>
                            <textarea name="motivo" class="form-control" rows="3" required placeholder="Resolución de coordinación académica para acreditación de la práctica..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-sm btn-light" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-sm btn-success fw-semibold">Confirmar Cierre y Conclusión</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Abandono de Práctica -->
    <div class="modal fade" id="modalAbandonarPractica" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow rounded-4">
                <div class="modal-header bg-warning text-dark">
                    <h6 class="modal-title fw-bold"><i class="bi bi-slash-circle me-2"></i>Declarar Abandono de Práctica</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form action="{{ route('seguimiento.cierre', $practica->id) }}" method="POST">
                    @csrf
                    <input type="hidden" name="nuevo_estado" value="abandonada">
                    <div class="modal-body">
                        <p class="small text-muted mb-2">
                            Esta acción cambiará el estado de la práctica a <strong>Abandonada</strong> y quedará registrada en el historial académico.
                        </p>
                        <div class="mb-2">
                            <label class="form-label small fw-semibold">Motivo justificado del abandono *</label>
                            <textarea name="motivo" class="form-control" rows="3" required placeholder="Indica el informe del tutor o empresa sobre el abandono del practicante..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-sm btn-light" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-sm btn-warning text-dark fw-semibold">Confirmar Abandono</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Cancelar Práctica -->
    <div class="modal fade" id="modalCancelarPractica" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow rounded-4">
                <div class="modal-header bg-danger text-white">
                    <h6 class="modal-title fw-bold"><i class="bi bi-x-circle me-2"></i>Cancelar Práctica Profesional</h6>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form action="{{ route('seguimiento.cierre', $practica->id) }}" method="POST">
                    @csrf
                    <input type="hidden" name="nuevo_estado" value="cancelada">
                    <div class="modal-body">
                        <p class="small text-muted mb-2">
                            Esta acción cancelará la práctica en curso y quedará registrada en la auditoría con el motivo ingresado.
                        </p>
                        <div class="mb-2">
                            <label class="form-label small fw-semibold">Motivo de la cancelación *</label>
                            <textarea name="motivo" class="form-control" rows="3" required placeholder="Motivo administrativo o de fuerza mayor para cancelar la práctica..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-sm btn-light" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-sm btn-danger">Confirmar Cancelación</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endif

<!-- Modal Justificar Asistencia (Estudiante / Coordinación) -->
<div class="modal fade" id="modalJustificarAsistencia" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow rounded-4">
            <div class="modal-header text-white" style="background-color: var(--upds-navy);">
                <h6 class="modal-title fw-bold">
                    <i class="bi bi-shield-exclamation me-2"></i>Justificar Ausencia o Jornada Incompleta
                </h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('seguimiento.justificaciones.store', $practica->id) }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="modal-body p-4 small">
                    <div class="alert alert-info py-2 small mb-3">
                        <i class="bi bi-info-circle me-1"></i>
                        Límites permitidos: máximo <strong>2 faltas justificadas</strong> y <strong>2 jornadas incompletas</strong> durante la práctica. Una tercera genera incidencia grave que bloquea la conclusión.
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Tipo de Excepción *</label>
                        <select name="tipo" class="form-select" id="selectTipoJustificacion" onchange="toggleHorasJustificacion()" required>
                            <option value="falta">Falta / Ausencia total (0 horas trabajadas)</option>
                            <option value="incompleta">Jornada Incompleta (Entre 1 y 3.5 horas trabajadas)</option>
                        </select>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label fw-semibold">Fecha de la jornada *</label>
                            <input type="date" name="fecha" class="form-control" value="{{ date('Y-m-d') }}" max="{{ date('Y-m-d') }}" required>
                        </div>
                        <div class="col-6" id="divHorasRealesJustificacion" style="display: none;">
                            <label class="form-label fw-semibold">Horas trabajadas *</label>
                            <input type="number" step="0.5" min="1" max="3.5" name="horas_reales" class="form-control" placeholder="1 a 3.5">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Motivo justificado *</label>
                        <textarea name="motivo" class="form-control" rows="3" required placeholder="Explica detalladamente la causa médica, académica o de fuerza mayor..."></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Evidencia / Certificado (Opcional - PDF / JPG / PNG / DOCX)</label>
                        <input type="file" name="documento" class="form-control">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-sm btn-light rounded-pill px-3" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-sm btn-upds rounded-pill px-4">Enviar Justificación</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Gestión de Justificaciones de Asistencia -->
<div class="modal fade" id="modalGestionJustificaciones" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow rounded-4">
            <div class="modal-header text-white" style="background-color: var(--upds-navy);">
                <h6 class="modal-title fw-bold"><i class="bi bi-list-check me-2"></i>Justificaciones de Asistencia ({{ $practica->justificaciones->count() }})</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4 small">
                <div class="d-flex justify-content-between mb-3 text-muted">
                    <span>Faltas justificadas aprobadas: <strong class="{{ $practica->faltas_justificadas > 2 ? 'text-danger' : 'text-primary' }}">{{ $practica->faltas_justificadas }}/2</strong></span>
                    <span>Incompletas justificadas aprobadas: <strong class="{{ $practica->incompletas_justificadas > 2 ? 'text-danger' : 'text-primary' }}">{{ $practica->incompletas_justificadas }}/2</strong></span>
                </div>
                <div class="list-group list-group-flush" style="max-height: 400px; overflow-y: auto;">
                    @forelse($practica->justificaciones as $j)
                        <div class="list-group-item p-3 border rounded-3 mb-2 bg-light">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <div>
                                    <strong class="text-dark">{{ $j->fecha->format('d/m/Y') }}</strong> &bull;
                                    <span class="badge {{ $j->tipo === 'falta' ? 'bg-danger' : 'bg-warning text-dark' }}">
                                        {{ $j->tipo === 'falta' ? 'Falta (0h)' : "Jornada incompleta ({$j->horas_reales}h)" }}
                                    </span>
                                </div>
                                <span class="badge {{ $j->estado === 'aprobada' ? 'bg-success' : ($j->estado === 'rechazada' ? 'bg-danger' : 'bg-warning text-dark') }}">
                                    {{ ucfirst($j->estado) }}
                                </span>
                            </div>
                            <div class="text-secondary mb-2">{{ $j->motivo }}</div>
                            @if(auth()->user()->isCoordinador() && $j->estado === 'pendiente')
                                <form action="{{ route('seguimiento.justificaciones.resolver', $j->id) }}" method="POST" class="mt-2 pt-2 border-top">
                                    @csrf
                                    <div class="input-group input-group-sm">
                                        <input type="text" name="observacion_resolucion" class="form-control" required placeholder="Resolución institucional...">
                                        <button type="submit" name="estado" value="aprobada" class="btn btn-success">Aprobar</button>
                                        <button type="submit" name="estado" value="rechazada" class="btn btn-danger">Rechazar</button>
                                    </div>
                                </form>
                            @elseif($j->observacion_resolucion)
                                <div class="small text-muted border-top pt-1 mt-1 fst-italic">
                                    Resolución: {{ $j->observacion_resolucion }} ({{ $j->fecha_resolucion?->format('d/m/Y') }})
                                </div>
                            @endif
                        </div>
                    @empty
                        <div class="text-center text-muted py-4">No se han registrado justificaciones de asistencia.</div>
                    @endforelse
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-sm btn-light rounded-pill px-3" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Incidencias de la Práctica -->
<div class="modal fade" id="modalIncidenciasPractica" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow rounded-4">
            <div class="modal-header bg-danger text-white">
                <h6 class="modal-title fw-bold"><i class="bi bi-exclamation-octagon me-2"></i>Incidencias de la Práctica</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4 small">
                @forelse($practica->incidencias as $inc)
                    <div class="p-3 border rounded-3 mb-2 {{ $inc->resuelta ? 'bg-light' : 'bg-danger-subtle border-danger' }}">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <strong class="{{ $inc->resuelta ? 'text-dark' : 'text-danger' }}">{{ ucfirst(str_replace('_', ' ', $inc->tipo)) }}</strong>
                            <span class="badge {{ $inc->resuelta ? 'bg-success' : 'bg-danger' }}">{{ $inc->resuelta ? 'Resuelta' : 'Pendiente (Bloquea Cierre)' }}</span>
                        </div>
                        <div class="text-secondary mb-2">{{ $inc->descripcion }}</div>
                        @if(auth()->user()->isCoordinador() && !$inc->resuelta)
                            <form action="{{ route('seguimiento.incidencias.resolver', $inc->id) }}" method="POST" class="mt-2 pt-2 border-top">
                                @csrf
                                <div class="input-group input-group-sm">
                                    <input type="text" name="resolucion" class="form-control" required placeholder="Resolución de coordinación...">
                                    <button type="submit" class="btn btn-sm btn-success">Resolver Incidencia</button>
                                </div>
                            </form>
                        @elseif($inc->resolucion)
                            <div class="text-muted fst-italic border-top pt-1 mt-1">Resolución: {{ $inc->resolucion }}</div>
                        @endif
                    </div>
                @empty
                    <div class="text-center text-muted py-3">No existen incidencias registradas en esta práctica.</div>
                @endforelse
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-sm btn-light rounded-pill px-3" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<!-- Modales de Rúbricas por Actividad -->
@foreach($practica->actividades as $act)
    <!-- Modal Configurar Rúbrica -->
    <div class="modal fade" id="modalRubrica{{ $act->id }}" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content border-0 shadow rounded-4">
                <div class="modal-header text-white" style="background-color: var(--upds-navy);">
                    <h6 class="modal-title fw-bold"><i class="bi bi-card-checklist me-2"></i>Rúbrica de Actividad: {{ $act->titulo }}</h6>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form action="{{ route('seguimiento.actividades.rubrica.store', $act->id) }}" method="POST">
                    @csrf
                    <div class="modal-body p-4 small">
                        <div class="alert alert-info py-2 small mb-3">
                            <i class="bi bi-info-circle me-1"></i>
                            Define los criterios de evaluación. <strong>La suma de las ponderaciones de los criterios debe ser exactamente 100%.</strong>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Título de la rúbrica *</label>
                            <input type="text" name="titulo" class="form-control" value="{{ $act->rubrica->titulo ?? 'Rúbrica: ' . $act->titulo }}" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Descripción u objetivo evaluativo</label>
                            <textarea name="descripcion" class="form-control" rows="2">{{ $act->rubrica->descripcion ?? '' }}</textarea>
                        </div>

                        <h6 class="fw-bold text-navy mb-2">Criterios de Evaluación</h6>
                        <div id="contenedorCriterios{{ $act->id }}">
                            @php
                                $criteriosExistentes = $act->rubrica?->criterios ?? collect([
                                    (object)['nombre' => 'Cumplimiento técnico del objetivo', 'ponderacion' => 50],
                                    (object)['nombre' => 'Calidad del entregable y documentación', 'ponderacion' => 30],
                                    (object)['nombre' => 'Puntualidad y metodología aplicada', 'ponderacion' => 20],
                                ]);
                            @endphp
                            @foreach($criteriosExistentes as $idx => $crit)
                                <div class="row g-2 mb-2 p-2 bg-light rounded align-items-center fila-criterio-{{ $act->id }}">
                                    <div class="col-8">
                                        <input type="text" name="criterios[{{ $idx }}][nombre]" class="form-control form-control-sm" value="{{ $crit->nombre }}" required placeholder="Nombre del criterio...">
                                    </div>
                                    <div class="col-4">
                                        <div class="input-group input-group-sm">
                                            <input type="number" step="1" min="1" max="100" name="criterios[{{ $idx }}][ponderacion]" class="form-control form-control-sm input-pond-{{ $act->id }}" value="{{ (int)$crit->ponderacion }}" required oninput="calcularPonderacionTotal({{ $act->id }})">
                                            <span class="input-group-text">%</span>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        <div class="d-flex justify-content-between align-items-center mt-3 pt-2 border-top">
                            <span class="fw-bold">Ponderación Total: <strong id="lblPonderacionTotal{{ $act->id }}" class="text-success fw-bold">100%</strong></span>
                            <small class="text-muted">Debe sumar exactamente 100%</small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-sm btn-light rounded-pill px-3" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-sm btn-upds rounded-pill px-4">Guardar Rúbrica</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Evaluar con Rúbrica -->
    @if($act->rubrica)
        <div class="modal fade" id="modalEvaluarRubrica{{ $act->id }}" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content border-0 shadow rounded-4">
                    <div class="modal-header text-white" style="background-color: var(--upds-navy);">
                        <h6 class="modal-title fw-bold"><i class="bi bi-award me-2"></i>Calificar Actividad: {{ $act->titulo }}</h6>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <form action="{{ route('seguimiento.rubricas.evaluar', $act->rubrica->id) }}" method="POST">
                        @csrf
                        <div class="modal-body p-4 small">
                            <div class="alert alert-info py-2 small mb-3">
                                Asigna la puntuación de 0 a 100 para cada criterio. El sistema calculará la nota ponderada y el resultado (Aprobado/Reprobado) automáticamente.
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Informe de avance vinculado (Opcional)</label>
                                <select name="informe_avance_id" class="form-select form-select-sm">
                                    <option value="">-- Sin vincular a informe --</option>
                                    @foreach($practica->informes as $inf)
                                        <option value="{{ $inf->id }}">{{ $inf->titulo }} ({{ $inf->periodo_inicio->format('d/m/Y') }})</option>
                                    @endforeach
                                </select>
                            </div>

                            <h6 class="fw-bold text-navy mb-2">Puntuación por Criterio</h6>
                            @foreach($act->rubrica->criterios as $crit)
                                <div class="mb-3 p-2 border rounded-3 bg-light">
                                    <div class="d-flex justify-content-between mb-1">
                                        <strong>{{ $crit->nombre }}</strong>
                                        <span class="badge bg-secondary">{{ $crit->ponderacion }}% ponderación</span>
                                    </div>
                                    <div class="input-group input-group-sm mb-1">
                                        <span class="input-group-text">Calificación (0 - 100)</span>
                                        <input type="number" step="0.5" min="0" max="100" maxlength="5" name="puntuaciones[{{ $crit->id }}]" class="form-control input-calificacion-rubrica" required placeholder="0 a 100" oninput="validarNotaInput(this)">
                                    </div>
                                    <input type="text" name="comentarios[{{ $crit->id }}]" class="form-control form-control-sm" placeholder="Comentario u observación sobre el criterio...">
                                </div>
                            @endforeach
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-sm btn-light rounded-pill px-3" data-bs-dismiss="modal">Cancelar</button>
                            <button type="submit" class="btn btn-sm btn-success rounded-pill px-4">Guardar Calificación</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
@endforeach
@endsection

@section('scripts')
<script>
function validarNotaInput(input) {
    if (!input) return;
    // Recortar si supera 5 caracteres (ej. 100.0)
    if (input.value.length > 5) {
        input.value = input.value.slice(0, 5);
    }
    var num = parseFloat(input.value);
    if (isNaN(num)) return;
    if (num > 100) {
        input.value = '100';
    } else if (num < 0) {
        input.value = '0';
    }
}

document.addEventListener('input', function(e) {
    if (e.target && e.target.classList.contains('input-calificacion-rubrica')) {
        validarNotaInput(e.target);
    }
});

document.addEventListener('keydown', function(e) {
    if (e.target && e.target.classList.contains('input-calificacion-rubrica')) {
        // Bloquear caracteres no numéricos como 'e', 'E', '+', '-'
        if (['e', 'E', '+', '-'].includes(e.key)) {
            e.preventDefault();
        }
    }
});

function actualizarCalculoHoras() {
    var selH = document.getElementById('selectHorasJornada');
    var selM = document.getElementById('selectMinutosJornada');
    if (!selH || !selM) return;
    var h = parseInt(selH.value) || 0;
    var m = parseInt(selM.value) || 0;
    var decimal = parseFloat((h + (m / 60)).toFixed(2));
    document.getElementById('inputHorasDecimal').value = decimal;
    var descMin = m > 0 ? (' y ' + m + ' minutos') : '';
    var descHora = h > 0 ? (h + (h === 1 ? ' hora' : ' horas')) : '';
    var texto = (descHora + descMin) || '0 minutos';
    var txtEl = document.getElementById('textoHorasResumen');
    var badgeEl = document.getElementById('badgeHorasDecimal');
    if (txtEl) txtEl.innerText = 'Total: ' + texto + ' (' + decimal + ' hrs académicas)';
    if (badgeEl) badgeEl.innerText = decimal + ' h';
}

function setDuracion(h, m) {
    var selH = document.getElementById('selectHorasJornada');
    var selM = document.getElementById('selectMinutosJornada');
    if (selH) selH.value = h;
    if (selM) selM.value = m;
    actualizarCalculoHoras();
}

function toggleHorasJustificacion() {
    var sel = document.getElementById('selectTipoJustificacion');
    var divH = document.getElementById('divHorasRealesJustificacion');
    if (sel && divH) {
        divH.style.display = (sel.value === 'incompleta') ? 'block' : 'none';
    }
}

function calcularPonderacionTotal(actId) {
    var inputs = document.querySelectorAll('.input-pond-' + actId);
    var total = 0;
    inputs.forEach(function(inp) {
        total += parseFloat(inp.value) || 0;
    });
    var lbl = document.getElementById('lblPonderacionTotal' + actId);
    if (lbl) {
        lbl.innerText = total + '%';
        lbl.className = (Math.abs(total - 100) < 0.01) ? 'text-success fw-bold' : 'text-danger fw-bold';
    }
}

function actualizarFechasPorActividad(sel) {
    if (!sel || !sel.value) {
        var infoDiv = document.getElementById('infoActividadSeleccionada');
        if (infoDiv) infoDiv.classList.add('d-none');
        return;
    }
    var opt = sel.options[sel.selectedIndex];
    var inicio = opt.getAttribute('data-inicio');
    var fin = opt.getAttribute('data-fin');
    var titulo = opt.getAttribute('data-titulo');
    var entregable = opt.getAttribute('data-entregable');

    if (inicio) document.getElementById('inputPeriodoInicio').value = inicio;
    if (fin) document.getElementById('inputPeriodoFin').value = fin;
    if (titulo) document.getElementById('inputTituloInforme').value = 'Informe Técnico - ' + titulo;

    var infoDiv = document.getElementById('infoActividadSeleccionada');
    var infoTxt = document.getElementById('textoInfoActividad');
    if (infoDiv && infoTxt) {
        infoTxt.innerHTML = '<strong>Fecha límite de entrega:</strong> ' + (fin || 'Sin definir') +
                            (entregable ? ' &bull; <strong>Entregable esperado:</strong> ' + entregable : '');
        infoDiv.classList.remove('d-none');
    }
}

function prepararCorreccionInforme(actividadId, tituloBase) {
    var sel = document.getElementById('selectActividadInforme');
    if (sel && actividadId) {
        sel.value = actividadId;
        actualizarFechasPorActividad(sel);
    }
    var inputT = document.getElementById('inputTituloInforme');
    if (inputT && tituloBase) {
        inputT.value = tituloBase;
    }
}

function cambiarActividadParcial(sel) {
    var actId = sel.value;
    var opt = sel.options[sel.selectedIndex];
    var tieneRubrica = opt ? opt.getAttribute('data-tiene-rubrica') === '1' : false;
    var rubricaId = opt ? opt.getAttribute('data-rubrica-id') : '';
    var titulo = opt ? opt.getAttribute('data-titulo') : '';

    var inputRubrica = document.getElementById('inputRubricaIdParcial');
    if (inputRubrica) inputRubrica.value = rubricaId;
    if (titulo) {
        var inputPer = document.getElementById('inputPeriodoParcial');
        if (inputPer) inputPer.value = 'Evaluación Parcial: ' + titulo;
    }

    // Ocultar todas las secciones de criterios
    document.querySelectorAll('.seccion-criterios-actividad').forEach(function(el) {
        el.classList.add('d-none');
    });

    var aviso = document.getElementById('avisoSinRubrica');

    if (!actId) {
        if (aviso) aviso.classList.add('d-none');
        return;
    }

    if (tieneRubrica) {
        if (aviso) aviso.classList.add('d-none');
        var seccion = document.getElementById('criteriosActividad' + actId);
        if (seccion) {
            seccion.classList.remove('d-none');
            recalcularNotaParcial(actId);
        }
    } else {
        if (aviso) aviso.classList.remove('d-none');
    }
}

function recalcularNotaParcial(actId) {
    var inputs = document.querySelectorAll('.input-act-' + actId);
    var notaTotal = 0;
    inputs.forEach(function(inp) {
        var pond = parseFloat(inp.getAttribute('data-pond')) || 0;
        var puntos = parseFloat(inp.value) || 0;
        notaTotal += (puntos * (pond / 100.0));
    });

    notaTotal = parseFloat(notaTotal.toFixed(2));
    var lbl = document.getElementById('lblNotaParcial' + actId);
    var badge = document.getElementById('badgeResultadoParcial' + actId);

    if (lbl) {
        lbl.innerText = notaTotal.toFixed(2) + ' / 100';
    }

    if (badge) {
        if (notaTotal >= 51.0) {
            badge.className = 'badge bg-success fs-6';
            badge.innerText = 'Aprobado';
        } else {
            badge.className = 'badge bg-danger fs-6';
            badge.innerText = 'Reprobado';
        }
    }
}

// Inicializar al abrir el modal
document.addEventListener('DOMContentLoaded', function() {
    var modal = document.getElementById('modalRegistrarHoras');
    if (modal) {
        modal.addEventListener('shown.bs.modal', function() {
            actualizarCalculoHoras();
        });
    }
});
</script>
@endsection

