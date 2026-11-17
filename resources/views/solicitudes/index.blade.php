@extends('layouts.app')

@section('title', 'Solicitudes y Documentos - UPDSonline')

@section('content')
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
    <div>
        <h4 class="fw-bold mb-1 text-navy" style="color: var(--upds-navy);">
            <i class="bi bi-file-earmark-check text-primary me-2"></i>Mis solicitudes y documentos
        </h4>
        <p class="text-muted small mb-0">Gestión de trámites de inicio de práctica y validación de expedientes.</p>
    </div>
    @if(auth()->user()->isEstudiante())
        <a href="{{ route('solicitudes.create') }}" class="btn btn-upds">
            <i class="bi bi-plus-lg me-1"></i> + Nueva solicitud
        </a>
    @endif
</div>

<!-- Tabs idénticos a la Imagen 5 -->
<ul class="nav nav-pills mb-4 gap-2" id="solicitudesTab" role="tablist">
    <li class="nav-item" role="presentation">
        <button class="nav-link {{ $tab === 'solicitudes' ? 'active' : '' }} fw-semibold rounded-pill px-4" 
                id="sol-tab" data-bs-toggle="pill" data-bs-target="#sol-content" type="button" role="tab">
            <i class="bi bi-file-text me-1"></i> Solicitudes ({{ count($solicitudes) }})
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link {{ $tab === 'documentos' ? 'active' : '' }} fw-semibold rounded-pill px-4" 
                id="doc-tab" data-bs-toggle="pill" data-bs-target="#doc-content" type="button" role="tab">
            <i class="bi bi-paperclip me-1"></i> Documentos ({{ count($documentos) }})
        </button>
    </li>
</ul>

