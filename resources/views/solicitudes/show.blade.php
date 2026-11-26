@extends('layouts.app')

@section('title', 'Expediente de Solicitud S-00' . $solicitud->id . ' - UPDSonline')

@section('content')
<!-- Encabezado de la Solicitud -->
<div class="mb-4">
    <div class="d-flex align-items-center gap-2 mb-2">
        <a href="{{ route('solicitudes.index') }}" class="btn btn-sm btn-outline-secondary rounded-pill px-3">
            <i class="bi bi-arrow-left me-1"></i> Volver a solicitudes
        </a>
        <span class="badge bg-light text-secondary border rounded-pill px-3 py-1">
            <i class="bi bi-folder2-open me-1 text-primary"></i> Trámite Académico
        </span>
    </div>

    <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3 bg-white p-4 rounded-4 shadow-sm border">
        <div>
            <div class="d-flex align-items-center gap-2 flex-wrap mb-1">
                <h4 class="fw-bold mb-0" style="color: var(--upds-navy);">
                    Expediente de Solicitud: S-00{{ $solicitud->id }}
                </h4>
                <span class="badge-{{ $solicitud->estado }} fs-6 px-3 py-1">
                    {{ ucfirst(str_replace('_', ' ', $solicitud->estado)) }}
                </span>
            </div>
            <div class="text-muted small">
                <i class="bi bi-calendar3 me-1"></i> Presentada el {{ $solicitud->fecha_solicitud ? $solicitud->fecha_solicitud->format('d/m/Y H:i') : '—' }}
                &bull; Período: <strong class="text-dark">{{ $solicitud->periodo->codigo ?? 'Período Vigente' }}</strong>
            </div>
        </div>

        <div class="d-flex align-items-center gap-2 flex-wrap">
            @if($solicitud->practica)
                <a href="{{ route('seguimiento.show', $solicitud->practica->id) }}" class="btn btn-sm btn-success rounded-pill px-3 fw-semibold">
                    <i class="bi bi-arrow-up-right-square me-1"></i> Ir al Seguimiento
                </a>
            @endif

            @if(auth()->user()->isCoordinador())
                @if(in_array($solicitud->estado, ['presentada', 'en_revision', 'observada']))
                    <button type="button" class="btn btn-sm btn-success rounded-pill px-3 fw-semibold" data-bs-toggle="modal" data-bs-target="#modalAprobarSol">
                        <i class="bi bi-check-circle me-1"></i> Aprobar Solicitud
                    </button>
                    <button type="button" class="btn btn-sm btn-warning text-dark rounded-pill px-3 fw-semibold" data-bs-toggle="modal" data-bs-target="#modalObservarSol">
                        <i class="bi bi-exclamation-triangle me-1"></i> Observar
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-danger rounded-pill px-3" data-bs-toggle="modal" data-bs-target="#modalRechazarSol">
                        <i class="bi bi-x-circle me-1"></i> Rechazar
                    </button>
                @endif
            @endif
        </div>
    </div>
</div>

