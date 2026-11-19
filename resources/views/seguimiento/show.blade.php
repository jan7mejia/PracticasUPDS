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

<!-- Layout idéntico a la Pantalla 6 del Mockup -->
<div class="row g-4">
    <!-- COLUMNA IZQUIERDA -->
    <div class="col-12 col-lg-6">
        <!-- 1. Asignación de Tutor (Exacto a Screen 6) -->
        <div class="card border-0 shadow-sm rounded-4 p-4 mb-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="fw-bold text-navy mb-0" style="color: var(--upds-navy);">
                    <i class="bi bi-person-badge text-primary me-2"></i>Asignación de tutor
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

                    <div class="col-sm-4 text-muted">Organización:</div>
                    <div class="col-sm-8 fw-semibold text-primary">{{ $practica->solicitud->organizacion->nombre }}</div>

                    <div class="col-sm-4 text-muted">Tutor asignado:</div>
                    <div class="col-sm-8 fw-bold text-dark">
                        {{ $practica->tutor ? $practica->tutor->nombre_completo : 'Pendiente de asignación' }}
                        @if($practica->tutor)
                            <small class="text-muted d-block">({{ $practica->tutor->especialidad }})</small>
                        @endif
                    </div>

                    <div class="col-sm-4 text-muted">Período Práctica:</div>
                    <div class="col-sm-8">
                        {{ $practica->fecha_inicio ? $practica->fecha_inicio->format('d/m/Y') : 'Por definir' }} al 
                        {{ $practica->fecha_fin_prevista ? $practica->fecha_fin_prevista->format('d/m/Y') : 'Por definir' }}
                    </div>

                    <div class="col-sm-4 text-muted">Meta Horas:</div>
                    <div class="col-sm-8 fw-semibold">{{ $practica->horas_objetivo }} hrs planificadas</div>
                </div>
            </div>
        </div>

        <!-- 1.1 Cronograma y Actividades Planificadas -->
        <div class="card border-0 shadow-sm rounded-4 p-4 mb-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="fw-bold text-navy mb-0" style="color: var(--upds-navy);">
                    <i class="bi bi-calendar-check text-primary me-2"></i>Cronograma de Actividades
                </h6>
                @if(auth()->user()->isCoordinador())
                    <button class="btn btn-sm btn-outline-primary py-1 px-3 rounded-pill fw-semibold" data-bs-toggle="modal" data-bs-target="#modalNuevaActividad">
                        <i class="bi bi-plus-lg me-1"></i> + Planificar Actividad
                    </button>
                @endif
            </div>

            <div class="list-group list-group-flush small">
                @forelse($practica->actividades as $act)
                    <div class="list-group-item p-3 border rounded-3 mb-2 bg-light">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <strong class="text-dark">{{ $act->titulo }}</strong>
                            <div class="d-flex align-items-center gap-2">
                                <span class="badge-{{ $act->estado }}">{{ ucfirst(str_replace('_', ' ', $act->estado)) }}</span>
                                @if(auth()->user()->isCoordinador())
                                    <button type="button" class="btn btn-sm btn-light border py-0 px-2" data-bs-toggle="modal" data-bs-target="#modalEditarActividad{{ $act->id }}" title="Editar Actividad">
                                        <i class="bi bi-pencil text-secondary"></i>
                                    </button>
                                @endif
                            </div>
                        </div>
                        @if($act->objetivo)
                            <div class="text-muted mb-1">{{ $act->objetivo }}</div>
                        @endif
                        <div class="d-flex justify-content-between text-secondary" style="font-size: 0.75rem;">
                            <span><i class="bi bi-clock me-1"></i>{{ $act->horas_planificadas }} h estimadas</span>
                            <span>
                                @if($act->fecha_inicio && $act->fecha_fin)
                                    {{ $act->fecha_inicio->format('d/m/Y') }} al {{ $act->fecha_fin->format('d/m/Y') }}
                                @endif
                            </span>
                        </div>
                    </div>
                @empty
                    <div class="text-center text-muted small py-3">No hay actividades planificadas en el cronograma todavía.</div>
                @endforelse
            </div>
        </div>

        <!-- 2. Reportes de Seguimiento (Exacto a Screen 6) -->
        <div class="card border-0 shadow-sm rounded-4 p-4 mb-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="fw-bold text-navy mb-0" style="color: var(--upds-navy);">
                    <i class="bi bi-journal-check text-primary me-2"></i>Reportes de seguimiento
                </h6>
                @if(auth()->user()->isEstudiante())
                <button class="btn btn-sm btn-outline-primary py-1 px-3 rounded-pill fw-semibold" data-bs-toggle="modal" data-bs-target="#modalSubirInforme">
                    <i class="bi bi-plus-lg me-1"></i> Presentar reporte
                </button>
                @endif
            </div>

            <div class="list-group list-group-flush">
                @forelse($practica->informes as $inf)
                    <div class="list-group-item px-3 py-2 border rounded-3 mb-2 bg-light">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <div>
                                <div class="fw-semibold text-dark small">{{ $inf->titulo }}</div>
                                <small class="text-muted" style="font-size: 0.75rem;">
                                    Período: {{ $inf->periodo_inicio->format('d/m/Y') }} al {{ $inf->periodo_fin->format('d/m/Y') }} &bull;
                                    <span class="badge-{{ $inf->estado }}">{{ ucfirst($inf->estado) }}</span>
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
                        @if($inf->resumen)
                            <div class="text-muted small fst-italic mt-1" style="font-size: 0.8rem;">
                                {{ Str::limit($inf->resumen, 120) }}
                            </div>
                        @endif
                    </div>
                @empty
                    <div class="text-center text-muted small py-3">No hay reportes de avance registrados aún.</div>
                @endforelse
            </div>
        </div>

        <!-- 2.1 Evaluaciones Parciales / Seguimiento -->
        <div class="card border-0 shadow-sm rounded-4 p-4 mb-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="fw-bold text-navy mb-0" style="color: var(--upds-navy);">
                    <i class="bi bi-clipboard2-check text-primary me-2"></i>Evaluaciones de Seguimiento (Parciales)
                </h6>
                @if((auth()->user()->isTutor() || auth()->user()->isCoordinador()) && $practica->estado !== 'concluida')
                    <button class="btn btn-sm btn-outline-primary py-1 px-3 rounded-pill fw-semibold" data-bs-toggle="modal" data-bs-target="#modalEvaluacionParcial">
                        <i class="bi bi-plus-lg me-1"></i> + Calificar Parcial
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

        <!-- 2.2 Valoración de la Organización Receptora -->
        <div class="card border-0 shadow-sm rounded-4 p-4 mb-4">
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

        <!-- 3. Evaluación Final (Exacto a Screen 6) -->
        <div class="card border-0 shadow-sm rounded-4 p-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="fw-bold text-navy mb-0" style="color: var(--upds-navy);">
                    <i class="bi bi-award text-primary me-2"></i>Evaluación final y Cierre
                </h6>
            </div>

            @if($practica->evaluacionFinal)
                <div class="p-3 bg-light rounded-3 small mb-3">
                    <div class="row g-2">
                        <div class="col-sm-4 text-muted">Criterio:</div>
                        <div class="col-sm-8 fw-semibold">Desempeño general y cumplimiento</div>

                        <div class="col-sm-4 text-muted">Calificación:</div>
                        <div class="col-sm-8 fw-bold text-success fs-6">{{ $practica->evaluacionFinal->calificacion }} / 100</div>

                        <div class="col-sm-4 text-muted">Resultado final:</div>
                        <div class="col-sm-8"><span class="badge-activo">{{ $practica->evaluacionFinal->resultado }}</span></div>

                        <div class="col-sm-4 text-muted">Comentario Tutor:</div>
                        <div class="col-sm-8 fst-italic text-secondary">{{ $practica->evaluacionFinal->comentario }}</div>
                    </div>
                </div>
            @else
                <div class="p-3 bg-light rounded-3 small mb-3">
                    <div class="row g-2">
                        <div class="col-sm-4 text-muted">Criterio:</div>
                        <div class="col-sm-8 fw-semibold">Desempeño general</div>

                        <div class="col-sm-4 text-muted">Calificación:</div>
                        <div class="col-sm-8 text-muted">Pendiente de evaluación del tutor</div>

                        <div class="col-sm-4 text-muted">Resultado final:</div>
                        <div class="col-sm-8 text-muted">En proceso</div>
                    </div>
                </div>
            @endif

            @if(auth()->user()->isTutor() && $practica->estado !== 'concluida')
                <button type="button" class="btn btn-upds w-100" data-bs-toggle="modal" data-bs-target="#modalEvaluacionFinal">
                    <i class="bi bi-check2-circle me-1"></i> Registrar evaluación final
                </button>
            @endif

            @if(auth()->user()->isCoordinador() && !in_array($practica->estado, ['concluida', 'cancelada', 'abandonada']))
                <hr class="my-3">
                <h6 class="fw-bold small text-navy mb-2">Resolución y Cierre de Práctica (Coordinación)</h6>
                <div class="d-grid gap-2">
                    <button type="button" class="btn btn-sm btn-success fw-semibold" data-bs-toggle="modal" data-bs-target="#modalConcluirPractica">
                        <i class="bi bi-award me-1"></i> Finalizar y Concluir Práctica
                    </button>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-sm btn-outline-warning text-dark w-50" data-bs-toggle="modal" data-bs-target="#modalAbandonarPractica">
                            <i class="bi bi-slash-circle me-1"></i> Abandono
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-danger w-50" data-bs-toggle="modal" data-bs-target="#modalCancelarPractica">
                            <i class="bi bi-x-circle me-1"></i> Cancelar
                        </button>
                    </div>
                </div>
            @endif
        </div>
    </div>

    <!-- COLUMNA DERECHA -->
    <div class="col-12 col-lg-6">
        <!-- 4. Registro de Horas (Exacto a Screen 6) -->
        <div class="card border-0 shadow-sm rounded-4 p-4 mb-4" id="registroHoras">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="fw-bold text-navy mb-0" style="color: var(--upds-navy);">
                    <i class="bi bi-clock-history text-primary me-2"></i>Registro de horas
                </h6>
                @if(in_array($practica->estado, ['en_curso', 'planificada']) && auth()->user()->isEstudiante())
                <button class="btn btn-sm btn-outline-primary py-1 px-3 rounded-pill fw-semibold" data-bs-toggle="modal" data-bs-target="#modalRegistrarHoras">
                    <i class="bi bi-plus-lg me-1"></i> Registrar horas
                </button>
                @endif
            </div>

            <div class="row g-2 mb-3 small">
                <div class="col-6">
                    <span class="text-muted">Horas requeridas:</span>
                    <strong class="d-block fs-6 text-dark">{{ $practica->horas_objetivo }} h</strong>
                </div>
                <div class="col-6 text-end">
                    <span class="text-muted">Horas validadas:</span>
                    <strong class="d-block fs-6 text-success">{{ $practica->horas_validadas }} h</strong>
                </div>
            </div>

            <!-- Barra de Avance -->
            <div class="mb-4">
                <div class="d-flex justify-content-between small text-muted mb-1">
                    <span>Avance</span>
                    <strong class="text-dark">{{ $practica->porcentaje_horas }}%</strong>
                </div>
                <div class="progress" style="height: 10px; border-radius: 6px;">
                    <div class="progress-bar bg-success progress-bar-striped progress-bar-animated" role="progressbar" style="width: {{ $practica->porcentaje_horas }}%"></div>
                </div>
            </div>

            <!-- Tabla de Bitácora de Horas -->
            <h6 class="fw-bold small text-muted text-uppercase mb-2">Historial de Jornadas Registradas</h6>
            <div class="table-responsive" style="max-height: 280px; overflow-y: auto;">
                <table class="table table-sm align-middle small mb-0">
                    <thead class="table-light sticky-top">
                        <tr>
                            <th>Fecha</th>
                            <th>Horas</th>
                            <th>Descripción</th>
                            <th>Estado</th>
                            @if(auth()->user()->isTutor())
                                <th class="text-end">Validar</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($practica->registrosHoras as $h)
                            <tr>
                                <td>{{ $h->fecha->format('d/m/Y') }}</td>
                                <td><strong>{{ $h->horas }} h</strong></td>
                                <td class="text-truncate" style="max-width: 180px;" title="{{ $h->descripcion }}">{{ $h->descripcion }}</td>
                                <td>
                                    <span class="badge-{{ $h->estado }}">{{ ucfirst($h->estado) }}</span>
                                    @if($h->observacion_revision)
                                        <i class="bi bi-info-circle ms-1 text-muted" title="{{ $h->observacion_revision }}"></i>
                                    @endif
                                </td>
                                @if(auth()->user()->isTutor())
                                    <td class="text-end">
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
                                <td colspan="5" class="text-center py-3 text-muted">No se han registrado jornadas todavía.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- 5. Observaciones del Tutor (Exacto a Screen 6) -->
        <div class="card border-0 shadow-sm rounded-4 p-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="fw-bold text-navy mb-0" style="color: var(--upds-navy);">
                    <i class="bi bi-chat-left-dots text-primary me-2"></i>Observaciones del Tutor
                </h6>
                @if(auth()->user()->isTutor() || auth()->user()->isCoordinador())
                    <button class="btn btn-sm btn-outline-primary py-1 px-3 rounded-pill fw-semibold" data-bs-toggle="collapse" data-bs-target="#formNuevaObs">
                        <i class="bi bi-plus-lg me-1"></i> + Nueva nota
                    </button>
                @endif
            </div>

            <!-- Formulario colapsable para tutor -->
            <div class="collapse mb-3" id="formNuevaObs">
                <form action="{{ route('seguimiento.observaciones.store', $practica->id) }}" method="POST" class="p-3 bg-light rounded-3 border">
                    @csrf
                    <label class="form-label small fw-semibold">Detalle de la observación:</label>
                    <textarea name="detalle" class="form-control form-control-sm mb-2" rows="2" required placeholder="Escribe aquí tu observación o retroalimentación..."></textarea>
                    <div class="text-end">
                        <button type="submit" class="btn btn-sm btn-upds">Guardar Observación</button>
                    </div>
                </form>
            </div>

            <div class="list-group list-group-flush">
                @forelse($practica->observaciones as $obs)
                    <div class="list-group-item p-3 border rounded-3 mb-2 bg-light small">
                        <div class="text-dark mb-1">{{ $obs->detalle }}</div>
                        <div class="text-muted d-flex justify-content-between" style="font-size: 0.75rem;">
                            <span><i class="bi bi-person me-1"></i> {{ $obs->tutor->nombre_completo ?? 'Docente Tutor' }}</span>
                            <span>{{ $obs->fecha->format('d/m/Y H:i') }}</span>
                        </div>
                    </div>
                @empty
                    <div class="p-3 rounded-3 bg-light text-muted small text-center">
                        El estudiante aún no tiene observaciones registradas.
                    </div>
                @endforelse
            </div>
        </div>
    </div>
