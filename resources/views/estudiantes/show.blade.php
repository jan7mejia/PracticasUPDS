@extends('layouts.app')

@section('title', 'Ficha del Estudiante ' . $estudiante->nombre_completo . ' - UPDSonline')

@section('content')
<!-- Encabezado Ficha del Estudiante -->
<div class="mb-4">
    <div class="d-flex align-items-center gap-2 mb-2">
        <a href="{{ route('estudiantes.index') }}" class="btn btn-sm btn-outline-secondary rounded-pill px-3">
            <i class="bi bi-arrow-left me-1"></i> Volver al listado de estudiantes
        </a>
        <span class="badge bg-light text-secondary border rounded-pill px-3 py-1">
            <i class="bi bi-mortarboard me-1 text-primary"></i> Padrón Estudiantil
        </span>
    </div>

    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 bg-white p-4 rounded-4 shadow-sm border">
        <div class="d-flex align-items-center gap-3">
            <div class="user-avatar" style="width: 58px; height: 58px; font-size: 1.6rem; background: var(--upds-primary); color: white; display: flex; align-items: center; justify-content: center; border-radius: 50%; font-weight: 700;">
                {{ strtoupper(substr($estudiante->nombres, 0, 1)) }}
            </div>
            <div>
                <div class="d-flex align-items-center gap-2 flex-wrap mb-1">
                    <h4 class="fw-bold mb-0" style="color: var(--upds-navy);">
                        {{ $estudiante->nombre_completo }}
                    </h4>
                    <span class="badge-{{ $estudiante->estado }} fs-6 px-3 py-1">
                        {{ ucfirst($estudiante->estado) }}
                    </span>
                </div>
                <div class="text-muted small">
                    RU: <code class="fw-bold text-primary fs-6">{{ $estudiante->ru }}</code> &bull; 
                    Carrera: <strong class="text-dark">{{ $estudiante->carrera->nombre ?? 'Sin carrera' }}</strong> &bull; 
                    Semestre: <strong class="text-dark">{{ $estudiante->semestre }}°</strong>
                </div>
            </div>
        </div>

        <div>
            <a href="{{ route('estudiantes.edit', $estudiante->id) }}" class="btn btn-primary btn-sm rounded-pill px-3 fw-semibold">
                <i class="bi bi-pencil me-1"></i> Editar Datos
            </a>
        </div>
    </div>
</div>