<!-- Primera Sección: Información General (3 Tarjetas Equilibradas) -->
<div class="row g-4 mb-4">
    <!-- Tarjeta 1: Datos del Estudiante -->
    <div class="col-12 col-md-6 col-lg-4">
        <div class="card border-0 shadow-sm rounded-4 p-4 h-100">
            <div class="d-flex align-items-center gap-2 mb-3 pb-2 border-bottom">
                <div class="rounded-circle bg-primary-subtle text-primary p-2 d-flex align-items-center justify-content-center" style="width: 36px; height: 36px;">
                    <i class="bi bi-person-fill fs-5"></i>
                </div>
                <h6 class="fw-bold mb-0 text-navy" style="color: var(--upds-navy);">Datos del Estudiante</h6>
            </div>

            <div class="small">
                <div class="mb-2">
                    <span class="text-muted d-block">Nombre completo:</span>
                    <strong class="text-dark fs-6">{{ $solicitud->estudiante->nombre_completo }}</strong>
                </div>

                <div class="mb-2">
                    <span class="text-muted d-block">Registro Universitario (RU):</span>
                    <code class="fs-6 fw-bold text-primary">{{ $solicitud->estudiante->ru }}</code>
                </div>

                <div class="mb-2">
                    <span class="text-muted d-block">Carrera:</span>
                    <span class="fw-semibold text-dark">{{ $solicitud->estudiante->carrera->nombre ?? 'Sin carrera' }}</span>
                </div>

                <div class="mb-3">
                    <span class="text-muted d-block">Semestre actual:</span>
                    <span class="badge bg-light text-dark border">{{ $solicitud->estudiante->semestre }}° Semestre</span>
                </div>

                @if($solicitud->estudiante->telefono)
                    <div class="mb-3">
                        <span class="text-muted d-block">Contacto / Celular:</span>
                        <span class="text-dark">{{ $solicitud->estudiante->telefono }}</span>
                    </div>
                @endif
            </div>

            <div class="mt-auto pt-3 border-top">
                <a href="{{ route('estudiantes.show', $solicitud->estudiante_id) }}" class="btn btn-sm btn-outline-primary w-100 rounded-pill" target="_blank">
                    <i class="bi bi-card-checklist me-1"></i> Ver Ficha Académica
                </a>
            </div>
        </div>
    </div>

    <!-- Tarjeta 2: Organización Receptora -->
    <div class="col-12 col-md-6 col-lg-4">
        <div class="card border-0 shadow-sm rounded-4 p-4 h-100">
            <div class="d-flex align-items-center gap-2 mb-3 pb-2 border-bottom">
                <div class="rounded-circle bg-info-subtle text-info p-2 d-flex align-items-center justify-content-center" style="width: 36px; height: 36px;">
                    <i class="bi bi-building fs-5"></i>
                </div>
                <h6 class="fw-bold mb-0 text-navy" style="color: var(--upds-navy);">Organización Receptora</h6>
            </div>

            <div class="small">
                <div class="mb-2">
                    <span class="text-muted d-block">Empresa o Institución:</span>
                    <strong class="text-primary fs-6">{{ $solicitud->organizacion->nombre }}</strong>
                </div>

                <div class="mb-2">
                    <span class="text-muted d-block">Sector / Rubro:</span>
                    <span class="fw-semibold text-dark">{{ $solicitud->organizacion->rubro }}</span>
                </div>

                <div class="mb-2">
                    <span class="text-muted d-block">Dirección:</span>
                    <span class="text-secondary">{{ $solicitud->organizacion->direccion ?? 'Dirección institucional no especificada' }}</span>
                </div>

                <div class="mb-2">
                    <span class="text-muted d-block">Responsable de contacto:</span>
                    <span class="fw-semibold text-dark">
                        {{ $solicitud->organizacion->responsables->first()?->nombre_completo ?? 'No asignado aún' }}
                    </span>
                </div>

                <div class="mb-3">
                    <span class="text-muted d-block">Teléfono de contacto:</span>
                    <span class="text-dark">
                        {{ $solicitud->organizacion->telefono ?? ($solicitud->organizacion->responsables->first()?->telefono ?? 'No registrado') }}
                    </span>
                </div>
            </div>

            <div class="mt-auto pt-3 border-top">
                <span class="badge bg-light text-secondary border w-100 py-2 text-center d-block">
                    <i class="bi bi-shield-check text-success me-1"></i> Entidad registrada en UPDS
                </span>
            </div>
        </div>
    </div>

    <!-- Tarjeta 3: Información de Solicitud y Requisitos -->
    <div class="col-12 col-md-12 col-lg-4">
        <div class="card border-0 shadow-sm rounded-4 p-4 h-100">
            <div class="d-flex align-items-center gap-2 mb-3 pb-2 border-bottom">
                <div class="rounded-circle bg-warning-subtle text-warning p-2 d-flex align-items-center justify-content-center" style="width: 36px; height: 36px;">
                    <i class="bi bi-clipboard2-check fs-5"></i>
                </div>
                <h6 class="fw-bold mb-0 text-navy" style="color: var(--upds-navy);">Parámetros y Requisitos</h6>
            </div>

            @php
                $reqsObligatorios = $solicitud->estudiante->requisitos->filter(fn($r) => $r->requisito && $r->requisito->obligatorio);
                $cumplidos = $reqsObligatorios->where('estado', 'cumple')->count();
                $totalOblig = $reqsObligatorios->count();
            @endphp

            <div class="small">
                <div class="mb-2">
                    <span class="text-muted d-block">Período Académico:</span>
                    <strong class="text-dark">{{ $solicitud->periodo->nombre ?? ($solicitud->periodo->codigo ?? 'Período Activo') }}</strong>
                </div>

                <div class="mb-2">
                    <span class="text-muted d-block">Modalidad de práctica:</span>
                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle">
                        {{ ucfirst($solicitud->modalidad ?? 'Presencial') }}
                    </span>
                </div>

                <div class="mb-3">
                    <span class="text-muted d-block">Requisitos Académicos Obligatorios:</span>
                    <div class="d-flex justify-content-between align-items-center mt-1">
                        <strong>{{ $cumplidos }} de {{ $totalOblig }} cumplidos</strong>
                        @if($totalOblig > 0 && $cumplidos === $totalOblig)
                            <span class="badge bg-success text-white rounded-pill px-2">Habilitado</span>
                        @else
                            <span class="badge bg-warning text-dark rounded-pill px-2">Pendiente</span>
                        @endif
                    </div>
                    @if(auth()->user()->isCoordinador() || auth()->user()->isAdmin())
                        <div class="mt-2">
                            <button type="button" class="btn btn-outline-primary btn-sm w-100 py-1 rounded-pill" style="font-size: 0.78rem;" data-bs-toggle="modal" data-bs-target="#modalRequisitosExpediente">
                                <i class="bi bi-card-checklist me-1"></i>Ver / Validar Requisitos
                            </button>
                        </div>
                    @endif
                </div>

                <div class="mb-2">
                    <span class="text-muted d-block">Observaciones de la Solicitud:</span>
                    <div class="p-2 bg-light rounded text-secondary" style="font-size: 0.82rem;">
                        {{ $solicitud->observaciones ?? 'Sin observaciones iniciales registradas.' }}
                    </div>
                </div>
            </div>

            <div class="mt-auto pt-3 border-top">
                @if($solicitud->practica && $solicitud->practica->tutor)
                    <div class="small text-muted">
                        Tutor: <strong class="text-dark">{{ $solicitud->practica->tutor->nombre_completo }}</strong>
                    </div>
                @else
                    <div class="small text-muted fst-italic">
                        Tutor asignable tras la aprobación formal.
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Segunda Sección: Documentos Adjuntos en Expediente (ANCHO COMPLETO) -->
<div class="row g-4 mb-4">
    <div class="col-12">
        <div class="card border-0 shadow-sm rounded-4 p-4">
            @php
                $puedeAdjuntar = auth()->user()->isEstudiante() 
                    && auth()->user()->estudiante?->id === $solicitud->estudiante_id 
                    && in_array($solicitud->estado, ['presentada', 'en_revision', 'observada']);
            @endphp

            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-3 pb-2 border-bottom">
                <div class="d-flex align-items-center gap-2">
                    <div class="rounded-circle bg-primary-subtle text-primary p-2 d-flex align-items-center justify-content-center" style="width: 38px; height: 38px;">
                        <i class="bi bi-paperclip fs-5"></i>
                    </div>
                    <div>
                        <h5 class="fw-bold mb-0 text-navy" style="color: var(--upds-navy);">
                            Documentos Adjuntos en Expediente
                        </h5>
                        <small class="text-muted">
                            {{ $solicitud->documentos->count() }} {{ $solicitud->documentos->count() === 1 ? 'documento adjuntado' : 'documentos adjuntados' }}
                        </small>
                    </div>
                </div>

                @if($puedeAdjuntar)
                    <button type="button" class="btn btn-sm btn-outline-primary rounded-pill px-3 fw-semibold" data-bs-toggle="collapse" data-bs-target="#formSubirDoc">
                        <i class="bi bi-cloud-arrow-up me-1"></i> + Adjuntar Documento
                    </button>
                @endif
            </div>

            <!-- Formulario colapsable para adjuntar documento (Exclusivo Estudiante Propietario) -->
            @if($puedeAdjuntar)
            <div class="collapse mb-4" id="formSubirDoc">
                <div class="p-4 bg-light rounded-4 border">
                    <h6 class="fw-bold text-navy mb-3">
                        <i class="bi bi-file-earmark-arrow-up text-primary me-2"></i>Cargar o Reemplazar Documento del Expediente
                    </h6>
                    <form action="{{ route('documentos.store') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <input type="hidden" name="solicitud_id" value="{{ $solicitud->id }}">
                        <div class="row g-3 align-items-end">
                            <div class="col-12 col-md-5">
                                <label class="form-label small fw-semibold text-muted mb-1">Tipo de documento *</label>
                                <select name="tipo_documento_id" id="selectTipoDoc" class="form-select form-select-sm" required>
                                    @foreach($tiposDocumento as $td)
                                        <option value="{{ $td->id }}" {{ $td->id == 2 ? 'selected' : '' }}>{{ $td->nombre }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-12 col-md-5">
                                <label class="form-label small fw-semibold text-muted mb-1">Archivo digital (PDF / DOC / DOCX, máx. 10 MB) *</label>
                                <input type="file" name="archivo" id="inputArchivoDoc" accept=".pdf,.doc,.docx" class="form-control form-control-sm" required>
                            </div>
                            <div class="col-12 col-md-2">
                                <button type="submit" class="btn btn-sm btn-upds w-100 rounded-pill fw-semibold">
                                    <i class="bi bi-upload me-1"></i> Subir
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            @endif

            <!-- Tabla de documentos a ancho completo -->
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr class="small text-muted">
                            <th class="ps-3" style="min-width: 200px;">Documento</th>
                            <th style="min-width: 140px;">Tipo</th>
                            <th style="min-width: 130px;">Fecha de Carga</th>
                            <th style="min-width: 110px;">Estado</th>
                            <th style="min-width: 220px;">Validación / Observación</th>
                            <th class="text-end pe-3" style="min-width: 140px;">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($solicitud->documentos as $doc)
                            <tr>
                                <td class="ps-3">
                                    <div class="d-flex align-items-center gap-2">
                                        <i class="bi bi-file-earmark-pdf fs-4 text-danger"></i>
                                        <div>
                                            <div class="fw-semibold text-dark">
                                                {{ $doc->nombre_original }}
                                                @if($doc->version_documento > 1)
                                                    <span class="badge bg-secondary-subtle text-secondary border rounded-pill ms-1" style="font-size: 0.7rem;">v{{ $doc->version_documento }}</span>
                                                @endif
                                            </div>
                                            <small class="text-muted">{{ number_format($doc->tamano_bytes / 1024, 1) }} KB</small>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark border">
                                        {{ $doc->tipoDocumento->nombre ?? 'Documento' }}
                                    </span>
                                </td>
                                <td class="small text-muted">
                                    {{ $doc->created_at ? $doc->created_at->format('d/m/Y H:i') : '—' }}
                                </td>
                                <td>
                                    <span class="badge-{{ $doc->estado }} px-2 py-1">
                                        {{ ucfirst($doc->estado) }}
                                    </span>
                                </td>
                                <td class="small">
                                    @if(in_array($doc->estado, ['observado', 'rechazado']))
                                        <div class="p-2 rounded bg-warning-subtle text-dark border border-warning-subtle small">
                                            <strong class="d-block text-danger" style="font-size: 0.75rem;">
                                                <i class="bi bi-exclamation-triangle-fill me-1"></i>Motivo de {{ ucfirst($doc->estado) }}:
                                            </strong>
                                            <span>{{ $doc->observacion_validacion }}</span>
                                        </div>
                                    @elseif($doc->observacion_validacion)
                                        <span class="text-dark">{{ $doc->observacion_validacion }}</span>
                                    @else
                                        <span class="text-muted fst-italic">Sin observaciones registradas</span>
                                    @endif
                                </td>
                                <td class="text-end pe-3">
                                    <div class="d-flex justify-content-end align-items-center gap-1">
                                        {{-- Botón de descarga institucional --}}
                                        <a href="{{ route('documentos.download', $doc->id) }}" class="btn btn-sm btn-outline-primary" title="Descargar documento">
                                            <i class="bi bi-download"></i>
                                        </a>

                                        {{-- Acción para el Estudiante: Subir reemplazo si fue observado/rechazado --}}
                                        @if($puedeAdjuntar && in_array($doc->estado, ['observado', 'rechazado']))
                                            <button type="button" class="btn btn-sm btn-outline-warning text-dark" onclick="abrirReemplazoDoc({{ $doc->tipo_documento_id }})" title="Volver a adjuntar documento corregido">
                                                <i class="bi bi-arrow-repeat me-1"></i> Corregir
                                            </button>
                                        @endif

                                        {{-- Acciones EXCLUSIVAS de Coordinación Académica --}}
                                        @if(auth()->user()->isCoordinador() && in_array($doc->estado, ['pendiente', 'observado']))
                                            <form action="{{ route('documentos.validar', $doc->id) }}" method="POST" class="d-inline">
                                                @csrf
                                                <input type="hidden" name="estado" value="validado">
                                                <button type="submit" class="btn btn-sm btn-success" title="Validar documento">
                                                    <i class="bi bi-check-lg"></i>
                                                </button>
                                            </form>
                                            <button type="button" class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#modalRechazarDoc{{ $doc->id }}" title="Observar o rechazar documento">
                                                <i class="bi bi-x-lg"></i>
                                            </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center py-5">
                                    <div class="py-3">
                                        <i class="bi bi-folder-x fs-1 text-muted opacity-50 d-block mb-2"></i>
                                        <h6 class="fw-semibold text-muted">No existen documentos adjuntos en este expediente</h6>
                                        <p class="small text-muted mb-3">Sube los requisitos documentales solicitados para proceder con la revisión académica.</p>
                                        @if($puedeAdjuntar)
                                            <button type="button" class="btn btn-sm btn-upds rounded-pill px-4" data-bs-toggle="collapse" data-bs-target="#formSubirDoc">
                                                <i class="bi bi-cloud-arrow-up me-1"></i> Adjuntar Primer Documento
                                            </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Tercera Sección: Historial de Trazabilidad y Auditoría (ANCHO COMPLETO) -->
<div class="row g-4 mb-4">
    <div class="col-12">
        <div class="card border-0 shadow-sm rounded-4 p-4">
            <div class="d-flex align-items-center gap-2 mb-3 pb-2 border-bottom">
                <div class="rounded-circle bg-secondary-subtle text-secondary p-2 d-flex align-items-center justify-content-center" style="width: 38px; height: 38px;">
                    <i class="bi bi-clock-history fs-5"></i>
                </div>
                <div>
                    <h5 class="fw-bold mb-0 text-navy" style="color: var(--upds-navy);">
                        Historial de Trazabilidad y Seguimiento de Estados
                    </h5>
                    <small class="text-muted">Registro de auditoría con las resoluciones y cambios de estado efectuados.</small>
                </div>
            </div>

            <div class="timeline p-2">
                @forelse($solicitud->historial as $h)
                    <div class="d-flex gap-3 mb-3 pb-3 border-bottom position-relative">
                        <div class="mt-1">
                            <span class="badge bg-primary rounded-circle p-2 d-inline-flex align-items-center justify-content-center" style="width: 28px; height: 28px;">
                                <i class="bi bi-arrow-repeat text-white" style="font-size: 0.8rem;"></i>
                            </span>
                        </div>
                        <div class="flex-grow-1">
                            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-1 mb-1">
                                <div class="fw-bold text-dark fs-6">
                                    Estado: <span class="badge-{{ $h->estado_nuevo }} px-2 py-1">{{ ucfirst(str_replace('_', ' ', $h->estado_nuevo)) }}</span>
                                    @if($h->estado_anterior && $h->estado_anterior !== $h->estado_nuevo)
                                        <small class="text-muted fw-normal ms-2">
                                            (Anterior: <span class="text-secondary">{{ ucfirst(str_replace('_', ' ', $h->estado_anterior)) }}</span>)
                                        </small>
                                    @endif
                                </div>
                                <div class="small text-muted">
                                    <i class="bi bi-calendar-event me-1"></i> {{ $h->fecha_cambio ? $h->fecha_cambio->format('d/m/Y H:i') : '—' }}
                                    &bull; Registrado por: <strong class="text-dark">{{ $h->usuario->name ?? 'Sistema' }}</strong>
                                </div>
                            </div>
                            @if($h->motivo)
                                <div class="p-3 bg-light rounded-3 text-secondary border small mt-2">
                                    <strong class="text-dark d-block mb-1"><i class="bi bi-chat-left-quote me-1 text-primary"></i>Motivo / Observación:</strong>
                                    {{ $h->motivo }}
                                </div>
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="text-center py-4 text-muted">
                        <i class="bi bi-info-circle fs-3 d-block mb-2 opacity-50"></i>
                        No existen cambios de estado registrados en el historial de esta solicitud aún.
                    </div>
                @endforelse
            </div>
        </div>
    </div>
</div>

<!-- Modal Aprobar Solicitud -->
<div class="modal fade" id="modalAprobarSol" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow rounded-4">
            <div class="modal-header bg-success text-white">
                <h6 class="modal-title fw-bold"><i class="bi bi-check-circle me-2"></i>Aprobar Solicitud de Práctica</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('solicitudes.cambiar-estado', $solicitud->id) }}" method="POST">
                @csrf
                <input type="hidden" name="nuevo_estado" value="aprobada">
                <div class="modal-body">
                    <p class="small text-muted mb-3">
                        Al aprobar la solicitud, se habilitará automáticamente la creación del expediente de práctica y la posterior asignación de docente tutor.
                    </p>
                    <label class="form-label small fw-semibold">Resolución / Motivo de Aprobación *</label>
                    <textarea name="motivo" class="form-control" rows="3" required placeholder="Ej. Solicitud aprobada tras verificar documentación completa y requisitos académicos cumplidos."></textarea>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-sm btn-light rounded-pill px-3" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-sm btn-success rounded-pill px-3 fw-semibold">Confirmar Aprobación</button>
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
                <h6 class="modal-title fw-bold"><i class="bi bi-exclamation-triangle me-2"></i>Observar Solicitud de Práctica</h6>
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
                    <button type="button" class="btn btn-sm btn-light rounded-pill px-3" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-sm btn-warning rounded-pill px-3 fw-semibold">Registrar Observación</button>
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
                <h6 class="modal-title fw-bold"><i class="bi bi-x-circle me-2"></i>Rechazar Solicitud de Práctica</h6>
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
                    <button type="button" class="btn btn-sm btn-light rounded-pill px-3" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-sm btn-danger rounded-pill px-3 fw-semibold">Confirmar Rechazo</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modales para Rechazar / Observar Documentos (Exclusivo Coordinación Académica) -->
