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

<!-- Tabs con contadores reales -->
<ul class="nav nav-pills mb-4 gap-2" id="solicitudesTab" role="tablist">
    <li class="nav-item" role="presentation">
        <button class="nav-link {{ $tab === 'solicitudes' ? 'active' : '' }} fw-semibold rounded-pill px-4" 
                id="sol-tab" data-bs-toggle="pill" data-bs-target="#sol-content" type="button" role="tab">
            <i class="bi bi-file-text me-1"></i> Solicitudes ({{ $solicitudes->total() }})
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link {{ $tab === 'documentos' ? 'active' : '' }} fw-semibold rounded-pill px-4" 
                id="doc-tab" data-bs-toggle="pill" data-bs-target="#doc-content" type="button" role="tab">
            <i class="bi bi-paperclip me-1"></i> Documentos ({{ $documentos->total() }})
        </button>
    </li>
</ul>

<!-- Barra de Búsqueda y Filtros con Auto-búsqueda -->
<div class="card border-0 shadow-sm rounded-4 p-3 mb-4">
    <form action="{{ route('solicitudes.index') }}" method="GET" class="row g-2 align-items-center">
        <input type="hidden" name="tab" value="{{ $tab }}">
        <div class="col-12 col-md-5">
            <div class="input-group input-group-sm">
                <span class="input-group-text bg-light border-end-0"><i class="bi bi-search text-muted"></i></span>
                <input type="text" name="buscar" value="{{ request('buscar') }}" class="form-control bg-light border-start-0" 
                       placeholder="{{ auth()->user()->isEstudiante() ? 'Buscar por número u organización...' : 'Buscar por estudiante, RU u organización...' }}" 
                       data-auto-search autocomplete="off">
                @if(request('buscar'))
                    <a href="{{ route('solicitudes.index', ['tab' => $tab]) }}" class="btn btn-outline-secondary btn-sm" title="Limpiar"><i class="bi bi-x-lg"></i></a>
                @endif
            </div>
        </div>
        <div class="col-6 col-md-3">
            <select name="estado" class="form-select form-select-sm" onchange="this.form.submit()">
                <option value="">Todos los estados</option>
                <option value="presentada" {{ request('estado') === 'presentada' ? 'selected' : '' }}>Presentada</option>
                <option value="en_revision" {{ request('estado') === 'en_revision' ? 'selected' : '' }}>En revisión</option>
                <option value="observada" {{ request('estado') === 'observada' ? 'selected' : '' }}>Observada</option>
                <option value="aprobada" {{ request('estado') === 'aprobada' ? 'selected' : '' }}>Aprobada</option>
                <option value="rechazada" {{ request('estado') === 'rechazada' ? 'selected' : '' }}>Rechazada</option>
            </select>
        </div>
        <div class="col-6 col-md-2">
            <select name="per_page" class="form-select form-select-sm" onchange="this.form.submit()">
                <option value="10" {{ request('per_page', 10) == 10 ? 'selected' : '' }}>10 por pág.</option>
                <option value="25" {{ request('per_page') == 25 ? 'selected' : '' }}>25 por pág.</option>
                <option value="50" {{ request('per_page') == 50 ? 'selected' : '' }}>50 por pág.</option>
            </select>
        </div>
        <div class="col-12 col-md-2 text-md-end">
            <span class="small text-muted">Total: <strong>{{ $solicitudes->total() }}</strong></span>
        </div>
    </form>
</div>

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
                                <td colspan="7" class="text-center py-5">
                                    <div class="py-2">
                                        <i class="bi bi-search text-muted fs-1 d-block mb-2"></i>
                                        <h6 class="fw-bold text-dark mb-1">No se encontraron resultados</h6>
                                        <p class="text-muted small mb-3">
                                            @if(request('buscar'))
                                                No existen solicitudes que coincidan con "<strong>{{ request('buscar') }}</strong>"{{ request('estado') ? ' y estado ' . request('estado') : '' }}.
                                            @elseif(request('estado'))
                                                No existen solicitudes con el estado "<strong>{{ ucfirst(request('estado')) }}</strong>".
                                            @else
                                                No hay solicitudes registradas en este momento.
                                            @endif
                                        </p>
                                        @if(request('buscar') || request('estado'))
                                            <a href="{{ route('solicitudes.index', ['tab' => 'solicitudes']) }}" class="btn btn-sm btn-outline-primary rounded-pill px-4">
                                                <i class="bi bi-arrow-counterclockwise me-1"></i> Restaurar listado completo
                                            </a>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Controles de Paginación y Conteo (Solicitudes) -->
            @if($solicitudes->hasPages() || $solicitudes->total() > 0)
                <div class="card-footer bg-white border-0 py-3 px-4 d-flex flex-column flex-md-row justify-content-between align-items-center gap-2">
                    <span class="small text-muted">
                        Mostrando <strong>{{ $solicitudes->firstItem() ?? 0 }}</strong>–<strong>{{ $solicitudes->lastItem() ?? 0 }}</strong> de <strong>{{ $solicitudes->total() }}</strong> solicitudes
                    </span>
                    <div>
                        {{ $solicitudes->appends(['tab' => 'solicitudes'])->links('pagination::bootstrap-5') }}
                    </div>
                </div>
            @endif
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
                                <input type="file" name="archivo" accept=".pdf,.doc,.docx" class="d-none" required onchange="document.getElementById('fileNameText').innerText = this.files[0]?.name || ''">
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
                                {{-- RF-08 / RF-09: Solo el coordinador puede validar/rechazar documentos; tutor solo lee --}}
                                @if(auth()->user()->isCoordinador())
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
                            <div class="text-center text-muted py-5">
                                <i class="bi bi-file-earmark-x fs-1 d-block mb-2 text-muted"></i>
                                <h6 class="fw-bold text-dark mb-1">No se encontraron documentos</h6>
                                <p class="small text-muted mb-3">
                                    @if(request('buscar'))
                                        No hay documentos que coincidan con "<strong>{{ request('buscar') }}</strong>".
                                    @else
                                        No hay documentos cargados en el expediente todavía.
                                    @endif
                                </p>
                                @if(request('buscar') || request('estado_doc'))
                                    <a href="{{ route('solicitudes.index', ['tab' => 'documentos']) }}" class="btn btn-sm btn-outline-primary rounded-pill px-4">
                                        <i class="bi bi-arrow-counterclockwise me-1"></i> Ver todos los documentos
                                    </a>
                                @endif
                            </div>
                        @endforelse
                    </div>

                    @if($documentos->hasPages() || $documentos->total() > 0)
                        <div class="border-top pt-3 mt-3 d-flex flex-column flex-md-row justify-content-between align-items-center gap-2">
                            <span class="small text-muted">
                                Mostrando <strong>{{ $documentos->firstItem() ?? 0 }}</strong>–<strong>{{ $documentos->lastItem() ?? 0 }}</strong> de <strong>{{ $documentos->total() }}</strong> documentos
                            </span>
                            <div>
                                {{ $documentos->appends(['tab' => 'documentos'])->links('pagination::bootstrap-5') }}
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