<!-- Primera Sección: Resumen de Ficha en 3 Tarjetas Proporcionales -->
<div class="row g-4 mb-4">
    <!-- Tarjeta 1: Datos Personales y Contacto -->
    <div class="col-12 col-md-6 col-lg-4">
        <div class="card border-0 shadow-sm rounded-4 p-4 h-100">
            <div class="d-flex align-items-center gap-2 mb-3 pb-2 border-bottom">
                <div class="rounded-circle bg-primary-subtle text-primary p-2 d-flex align-items-center justify-content-center" style="width: 36px; height: 36px;">
                    <i class="bi bi-person-lines-fill fs-5"></i>
                </div>
                <h6 class="fw-bold mb-0 text-navy" style="color: var(--upds-navy);">Datos Personales y Contacto</h6>
            </div>

            <div class="small">
                <div class="mb-2">
                    <span class="text-muted d-block">Cédula de Identidad (CI):</span>
                    <strong class="text-dark">{{ $estudiante->ci ?? 'No registrado' }}</strong>
                </div>

                <div class="mb-2">
                    <span class="text-muted d-block">Teléfono / Celular:</span>
                    <span class="fw-semibold text-dark">{{ $estudiante->telefono ?? 'No registrado' }}</span>
                </div>

                <div class="mb-3">
                    <span class="text-muted d-block">Cuenta de Acceso al Sistema:</span>
                    @if($estudiante->user)
                        <span class="badge bg-success-subtle text-success border border-success-subtle mt-1 d-inline-block">
                            <i class="bi bi-check-circle me-1"></i> {{ $estudiante->user->email }}
                        </span>
                    @else
                        <span class="badge bg-warning-subtle text-warning border border-warning-subtle mt-1 d-inline-block">
                            <i class="bi bi-clock-history me-1"></i> Pendiente de vinculación
                        </span>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Tarjeta 2: Información Académica Universitaria -->
    <div class="col-12 col-md-6 col-lg-4">
        <div class="card border-0 shadow-sm rounded-4 p-4 h-100">
            <div class="d-flex align-items-center gap-2 mb-3 pb-2 border-bottom">
                <div class="rounded-circle bg-info-subtle text-info p-2 d-flex align-items-center justify-content-center" style="width: 36px; height: 36px;">
                    <i class="bi bi-mortarboard fs-5"></i>
                </div>
                <h6 class="fw-bold mb-0 text-navy" style="color: var(--upds-navy);">Información Universitaria</h6>
            </div>

            <div class="small">
                <div class="mb-2">
                    <span class="text-muted d-block">Carrera:</span>
                    <strong class="text-dark">{{ $estudiante->carrera->nombre ?? 'Sin asignar' }}</strong>
                </div>

                <div class="mb-2">
                    <span class="text-muted d-block">Código de Carrera:</span>
                    <code>{{ $estudiante->carrera->codigo ?? 'UPDS' }}</code>
                </div>

                <div class="mb-3">
                    <span class="text-muted d-block">Semestre en curso:</span>
                    <span class="badge bg-primary rounded-pill px-3 py-1">{{ $estudiante->semestre }}° Semestre</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Tarjeta 3: Habilitación y Prácticas -->
    <div class="col-12 col-md-12 col-lg-4">
        <div class="card border-0 shadow-sm rounded-4 p-4 h-100">
            <div class="d-flex align-items-center gap-2 mb-3 pb-2 border-bottom">
                <div class="rounded-circle bg-success-subtle text-success p-2 d-flex align-items-center justify-content-center" style="width: 36px; height: 36px;">
                    <i class="bi bi-shield-check fs-5"></i>
                </div>
                <h6 class="fw-bold mb-0 text-navy" style="color: var(--upds-navy);">Estado de Habilitación</h6>
            </div>

            @php
                $reqsObligatorios = $estudiante->requisitos->filter(fn($r) => $r->requisito && $r->requisito->obligatorio);
                $cumplidos = $reqsObligatorios->where('estado', 'cumple')->count();
                $totalOblig = $reqsObligatorios->count();
            @endphp

            <div class="small">
                <div class="mb-2">
                    <span class="text-muted d-block">Requisitos Obligatorios:</span>
                    <strong class="text-dark">{{ $cumplidos }} de {{ $totalOblig }} cumplidos</strong>
                </div>

                <div class="mb-3">
                    <span class="text-muted d-block">Condición para Prácticas:</span>
                    @if($totalOblig > 0 && $cumplidos === $totalOblig)
                        <span class="badge bg-success-subtle text-success border border-success-subtle py-1 px-2 mt-1">
                            <i class="bi bi-check-circle me-1"></i> Habilitado para Postulación
                        </span>
                    @else
                        <span class="badge bg-warning-subtle text-warning border border-warning-subtle py-1 px-2 mt-1">
                            <i class="bi bi-exclamation-triangle me-1"></i> Requisitos Pendientes
                        </span>
                    @endif
                </div>

                <div>
                    <span class="text-muted d-block">Historial de Trámites:</span>
                    <span class="fw-semibold text-dark">{{ $estudiante->solicitudes->count() }} {{ $estudiante->solicitudes->count() === 1 ? 'solicitud registrada' : 'solicitudes registradas' }}</span>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Segunda Sección: Cumplimiento de Requisitos Académicos (ANCHO COMPLETO) -->