</div>

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
                        @foreach($tutores as $t)
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

<!-- Modal Registrar Horas -->
<div class="modal fade" id="modalRegistrarHoras" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow rounded-4">
            <div class="modal-header text-white" style="background-color: var(--upds-navy);">
                <h6 class="modal-title fw-bold">Registrar Jornada de Práctica</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('seguimiento.horas.store', $practica->id) }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Fecha de la jornada *</label>
                        <input type="date" name="fecha" class="form-control" value="{{ date('Y-m-d') }}" max="{{ date('Y-m-d') }}" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Horas realizadas * (entre 0.5 y {{ config('practicas.max_horas_diarias', 8.0) }})</label>
                        <input type="number" step="0.5" name="horas" class="form-control" min="0.5" max="{{ config('practicas.max_horas_diarias', 8.0) }}" value="4" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Descripción detallada de actividades *</label>
                        <textarea name="descripcion" class="form-control" rows="3" required placeholder="Describe las tareas, funciones o proyectos desarrollados durante la jornada..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-sm btn-light" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-sm btn-upds">Registrar Jornada</button>
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
                <h6 class="modal-title fw-bold">Presentar Informe de Avance</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('seguimiento.informes.store', $practica->id) }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Título del Informe *</label>
                        <input type="text" name="titulo" class="form-control" required placeholder="Ej. Reporte N° 3 - Quincenal">
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label small fw-semibold">Período Inicio *</label>
                            <input type="date" name="periodo_inicio" class="form-control" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-semibold">Período Fin *</label>
                            <input type="date" name="periodo_fin" class="form-control" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Resumen de Actividades *</label>
                        <textarea name="resumen" class="form-control" rows="3" required placeholder="Sintetiza los avances y logros del período..."></textarea>
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

