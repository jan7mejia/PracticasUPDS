@extends('layouts.app')

@section('title', 'Detalle de Solicitud - UPDSonline')

@section('content')
<div class="mb-4">
    <a href="{{ route('solicitudes.index') }}" class="text-decoration-none text-muted small">
        <i class="bi bi-arrow-left"></i> Volver a solicitudes
    </a>
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mt-2 gap-3">
        <div>
            <h4 class="fw-bold text-navy mb-1" style="color: var(--upds-navy);">
                Expediente de Solicitud: S-00{{ $solicitud->id }}
            </h4>
            <span class="text-muted small">Presentada el {{ $solicitud->fecha_solicitud->format('d/m/Y H:i') }}</span>
        </div>
        <div class="d-flex align-items-center gap-2">
            <span class="badge-{{ $solicitud->estado }} fs-6 px-3 py-2">
                {{ ucfirst(str_replace('_', ' ', $solicitud->estado)) }}
            </span>
            @if($solicitud->practica)
                <a href="{{ route('seguimiento.show', $solicitud->practica->id) }}" class="btn btn-sm btn-success">
                    <i class="bi bi-arrow-up-right-square me-1"></i> Ir al Seguimiento
                </a>
            @endif
        </div>
    </div>
</div>

<div class="row g-4">
    <!-- Columna Izquierda: Información de la Solicitud -->
    <div class="col-12 col-lg-4">
        <div class="card border-0 shadow-sm rounded-4 p-4 mb-4">
            <h6 class="fw-bold text-navy mb-3">Información del Postulante</h6>
            <div class="small">
                <div class="mb-2 text-muted">Estudiante:</div>
                <div class="fw-bold mb-3">{{ $solicitud->estudiante->nombre_completo }}</div>

                <div class="mb-2 text-muted">RU / Carrera:</div>
                <div class="fw-semibold mb-3"><code>{{ $solicitud->estudiante->ru }}</code> &bull; {{ $solicitud->estudiante->carrera->nombre }}</div>

                <div class="mb-2 text-muted">Semestre:</div>
                <div class="fw-semibold mb-3">{{ $solicitud->estudiante->semestre }}° Semestre</div>
            </div>

            <hr>

            <h6 class="fw-bold text-navy mb-3">Organización Receptora</h6>
            <div class="small">
                <div class="mb-2 text-muted">Empresa / Institución:</div>
                <div class="fw-bold mb-3 text-primary">{{ $solicitud->organizacion->nombre }}</div>

                <div class="mb-2 text-muted">Sector / Rubro:</div>
                <div class="fw-semibold mb-3">{{ $solicitud->organizacion->rubro }}</div>

                <div class="mb-2 text-muted">Contacto Empresa:</div>
                <div class="fw-semibold mb-3">{{ $solicitud->organizacion->responsables->first()?->nombre_completo ?? 'No asignado' }}</div>
            </div>

            <hr>

            <h6 class="fw-bold text-navy mb-2">Requisitos Académicos</h6>
            @php
                $reqsObligatorios = $solicitud->estudiante->requisitos->filter(fn($r) => $r->requisito && $r->requisito->obligatorio);
                $cumplidos = $reqsObligatorios->where('estado', 'cumple')->count();
                $totalOblig = $reqsObligatorios->count();
            @endphp
            <div class="small mb-3">
                <div class="d-flex justify-content-between mb-1">
                    <span class="text-muted">Obligatorios cumplidos:</span>
                    <strong>{{ $cumplidos }} de {{ $totalOblig }}</strong>
                </div>
                @if($totalOblig > 0 && $cumplidos === $totalOblig)
                    <span class="badge bg-success-subtle text-success border border-success-subtle w-100 py-1">
                        <i class="bi bi-check-circle me-1"></i> Requisitos Habilitados
                    </span>
                @else
                    <span class="badge bg-warning-subtle text-warning border border-warning-subtle w-100 py-1">
                        <i class="bi bi-exclamation-triangle me-1"></i> Requisitos Pendientes
                    </span>
                @endif
                <a href="{{ route('estudiantes.show', $solicitud->estudiante_id) }}" class="btn btn-xs btn-outline-primary w-100 mt-2" target="_blank">
                    <i class="bi bi-card-checklist me-1"></i> Verificar Ficha del Estudiante
                </a>
            </div>

            @if(auth()->user()->isCoordinador())
                <hr>
                <h6 class="fw-bold text-navy mb-3">Acciones de Coordinación</h6>
                <div class="d-grid gap-2">
                    <button type="button" class="btn btn-success btn-sm fw-semibold" data-bs-toggle="modal" data-bs-target="#modalAprobarSol">
                        <i class="bi bi-check-circle me-1"></i> Aprobar Solicitud e Iniciar Práctica
                    </button>
                    <button type="button" class="btn btn-warning text-dark btn-sm fw-semibold" data-bs-toggle="modal" data-bs-target="#modalObservarSol">
                        <i class="bi bi-exclamation-triangle me-1"></i> Observar Solicitud
                    </button>
                    <button type="button" class="btn btn-outline-danger btn-sm" data-bs-toggle="modal" data-bs-target="#modalRechazarSol">
                        <i class="bi bi-x-circle me-1"></i> Rechazar Solicitud
                    </button>
                </div>
            @endif
        </div>

        <!-- Historial de Auditoría / Trazabilidad de Estados -->
        <div class="card border-0 shadow-sm rounded-4 p-4">
            <h6 class="fw-bold text-navy mb-3">Historial de Trazabilidad</h6>
            <div class="timeline small">
                @forelse($solicitud->historial as $h)
                    <div class="border-start border-2 border-primary ps-3 pb-3 position-relative">
                        <div class="fw-bold text-dark">
                            {{ ucfirst(str_replace('_', ' ', $h->estado_nuevo)) }}
                            @if($h->estado_anterior && $h->estado_anterior !== $h->estado_nuevo)
                                <small class="text-muted fw-normal ms-1">← {{ ucfirst(str_replace('_', ' ', $h->estado_anterior)) }}</small>
                            @endif
                        </div>
                        <div class="text-muted" style="font-size: 0.75rem;">
                            {{ $h->fecha_cambio->format('d/m/Y H:i') }} &bull; Por: {{ $h->usuario->name }}
                        </div>
                        @if($h->motivo)
                            <div class="mt-1 p-2 bg-light rounded text-secondary" style="font-size: 0.8rem;">
                                {{ $h->motivo }}
                            </div>
                        @endif
                    </div>
                @empty
                    <div class="text-muted small">Sin cambios de estado registrados.</div>
                @endforelse
            </div>
        </div>
    </div>

    <!-- Columna Derecha: Documentos del Expediente -->
    <div class="col-12 col-lg-8">
        <div class="card border-0 shadow-sm rounded-4 p-4 mb-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="fw-bold text-navy mb-0" style="color: var(--upds-navy);">
                    <i class="bi bi-paperclip text-primary me-2"></i>Documentos Adjuntos en Expediente
                </h5>
                @if(auth()->user()->isEstudiante() || auth()->user()->isCoordinador() || auth()->user()->isAdmin())
                <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="collapse" data-bs-target="#formSubirDoc">
                    <i class="bi bi-cloud-arrow-up me-1"></i> + Adjuntar Documento
                </button>
                @endif
            </div>

            <!-- Formulario colapsable para adjuntar -->
            <div class="collapse mb-4" idformSubirDoc id="formSubirDoc">
                <div class="p-3 bg-light rounded-3 border">
                    <form action="{{ route('documentos.store') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <input type="hidden" name="solicitud_id" value="{{ $solicitud->id }}">
                        <div class="row g-2 align-items-end">
                            <div class="col-md-5">
                                <label class="form-label small fw-semibold">Tipo de documento *</label>
                                <select name="tipo_documento_id" class="form-select form-select-sm" required>
                                    @foreach($tiposDocumento as $td)
                                        <option value="{{ $td->id }}">{{ $td->nombre }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-5">
                                <label class="form-label small fw-semibold">Archivo (PDF / DOC / DOCX) *</label>
                                <input type="file" name="archivo" accept=".pdf,.doc,.docx" class="form-control form-control-sm" required>
                            </div>
                            <div class="col-md-2">
                                <button type="submit" class="btn btn-sm btn-upds w-100">Subir</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Tabla de documentos adjuntos -->
            <div class="table-responsive">
                <table class="table align-middle">
                    <thead class="table-light">
                        <tr class="small text-muted">
                            <th>Documento</th>
                            <th>Tipo</th>
                            <th>Estado</th>
                            <th>Validación</th>
                            <th class="text-end">Acción</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($solicitud->documentos as $doc)
                            <tr>
                                <td>
                                    <div class="fw-semibold text-dark">{{ $doc->nombre_original }}</div>
                                    <small class="text-muted">{{ number_format($doc->tamano_bytes / 1024, 1) }} KB</small>
                                </td>
                                <td><span class="badge bg-light text-dark border">{{ $doc->tipoDocumento->nombre }}</span></td>
                                <td><span class="badge-{{ $doc->estado }}">{{ ucfirst($doc->estado) }}</span></td>
                                <td class="small text-muted">
                                    {{ $doc->observacion_validacion ?? 'Sin observaciones' }}
                                </td>
                                <td class="text-end">
                                    <div class="d-flex justify-content-end gap-1">
                                        <a href="{{ route('documentos.download', $doc->id) }}" class="btn btn-sm btn-light border text-primary" title="Descargar">
                                            <i class="bi bi-download"></i>
                                        </a>
                                        @if(auth()->user()->isCoordinador() && $doc->estado === 'pendiente')
                                            <form action="{{ route('documentos.validar', $doc->id) }}" method="POST" class="d-inline">
                                                @csrf
                                                <input type="hidden" name="estado" value="validado">
                                                <button type="submit" class="btn btn-sm btn-success py-1 px-2" title="Validar Documento">
                                                    <i class="bi bi-check-lg"></i>
                                                </button>
                                            </form>
                                            <button type="button" class="btn btn-sm btn-danger py-1 px-2" data-bs-toggle="modal" data-bs-target="#modalRechazarDoc{{ $doc->id }}" title="Rechazar / Observar Documento">
                                                <i class="bi bi-x-lg"></i>
                                            </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted py-4">No se han subido documentos a esta solicitud todavía.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal Aprobar Solicitud -->
<div class="modal fade" id="modalAprobarSol" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow rounded-4">
            <div class="modal-header bg-success text-white">
                <h6 class="modal-title fw-bold">Aprobar Solicitud de Práctica</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('solicitudes.cambiar-estado', $solicitud->id) }}" method="POST">
                @csrf
                <input type="hidden" name="nuevo_estado" value="aprobada">
                <div class="modal-body">
                    <p class="small text-muted mb-3">Al aprobar la solicitud, se habilitará automáticamente la creación del expediente de práctica y la posterior asignación de docente tutor.</p>
                    <label class="form-label small fw-semibold">Resolución / Motivo de Aprobación *</label>
                    <textarea name="motivo" class="form-control" rows="3" required placeholder="Ej. Solicitud aprobada tras verificar documentación completa y requisitos académicos cumplidos."></textarea>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-sm btn-light" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-sm btn-success">Confirmar Aprobación</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Observar Solicitud -->