<div class="row g-4 mb-4">
    <div class="col-12">
        <div class="card border-0 shadow-sm rounded-4 p-4">
            <div class="d-flex justify-content-between align-items-center mb-3 pb-2 border-bottom">
                <div class="d-flex align-items-center gap-2">
                    <div class="rounded-circle bg-primary-subtle text-primary p-2 d-flex align-items-center justify-content-center" style="width: 38px; height: 38px;">
                        <i class="bi bi-card-checklist fs-5"></i>
                    </div>
                    <div>
                        <h5 class="fw-bold mb-0 text-navy" style="color: var(--upds-navy);">
                            Cumplimiento de Requisitos Académicos
                        </h5>
                        <small class="text-muted">Revisión de requisitos previos reglamentarios para habilitar el inicio de prácticas profesionales</small>
                    </div>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr class="small text-muted">
                            <th class="ps-3" style="min-width: 200px;">Requisito Institucional</th>
                            <th style="min-width: 120px;">Prioridad</th>
                            <th style="min-width: 140px;">Estado de Verificación</th>
                            <th style="min-width: 250px;">Observación / Evidencia Documental</th>
                            @if(auth()->user()->isCoordinador() || auth()->user()->isAdmin())
                                <th class="text-end pe-3" style="min-width: 110px;">Acción</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($estudiante->requisitos as $req)
                            <tr>
                                <td class="ps-3">
                                    <div class="fw-semibold text-dark">{{ $req->requisito->nombre }}</div>
                                    <small class="text-muted">{{ $req->requisito->descripcion ?? 'Requisito reglamentario' }}</small>
                                </td>
                                <td>
                                    @if($req->requisito->obligatorio)
                                        <span class="badge bg-danger-subtle text-danger border border-danger-subtle">Obligatorio</span>
                                    @else
                                        <span class="badge bg-secondary-subtle text-secondary">Opcional</span>
                                    @endif
                                </td>
                                <td>
                                    @if($req->estado === 'cumple')
                                        <span class="badge-activo px-2 py-1"><i class="bi bi-check-circle me-1"></i> Cumple</span>
                                    @elseif($req->estado === 'no_cumple')
                                        <span class="badge-observada px-2 py-1"><i class="bi bi-x-circle me-1"></i> No cumple</span>
                                    @else
                                        <span class="badge bg-warning-subtle text-warning border border-warning-subtle px-2 py-1"><i class="bi bi-hourglass-split me-1"></i> Pendiente</span>
                                    @endif
                                </td>
                                <td class="small">
                                    <div class="text-dark">{{ $req->observacion ?? 'Sin observaciones registradas' }}</div>
                                    @if($req->fecha_verificacion)
                                        <div class="text-secondary" style="font-size: 0.75rem;">
                                            <i class="bi bi-calendar-check me-1"></i>Verificado: {{ $req->fecha_verificacion->format('d/m/Y') }}
                                        </div>
                                    @endif
                                </td>
                                @if(auth()->user()->isCoordinador() || auth()->user()->isAdmin())
                                    <td class="text-end pe-3">
                                        <button type="button" class="btn btn-sm btn-outline-primary rounded-pill px-3" data-bs-toggle="modal" data-bs-target="#modalReq{{ $req->id }}">
                                            <i class="bi bi-pencil-square me-1"></i> Verificar
                                        </button>
                                    </td>
                                @endif
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ (auth()->user()->isCoordinador() || auth()->user()->isAdmin()) ? '5' : '4' }}" class="text-center py-5 text-muted">
                                    <i class="bi bi-folder-x fs-2 d-block mb-2 opacity-50"></i>
                                    No hay requisitos académicos configurados para la carrera de este estudiante.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Tercera Sección: Historial de Solicitudes y Prácticas (ANCHO COMPLETO) -->