<!-- Modal Registrar Evaluación Final (RF-13: Emisión del tutor; no concluye la práctica) -->
<div class="modal fade" id="modalEvaluacionFinal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow rounded-4">
            <div class="modal-header bg-primary text-white">
                <h6 class="modal-title fw-bold"><i class="bi bi-award me-2"></i>Evaluación Final del Docente Tutor</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('seguimiento.evaluacion.store', $practica->id) }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="alert alert-info py-2 small mb-3">
                        <i class="bi bi-info-circle me-1"></i>
                        El Docente Tutor califica el desempeño final. El cierre institucional corresponde a la Coordinación Académica.
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Calificación Final (0 a 100) *</label>
                        <input type="number" step="0.5" name="calificacion" class="form-control" min="0" max="100" value="95" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Resultado *</label>
                        <select name="resultado" class="form-select" required>
                            <option value="Aprobado">Aprobado</option>
                            <option value="Reprobado">Reprobado</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Comentario / Opinión Evaluativa *</label>
                        <textarea name="comentario" class="form-control" rows="3" required placeholder="Dictamen del docente tutor sobre el desempeño del practicante..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-sm btn-light" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-sm btn-primary">Guardar Evaluación Final</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Registrar Evaluación Parcial -->
<div class="modal fade" id="modalEvaluacionParcial" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow rounded-4">
            <div class="modal-header text-white" style="background-color: var(--upds-navy);">
                <h6 class="modal-title fw-bold">Registrar Evaluación Parcial / Seguimiento</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('seguimiento.evaluacion-parcial.store', $practica->id) }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Período de Evaluación *</label>
                        <input type="text" name="periodo" class="form-control" placeholder="Ej. 1er Parcial - Avance 30%" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Fecha de Evaluación *</label>
                        <input type="date" name="fecha" class="form-control" value="{{ date('Y-m-d') }}" required>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label small fw-semibold">Calificación (0 a 100) *</label>
                            <input type="number" step="0.5" name="calificacion" class="form-control" min="0" max="100" value="85" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-semibold">Resultado *</label>
                            <select name="resultado" class="form-select" required>
                                <option value="Aprobado">Aprobado</option>
                                <option value="En seguimiento">En seguimiento</option>
                                <option value="Excelente">Excelente</option>
                                <option value="Bueno">Bueno</option>
                                <option value="Regular">Regular</option>
                                <option value="Reprobado">Reprobado</option>
                            </select>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Comentarios u Observaciones Pedagógicas</label>
                        <textarea name="comentario" class="form-control" rows="3" placeholder="Retroalimentación para el estudiante..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-sm btn-light" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-sm btn-upds">Guardar Evaluación</button>
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
@if(auth()->user()->isCoordinador())
    <!-- Modal Planificar Nueva Actividad -->
    <div class="modal fade" id="modalNuevaActividad" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow rounded-4">
                <div class="modal-header text-white" style="background-color: var(--upds-navy);">
                    <h6 class="modal-title fw-bold">Planificar Actividad en Cronograma</h6>
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
                            <label class="form-label small fw-semibold">Objetivo formativo / entregable</label>
                            <input type="text" name="objetivo" class="form-control" placeholder="Ej. Modelo relacional y diagramas ER">
                        </div>
                        <div class="row g-2 mb-3">
                            <div class="col-6">
                                <label class="form-label small fw-semibold">Fecha inicio</label>
                                <input type="date" name="fecha_inicio" class="form-control" value="{{ date('Y-m-d') }}">
                            </div>
                            <div class="col-6">
                                <label class="form-label small fw-semibold">Fecha estimada fin</label>
                                <input type="date" name="fecha_fin" class="form-control">
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
                                    <label class="form-label small fw-semibold">Estado *</label>
                                    <select name="estado" class="form-select" required>
                                        <option value="pendiente" {{ $act->estado == 'pendiente' ? 'selected' : '' }}>Pendiente</option>
                                        <option value="en_progreso" {{ $act->estado == 'en_progreso' ? 'selected' : '' }}>En progreso</option>
                                        <option value="completada" {{ $act->estado == 'completada' ? 'selected' : '' }}>Completada</option>
                                        <option value="cancelada" {{ $act->estado == 'cancelada' ? 'selected' : '' }}>Cancelada</option>
                                    </select>
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
@endsection