@if(auth()->user()->isCoordinador())
    @foreach($solicitud->documentos as $doc)
        <div class="modal fade" id="modalRechazarDoc{{ $doc->id }}" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content border-0 shadow rounded-4">
                    <div class="modal-header bg-danger text-white">
                        <h6 class="modal-title fw-bold"><i class="bi bi-file-earmark-x me-2"></i>Rechazar / Observar Documento</h6>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <form action="{{ route('documentos.validar', $doc->id) }}" method="POST">
                        @csrf
                        <div class="modal-body">
                            <p class="small text-muted mb-2">
                                Archivo: <strong>{{ $doc->nombre_original }}</strong> ({{ $doc->tipoDocumento->nombre ?? 'Documento' }})
                            </p>
                            <div class="mb-3">
                                <label class="form-label small fw-semibold">Acción Resolutiva *</label>
                                <select name="estado" class="form-select" required>
                                    <option value="observado">Observar (Permite al estudiante corregir)</option>
                                    <option value="rechazado">Rechazar Definitivamente</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label small fw-semibold">Motivo u Observación Detallada (Obligatorio en español) *</label>
                                <textarea name="observacion" class="form-control" rows="3" required minlength="5" placeholder="Escribe el motivo detallado en español explicando por qué el documento es observado o rechazado para conocimiento del estudiante..."></textarea>
                                <div class="form-text small text-muted">El estudiante podrá visualizar este motivo en su expediente para realizar las correcciones necesarias.</div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-sm btn-light rounded-pill px-3" data-bs-dismiss="modal">Cancelar</button>
                            <button type="submit" class="btn btn-sm btn-danger rounded-pill px-3 fw-semibold">Confirmar Resolución</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endforeach
