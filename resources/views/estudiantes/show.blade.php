@extends('layouts.app')

@section('title', 'Ficha del Estudiante - UPDSonline')

@section('content')
<div class="mb-4">
    <a href="{{ route('estudiantes.index') }}" class="text-decoration-none text-muted small">
        <i class="bi bi-arrow-left"></i> Volver al listado de estudiantes
    </a>
    <div class="d-flex justify-content-between align-items-center mt-2">
        <h4 class="fw-bold text-navy mb-0" style="color: var(--upds-navy);">
            Ficha Académica: {{ $estudiante->nombre_completo }}
        </h4>
        <span class="badge-{{ $estudiante->estado }} fs-6">{{ ucfirst($estudiante->estado) }}</span>
    </div>
</div>

<div class="row g-4">
    <!-- Columna Izquierda: Datos Personales y Académicos -->
    <div class="col-12 col-lg-4">
        <div class="card border-0 shadow-sm rounded-4 p-4">
            <div class="text-center mb-3">
                <div class="user-avatar mx-auto mb-2" style="width: 72px; height: 72px; font-size: 1.8rem; background: var(--upds-primary);">
                    {{ strtoupper(substr($estudiante->nombres, 0, 1)) }}
                </div>
                <h5 class="fw-bold mb-0">{{ $estudiante->nombre_completo }}</h5>
                <span class="badge bg-light text-dark border mt-1">RU: {{ $estudiante->ru }}</span>
            </div>

            <hr>

            <div class="small">
                <div class="mb-2 text-muted">Carrera:</div>
                <div class="fw-bold mb-3">{{ $estudiante->carrera->nombre }}</div>

                <div class="mb-2 text-muted">Semestre Actual:</div>
                <div class="fw-bold mb-3">{{ $estudiante->semestre }}° Semestre</div>

                <div class="mb-2 text-muted">Cédula de Identidad:</div>
                <div class="fw-semibold mb-3">{{ $estudiante->ci ?? 'No registrado' }}</div>

                <div class="mb-2 text-muted">Cuenta de Acceso al Sistema:</div>
                <div class="mb-3">
                    @if($estudiante->user)
                        <span class="badge bg-success-subtle text-success border border-success-subtle">
                            <i class="bi bi-check-circle me-1"></i> Vinculada ({{ $estudiante->user->email }})
                        </span>
                    @else
                        <span class="badge bg-warning-subtle text-warning border border-warning-subtle">
                            <i class="bi bi-clock-history me-1"></i> Pendiente de vinculación (Admin)
                        </span>
                    @endif
                </div>

                <div class="mb-2 text-muted">Teléfono / Celular:</div>
                <div class="fw-semibold mb-3">{{ $estudiante->telefono ?? 'No registrado' }}</div>
            </div>

            <div class="mt-3">
                <a href="{{ route('estudiantes.edit', $estudiante->id) }}" class="btn btn-outline-primary w-100 btn-sm">
                    <i class="bi bi-pencil me-1"></i> Editar datos del estudiante
                </a>
            </div>
        </div>
    </div>

    <!-- Columna Derecha: Requisitos Académicos e Historial de Solicitudes -->
    <div class="col-12 col-lg-8">
        <!-- Requisitos Académicos para Prácticas -->
        <div class="card border-0 shadow-sm rounded-4 p-4 mb-4">
            <h5 class="fw-bold text-navy mb-3" style="color: var(--upds-navy);">
                <i class="bi bi-card-checklist text-primary me-2"></i>Cumplimiento de Requisitos Académicos
            </h5>
            <div class="table-responsive">
                <table class="table align-middle">
                    <thead class="table-light">
                        <tr class="small text-muted">
                            <th>Requisito</th>
                            <th>Obligatorio</th>
                            <th>Estado</th>
                            <th>Observación / Verificación</th>
                            @if(auth()->user()->isCoordinador() || auth()->user()->isAdmin())
                                <th class="text-end">Acción</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($estudiante->requisitos as $req)
                            <tr>
                                <td class="fw-semibold">{{ $req->requisito->nombre }}</td>
                                <td>
                                    @if($req->requisito->obligatorio)
                                        <span class="badge bg-danger-subtle text-danger border border-danger-subtle">Obligatorio</span>
                                    @else
                                        <span class="badge bg-secondary-subtle text-secondary">Opcional</span>
                                    @endif
                                </td>
                                <td>
                                    @if($req->estado === 'cumple')
                                        <span class="badge-activo"><i class="bi bi-check-circle me-1"></i> Cumple</span>
                                    @elseif($req->estado === 'no_cumple')
                                        <span class="badge-observada"><i class="bi bi-x-circle me-1"></i> No cumple</span>
                                    @else
                                        <span class="badge bg-warning-subtle text-warning border border-warning-subtle"><i class="bi bi-hourglass-split me-1"></i> Pendiente</span>
                                    @endif
                                </td>
                                <td class="small text-muted">
                                    {{ $req->observacion ?? 'Sin observaciones' }}
                                    @if($req->fecha_verificacion)
                                        <div style="font-size: 0.72rem;" class="text-secondary">Verificado: {{ $req->fecha_verificacion->format('d/m/Y') }}</div>
                                    @endif
                                </td>
                                @if(auth()->user()->isCoordinador() || auth()->user()->isAdmin())
                                    <td class="text-end">
                                        <button type="button" class="btn btn-xs btn-outline-primary py-1 px-2" data-bs-toggle="modal" data-bs-target="#modalReq{{ $req->id }}">
                                            <i class="bi bi-pencil-square me-1"></i> Verificar
                                        </button>
                                    </td>
                                @endif
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted py-3">No hay requisitos configurados para esta carrera.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Historial de Solicitudes y Prácticas -->
        <div class="card border-0 shadow-sm rounded-4 p-4">
            <h5 class="fw-bold text-navy mb-3" style="color: var(--upds-navy);">
                <i class="bi bi-journal-text text-primary me-2"></i>Historial de Solicitudes y Prácticas
            </h5>
            <div class="table-responsive">
                <table class="table align-middle">
                    <thead class="table-light">
                        <tr class="small text-muted">
                            <th>N° Trámite</th>
                            <th>Organización</th>
                            <th>Fecha</th>
                            <th>Estado Solicitud</th>
                            <th class="text-end">Acción</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($estudiante->solicitudes as $sol)
                            <tr>
                                <td><strong>S-00{{ $sol->id }}</strong></td>
                                <td class="fw-semibold">{{ $sol->organizacion->nombre }}</td>
                                <td class="small text-muted">{{ $sol->fecha_solicitud->format('d/m/Y') }}</td>
                                <td><span class="badge-{{ $sol->estado }}">{{ ucfirst(str_replace('_', ' ', $sol->estado)) }}</span></td>
                                <td class="text-end">
                                    <a href="{{ route('solicitudes.show', $sol->id) }}" class="btn btn-sm btn-outline-primary">
                                        Ver expediente <i class="bi bi-arrow-right"></i>
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted py-4">Este estudiante no ha presentado solicitudes de práctica aún.</td>
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
                    <h6 class="modal-title fw-bold">Verificar Requisito Académico</h6>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form action="{{ route('estudiantes.requisitos.verificar', [$estudiante->id, $req->id]) }}" method="POST">
                    @csrf
                    <div class="modal-body">
                        <div class="p-3 bg-light rounded-3 mb-3 small">
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
                        <button type="button" class="btn btn-sm btn-light" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-sm btn-upds">Guardar Verificación</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endforeach
@endsection