<div class="modal fade" id="modalObservarSol" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow rounded-4">
            <div class="modal-header bg-warning text-dark">
                <h6 class="modal-title fw-bold">Observar Solicitud de Práctica</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('solicitudes.cambiar-estado', $solicitud->id) }}" method="POST">
                @csrf
                <input type="hidden" name="nuevo_estado" value="observada">
                <div class="modal-body">
                    <label class="form-label small fw-semibold">Detalle de la Observación (Se notificará al estudiante) *</label>
                    <textarea name="motivo" class="form-control" rows="3" required placeholder="Indica los documentos faltantes o correcciones requeridas..."></textarea>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-sm btn-light" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-sm btn-warning">Registrar Observación</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Rechazar Solicitud -->
<div class="modal fade" id="modalRechazarSol" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow rounded-4">
            <div class="modal-header bg-danger text-white">
                <h6 class="modal-title fw-bold">Rechazar Solicitud de Práctica</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('solicitudes.cambiar-estado', $solicitud->id) }}" method="POST">
                @csrf
                <input type="hidden" name="nuevo_estado" value="rechazada">
                <div class="modal-body">
                    <label class="form-label small fw-semibold">Motivo del Rechazo Definitivo *</label>
                    <textarea name="motivo" class="form-control" rows="3" required placeholder="Motivo institucional del rechazo..."></textarea>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-sm btn-light" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-sm btn-danger">Confirmar Rechazo</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modales para Rechazar / Observar Documentos -->
@foreach($solicitud->documentos as $doc)
    <div class="modal fade" id="modalRechazarDoc{{ $doc->id }}" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow rounded-4">
                <div class="modal-header bg-danger text-white">
                    <h6 class="modal-title fw-bold">Rechazar / Observar Documento</h6>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form action="{{ route('documentos.validar', $doc->id) }}" method="POST">
                    @csrf
                    <div class="modal-body">
                        <p class="small text-muted mb-2">Archivo: <strong>{{ $doc->nombre_original }}</strong> ({{ $doc->tipoDocumento->nombre }})</p>
                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Acción Resolutiva *</label>
                            <select name="estado" class="form-select" required>
                                <option value="observado">Observar (Permite al estudiante corregir)</option>
                                <option value="rechazado">Rechazar Definitivamente</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Motivo u Observación Detallada *</label>
                            <textarea name="observacion" class="form-control" rows="3" required placeholder="Indica el motivo del rechazo u observación para conocimiento del estudiante..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-sm btn-light" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-sm btn-danger">Confirmar Resolución</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endforeach
@endsection