<div class="tab-content" id="solicitudesTabContent">
    <!-- PESTAÑA 1: SOLICITUDES -->
    <div class="tab-pane fade {{ $tab === 'solicitudes' ? 'show active' : '' }}" id="sol-content" role="tabpanel">
        <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr style="font-size: 0.85rem; color: #475569;">
                            <th class="ps-4">N°</th>
                            @if(!auth()->user()->isEstudiante())
                                <th>Estudiante</th>
                            @endif
                            <th>Tipo de solicitud</th>
                            <th>Organización Receptora</th>
                            <th>Fecha</th>
                            <th>Estado</th>
                            <th class="text-end pe-4">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($solicitudes as $sol)
                            <tr>
                                <td class="ps-4">
                                    <span class="badge bg-light text-dark border fw-bold">S-00{{ $sol->id }}</span>
                                </td>
                                @if(!auth()->user()->isEstudiante())
                                    <td>
                                        <div class="fw-semibold">{{ $sol->estudiante->nombre_completo }}</div>
                                        <small class="text-muted">{{ $sol->estudiante->carrera->nombre }} (RU: {{ $sol->estudiante->ru }})</small>
                                    </td>
                                @endif
                                <td>
                                    <span class="fw-semibold text-dark">Inicio de práctica profesional</span>
                                    <small class="text-muted d-block">{{ $sol->periodo->codigo ?? '2026-I' }}</small>
                                </td>
                                <td>
                                    <div class="fw-semibold">{{ $sol->organizacion->nombre }}</div>
                                    <small class="text-muted">{{ $sol->organizacion->rubro }}</small>
                                </td>
                                <td>
                                    <span class="text-muted small">{{ $sol->fecha_solicitud->format('d/m/Y') }}</span>
                                </td>
                                <td>
                                    <span class="badge-{{ $sol->estado }}">
                                        {{ ucfirst(str_replace('_', ' ', $sol->estado)) }}
                                    </span>
                                </td>
                                <td class="text-end pe-4">
                                    <a href="{{ route('solicitudes.show', $sol->id) }}" class="btn btn-sm btn-light border text-primary" title="Ver Expediente Completo">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-4 text-muted">
                                    No hay solicitudes registradas en este momento.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- PESTAÑA 2: DOCUMENTOS Y ADJUNTOS -->
    <div class="tab-pane fade {{ $tab === 'documentos' ? 'show active' : '' }}" id="doc-content" role="tabpanel">
        <div class="row g-4">
            <!-- Zona de Subir Documento (Drag & Drop Mockup) -->
            <div class="col-12 col-lg-5">
                <div class="card border-0 shadow-sm rounded-4 p-4 h-100">
                    <h5 class="fw-bold text-navy mb-3" style="color: var(--upds-navy);">
                        <i class="bi bi-cloud-arrow-up text-primary me-2"></i>Subir documento
                    </h5>

                    <form action="{{ route('documentos.store') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Solicitud de referencia *</label>
                            <select name="solicitud_id" class="form-select form-select-sm" required>
                                @foreach($solicitudes as $s)
                                    <option value="{{ $s->id }}">
                                        S-00{{ $s->id }} - {{ $s->organizacion->nombre }} ({{ $s->estado }})
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Tipo de documento *</label>
                            <select name="tipo_documento_id" class="form-select form-select-sm" required>
                                @foreach($tiposDocumento as $td)
                                    <option value="{{ $td->id }}">{{ $td->nombre }}</option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Dropzone estilizada -->
                        <div class="border border-2 border-dashed rounded-4 p-4 text-center mb-3" style="background-color: #f8fafc; border-color: #cbd5e1 !important;">
                            <i class="bi bi-file-earmark-arrow-up fs-1 text-primary mb-2"></i>
                            <p class="small text-muted mb-2">Arrastra tu archivo aquí o</p>
                            <label class="btn btn-sm btn-outline-primary fw-semibold px-3 mb-2" style="cursor: pointer;">
                                <i class="bi bi-folder2-open me-1"></i> Selecciona un archivo
                                <input type="file" name="archivo" class="d-none" required onchange="document.getElementById('fileNameText').innerText = this.files[0]?.name || ''">
                            </label>
                            <div id="fileNameText" class="text-success small fw-semibold"></div>
                            <small class="text-muted d-block mt-2" style="font-size: 0.75rem;">Formatos admitidos: PDF, DOC, DOCX (Máx. 10 MB)</small>
                        </div>

                        <button type="submit" class="btn btn-upds w-100">
                            <i class="bi bi-upload me-1"></i> Cargar documento al expediente
                        </button>
                    </form>
                </div>
            </div>

            <!-- Listado y Revisión de Documentos -->
            <div class="col-12 col-lg-7">
                <div class="card border-0 shadow-sm rounded-4 p-4 h-100">
                    <h5 class="fw-bold text-navy mb-3" style="color: var(--upds-navy);">
                        <i class="bi bi-files text-primary me-2"></i>Documentos en el expediente
                    </h5>

                    <div class="list-group list-group-flush">
                        @forelse($documentos as $doc)
                            <div class="list-group-item p-3 border rounded-3 mb-3 bg-light">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div class="d-flex align-items-center gap-3">
                                        <div class="rounded-3 bg-white p-2 text-primary shadow-sm border fs-4">
                                            <i class="bi bi-file-earmark-pdf-fill text-danger"></i>
                                        </div>
                                        <div>
                                            <div class="fw-bold text-dark">{{ $doc->nombre_original }}</div>
                                            <small class="text-muted">
                                                {{ $doc->tipoDocumento->nombre }} &bull; {{ number_format($doc->tamano_bytes / 1024, 1) }} KB
                                            </small>
                                            <div class="mt-1">
                                                <span class="badge-{{ $doc->estado }}">{{ ucfirst($doc->estado) }}</span>
                                                @if($doc->observacion_validacion)
                                                    <small class="text-muted ms-2 fst-italic">"{{ $doc->observacion_validacion }}"</small>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                    <a href="{{ route('documentos.download', $doc->id) }}" class="btn btn-sm btn-outline-secondary" title="Descargar archivo privado">
                                        <i class="bi bi-download"></i>
                                    </a>
                                </div>

                                <!-- Acciones de revisión para Coordinador / Tutor (Exactas a la Imagen 5) -->
                                @if(auth()->user()->isCoordinador() || auth()->user()->isTutor())
                                    <div class="mt-3 pt-2 border-top d-flex gap-2 justify-content-end align-items-center">
                                        <span class="small text-muted me-auto">Revisión del documento:</span>
                                        <form action="{{ route('documentos.validar', $doc->id) }}" method="POST" class="d-inline">
                                            @csrf
                                            <input type="hidden" name="estado" value="validado">
                                            <button type="submit" class="btn btn-sm btn-success fw-semibold py-1 px-3">
                                                <i class="bi bi-check-lg"></i> Aprobar
                                            </button>
                                        </form>
                                        <button type="button" class="btn btn-sm btn-warning text-dark fw-semibold py-1 px-2" data-bs-toggle="modal" data-bs-target="#modalObservarDoc{{ $doc->id }}">
                                            <i class="bi bi-exclamation-triangle"></i> Observar
                                        </button>
                                    </div>

                                    <!-- Modal Observar Doc -->
                                    <div class="modal fade" id="modalObservarDoc{{ $doc->id }}" tabindex="-1" aria-hidden="true">
                                        <div class="modal-dialog modal-dialog-centered">
                                            <div class="modal-content border-0 shadow rounded-4">
                                                <div class="modal-header bg-warning text-dark">
                                                    <h6 class="modal-title fw-bold">Observar Documento</h6>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                </div>
                                                <form action="{{ route('documentos.validar', $doc->id) }}" method="POST">
                                                    @csrf
                                                    <input type="hidden" name="estado" value="observado">
                                                    <div class="modal-body">
                                                        <label class="form-label small fw-semibold">Detalle de la observación / ajuste solicitado:</label>
                                                        <textarea name="observacion" class="form-control" rows="3" required placeholder="Indica el motivo de la observación..."></textarea>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-sm btn-light" data-bs-dismiss="modal">Cancelar</button>
                                                        <button type="submit" class="btn btn-sm btn-warning">Registrar observación</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                @endif
                            </div>
                        @empty
                            <div class="text-center text-muted py-4">
                                No hay documentos cargados en el expediente todavía.
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