<div class="row g-4 mb-4">
    <div class="col-12">
        <div class="card border-0 shadow-sm rounded-4 p-4">
            <div class="d-flex justify-content-between align-items-center mb-3 pb-2 border-bottom">
                <div class="d-flex align-items-center gap-2">
                    <div class="rounded-circle bg-secondary-subtle text-secondary p-2 d-flex align-items-center justify-content-center" style="width: 38px; height: 38px;">
                        <i class="bi bi-journal-text fs-5"></i>
                    </div>
                    <div>
                        <h5 class="fw-bold mb-0 text-navy" style="color: var(--upds-navy);">
                            Historial de Solicitudes y Prácticas
                        </h5>
                        <small class="text-muted">Registro cronológico de postulaciones institucionales presentadas por el estudiante</small>
                    </div>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr class="small text-muted">
                            <th class="ps-3" style="min-width: 130px;">N° Trámite</th>
                            <th style="min-width: 220px;">Organización Receptora</th>
                            <th style="min-width: 140px;">Fecha de Solicitud</th>
                            <th style="min-width: 140px;">Estado</th>
                            <th class="text-end pe-3" style="min-width: 140px;">Acción</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($estudiante->solicitudes as $sol)
                            <tr>
                                <td class="ps-3">
                                    <strong class="text-primary fs-6">S-00{{ $sol->id }}</strong>
                                </td>
                                <td>
                                    <div class="fw-semibold text-dark">{{ $sol->organizacion->nombre }}</div>
                                    <small class="text-muted">{{ $sol->organizacion->rubro }}</small>
                                </td>
                                <td class="small text-muted">
                                    {{ $sol->fecha_solicitud ? $sol->fecha_solicitud->format('d/m/Y H:i') : '—' }}
                                </td>
                                <td>
                                    <span class="badge-{{ $sol->estado }} px-2 py-1">
                                        {{ ucfirst(str_replace('_', ' ', $sol->estado)) }}
                                    </span>
                                </td>
                                <td class="text-end pe-3">
                                    <a href="{{ route('solicitudes.show', $sol->id) }}" class="btn btn-sm btn-outline-primary rounded-pill px-3">
                                        Ver expediente <i class="bi bi-arrow-right ms-1"></i>
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center py-5 text-muted">
                                    <i class="bi bi-inbox fs-2 d-block mb-2 opacity-50"></i>
                                    Este estudiante no ha presentado solicitudes de práctica profesional todavía.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modales para Verificación de Requisitos Académicos -->
@foreach($estudiante->requisitos as $req)
    <div class="modal fade" id="modalReq{{ $req->id }}" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow rounded-4">
                <div class="modal-header text-white" style="background-color: var(--upds-navy);">
                    <h6 class="modal-title fw-bold"><i class="bi bi-card-checklist me-2"></i>Verificar Requisito Académico</h6>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form action="{{ route('estudiantes.requisitos.verificar', [$estudiante->id, $req->id]) }}" method="POST">
                    @csrf
                    <div class="modal-body">
                        <div class="p-3 bg-light rounded-3 mb-3 small border">
                            <div class="fw-bold text-dark fs-6">{{ $req->requisito->nombre }}</div>
                            <div class="text-muted mt-1">{{ $req->requisito->descripcion ?? 'Requisito institucional para habilitación de prácticas profesionales.' }}</div>
                            <div class="mt-2">
                                Prioridad: 
                                @if($req->requisito->obligatorio)
                                    <span class="badge bg-danger-subtle text-danger border">Obligatorio</span>
                                @else
                                    <span class="badge bg-secondary-subtle text-secondary">Opcional</span>
                                @endif
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Dictamen / Estado de Cumplimiento *</label>
                            <select name="estado" class="form-select" required>
                                <option value="cumple" {{ $req->estado === 'cumple' ? 'selected' : '' }}>Cumple (Habilitado)</option>
                                <option value="pendiente" {{ $req->estado === 'pendiente' ? 'selected' : '' }}>Pendiente de verificación</option>
                                <option value="no_cumple" {{ $req->estado === 'no_cumple' ? 'selected' : '' }}>No cumple (Inhabilitado)</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Observación / Evidencia Documental</label>
                            <textarea name="observacion" class="form-control" rows="3" placeholder="Detalle de verificación, código de certificado, materias aprobadas, etc.">{{ $req->observacion }}</textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-sm btn-light rounded-pill px-3" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-sm btn-upds rounded-pill px-3 fw-semibold">Guardar Verificación</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endforeach
@endsection