@endif

<!-- Modal Ver / Validar Requisitos Académicos del Estudiante -->
@if(auth()->user()->isCoordinador() || auth()->user()->isAdmin())
<div class="modal fade" id="modalRequisitosExpediente" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow rounded-4 overflow-hidden">
            <div class="modal-header bg-light">
                <h6 class="modal-title fw-bold text-navy" style="color:var(--upds-navy);">
                    <i class="bi bi-card-checklist text-primary me-2"></i>Requisitos Académicos - {{ $solicitud->estudiante->nombre_completo }}
                </h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div class="alert alert-info py-2 px-3 small rounded-3 mb-3">
                    <i class="bi bi-info-circle me-1"></i>
                    Verificación de requisitos reglamentarios para habilitar la práctica profesional (RU: <strong>{{ $solicitud->estudiante->ru }}</strong> &bull; {{ $solicitud->estudiante->carrera->nombre ?? 'UPDS' }} &bull; Semestre {{ $solicitud->estudiante->semestre }}°).
                </div>

                <div class="table-responsive rounded-3 border mb-3">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr class="small text-muted">
                                <th class="ps-3 py-2">Requisito</th>
                                <th class="py-2">Tipo</th>
                                <th class="py-2">Estado</th>
                                <th class="py-2 text-end pe-3">Acción Rápida</th>
                            </tr>
                        </thead>
                        <tbody class="small">
                            @forelse($solicitud->estudiante->requisitos as $req)
                                <tr>
                                    <td class="ps-3">
                                        <div class="fw-semibold text-dark">{{ $req->requisito->nombre ?? 'Requisito' }}</div>
                                        <small class="text-muted">{{ $req->requisito->descripcion ?? '' }}</small>
                                    </td>
                                    <td>
                                        @if($req->requisito && $req->requisito->obligatorio)
                                            <span class="badge bg-danger-subtle text-danger border border-danger-subtle">Obligatorio</span>
                                        @else
                                            <span class="badge bg-secondary-subtle text-secondary">Opcional</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($req->estado === 'cumple')
                                            <span class="badge bg-success-subtle text-success border border-success-subtle">
                                                <i class="bi bi-check-circle me-1"></i>Cumple
                                            </span>
                                        @elseif($req->estado === 'no_cumple')
                                            <span class="badge bg-danger-subtle text-danger border border-danger-subtle">
                                                <i class="bi bi-x-circle me-1"></i>No Cumple
                                            </span>
                                        @else
                                            <span class="badge bg-warning-subtle text-dark border border-warning-subtle">
                                                <i class="bi bi-clock me-1"></i>Pendiente
                                            </span>
                                        @endif
                                    </td>
                                    <td class="text-end pe-3">
                                        <form action="{{ route('estudiantes.requisitos.verificar', [$solicitud->estudiante_id, $req->id]) }}" method="POST" class="d-inline">
                                            @csrf
                                            @if($req->estado !== 'cumple')
                                                <input type="hidden" name="estado" value="cumple">
                                                <input type="hidden" name="observacion" value="Validado conforme en expediente de solicitud.">
                                                <button type="submit" class="btn btn-sm btn-outline-success py-0 px-2 rounded-pill" title="Marcar como cumplido">
                                                    <i class="bi bi-check-lg me-1"></i>Aprobar
                                                </button>
                                            @else
                                                <input type="hidden" name="estado" value="pendiente">
                                                <input type="hidden" name="observacion" value="Revertido a pendiente para re-verificación.">
                                                <button type="submit" class="btn btn-sm btn-outline-secondary py-0 px-2 rounded-pill" title="Revertir a pendiente">
                                                    <i class="bi bi-arrow-counterclockwise"></i>
                                                </button>
                                            @endif
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center py-3 text-muted">No hay requisitos registrados para este estudiante.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="d-flex justify-content-between align-items-center">
                    <form action="{{ route('estudiantes.requisitos.validar-todos', $solicitud->estudiante_id) }}" method="POST">
                        @csrf
                        <button type="submit" class="btn btn-sm btn-success rounded-pill px-3 fw-semibold">
                            <i class="bi bi-check-all me-1"></i>Validar Todos los Requisitos como CUMPLE
                        </button>
                    </form>
                    <a href="{{ route('estudiantes.show', $solicitud->estudiante_id) }}" class="btn btn-sm btn-outline-secondary rounded-pill px-3">
                        <i class="bi bi-person-lines-fill me-1"></i>Ver Ficha Académica Completa
                    </a>
                </div>
            </div>
            <div class="modal-footer bg-light py-2">
                <button type="button" class="btn btn-sm btn-secondary rounded-pill px-4" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>
@endif
@endsection

@section('scripts')
<script>
function abrirReemplazoDoc(tipoId) {
    var collapseEl = document.getElementById('formSubirDoc');
    if (collapseEl) {
        var bsCollapse = bootstrap.Collapse.getOrCreateInstance(collapseEl);
        bsCollapse.show();
        var selectTipo = document.getElementById('selectTipoDoc');
        if (selectTipo) {
            selectTipo.value = tipoId;
        }
        collapseEl.scrollIntoView({ behavior: 'smooth', block: 'center' });
        var fileInput = document.getElementById('inputArchivoDoc');
        if (fileInput) {
            fileInput.focus();
        }
    }
}
</script>
@endsection
