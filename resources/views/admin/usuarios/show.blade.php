@extends('layouts.app')

@section('title', 'Detalles de Usuario - Admin')

@section('content')
<div class="mb-4">
    <div class="d-flex align-items-center gap-2 mb-2">
        <a href="{{ route('admin.usuarios.index') }}" class="btn btn-sm btn-outline-secondary rounded-pill px-3">
            <i class="bi bi-arrow-left me-1"></i> Volver a Usuarios
        </a>
        <span class="badge bg-light text-secondary border rounded-pill px-3 py-1">
            <i class="bi bi-shield-lock me-1 text-primary"></i> Módulo de Administración
        </span>
    </div>

    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 bg-white p-4 rounded-4 shadow-sm border">
        <div>
            <div class="d-flex align-items-center gap-2 flex-wrap mb-1">
                <h4 class="fw-bold mb-0" style="color:var(--upds-navy);">
                    Expediente de Cuenta: {{ $usuario->name }}
                </h4>
                @if($usuario->activo)
                    <span class="badge bg-success rounded-pill px-3 py-1">Cuenta Activa</span>
                @else
                    <span class="badge bg-danger rounded-pill px-3 py-1">Cuenta Inactiva</span>
                @endif
            </div>
            <small class="text-muted">Consulta integral de credenciales, roles, vínculos académicos y auditoría institucional</small>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.usuarios.edit', $usuario) }}" class="btn btn-primary btn-sm rounded-pill px-3 fw-semibold">
                <i class="bi bi-pencil-square me-1"></i> Editar Cuenta
            </a>
        </div>
    </div>
</div>

<!-- Primera Sección: Datos de Cuenta y Vinculación Académica (2 Columnas Equilibradas) -->
<div class="row g-4 mb-4">
    <!-- Tarjeta 1: Información de Cuenta y Credenciales -->
    <div class="col-12 col-lg-6">
        <div class="card border-0 shadow-sm rounded-4 p-4 h-100">
            <div class="d-flex align-items-center gap-3 mb-3 pb-3 border-bottom">
                <div style="width: 54px; height: 54px; border-radius: 50%; background: var(--upds-primary); color: white; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; font-weight: 700;">
                    {{ strtoupper(substr($usuario->name, 0, 1)) }}
                </div>
                <div>
                    <h5 class="fw-bold mb-0 text-navy">{{ $usuario->name }}</h5>
                    <div class="text-muted small"><code>{{ $usuario->email }}</code></div>
                    <div class="mt-1">
                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill">{{ $usuario->rol->nombre ?? 'Sin Rol' }}</span>
                    </div>
                </div>
            </div>

            <div class="small">
                <div class="row g-3">
                    <div class="col-6">
                        <span class="text-muted d-block">ID de Usuario:</span>
                        <strong class="text-dark">#{{ $usuario->id }}</strong>
                    </div>
                    <div class="col-6">
                        <span class="text-muted d-block">Rol en Sistema:</span>
                        <strong class="text-dark">{{ $usuario->rol->nombre ?? 'Sin rol' }}</strong>
                    </div>
                    <div class="col-6">
                        <span class="text-muted d-block">Fecha de Registro:</span>
                        <span class="text-dark">{{ $usuario->created_at ? $usuario->created_at->format('d/m/Y H:i') : '—' }}</span>
                    </div>
                    <div class="col-6">
                        <span class="text-muted d-block">Última Actualización:</span>
                        <span class="text-dark">{{ $usuario->updated_at ? $usuario->updated_at->format('d/m/Y H:i') : '—' }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Tarjeta 2: Vinculación Académica Institucional -->
    <div class="col-12 col-lg-6">
        <div class="card border-0 shadow-sm rounded-4 p-4 h-100">
            <div class="d-flex align-items-center gap-2 mb-3 pb-3 border-bottom">
                <div class="rounded-circle bg-primary-subtle text-primary p-2 d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                    <i class="bi bi-mortarboard-fill fs-5"></i>
                </div>
                <div>
                    <h6 class="fw-bold mb-0 text-navy" style="color: var(--upds-navy);">Vinculación Académica</h6>
                    <small class="text-muted">Registro en el padrón institucional UPDS</small>
                </div>
            </div>

            @if($usuario->rol_id == 4)
                @if($usuario->estudiante)
                    <div class="p-3 bg-light rounded-3 small">
                        <div class="row g-2">
                            <div class="col-5 text-muted">Estudiante:</div>
                            <div class="col-7 fw-bold text-dark">{{ $usuario->estudiante->nombre_completo }}</div>

                            <div class="col-5 text-muted">Código RU:</div>
                            <div class="col-7"><code class="fw-bold">{{ $usuario->estudiante->ru }}</code></div>

                            <div class="col-5 text-muted">Carrera:</div>
                            <div class="col-7 fw-semibold">{{ $usuario->estudiante->carrera->nombre ?? '—' }}</div>

                            <div class="col-5 text-muted">Semestre:</div>
                            <div class="col-7">{{ $usuario->estudiante->semestre }}° Semestre</div>
                        </div>
                    </div>
                    <div class="mt-3 text-end">
                        <a href="{{ route('estudiantes.show', $usuario->estudiante->id) }}" class="btn btn-sm btn-outline-primary rounded-pill" target="_blank">
                            <i class="bi bi-card-checklist me-1"></i> Ver Ficha Estudiantil
                        </a>
                    </div>
                @else
                    <div class="alert alert-warning py-3 small mb-0 rounded-3">
                        <i class="bi bi-exclamation-triangle me-1"></i> Cuenta estudiantil sin ficha académica vinculada. La coordinación debe crear su ficha con RU institucional.
                    </div>
                @endif
            @elseif($usuario->rol_id == 3)
                @if($usuario->tutor)
                    <div class="p-3 bg-light rounded-3 small">
                        <div class="row g-2">
                            <div class="col-5 text-muted">Docente Tutor:</div>
                            <div class="col-7 fw-bold text-dark">{{ $usuario->tutor->nombre_completo }}</div>

                            <div class="col-5 text-muted">Especialidad:</div>
                            <div class="col-7">{{ $usuario->tutor->especialidad }}</div>

                            <div class="col-5 text-muted">Prácticas:</div>
                            <div class="col-7"><span class="badge bg-primary rounded-pill px-2">{{ $usuario->tutor->practicas->count() }} asignadas</span></div>
                        </div>
                    </div>
                @else
                    <div class="text-muted small p-3 bg-light rounded-3">Sin ficha docente creada.</div>
                @endif
            @elseif($usuario->rol_id == 2)
                @if($usuario->coordinador)
                    <div class="p-3 bg-light rounded-3 small">
                        <div class="row g-2">
                            <div class="col-5 text-muted">Coordinador:</div>
                            <div class="col-7 fw-bold text-dark">{{ $usuario->coordinador->nombre_completo }}</div>

                            <div class="col-5 text-muted">Teléfono:</div>
                            <div class="col-7">{{ $usuario->coordinador->telefono ?: 'No registrado' }}</div>
                        </div>
                    </div>
                @endif
            @else
                <div class="p-3 bg-light rounded-3 text-muted small">
                    <i class="bi bi-shield-check text-success me-1"></i> Cuenta de nivel administrador de plataforma sin requerimiento de expediente académico.
                </div>
            @endif
        </div>
    </div>
</div>

<!-- Segunda Sección: Historial de Eventos de Seguridad y Auditoría (ANCHO COMPLETO) -->
<div class="row g-4 mb-4">
    <div class="col-12">
        <div class="card border-0 shadow-sm rounded-4 p-4">
            <div class="d-flex align-items-center gap-2 mb-3 pb-3 border-bottom">
                <div class="rounded-circle bg-secondary-subtle text-secondary p-2 d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                    <i class="bi bi-clock-history fs-5"></i>
                </div>
                <div>
                    <h5 class="fw-bold mb-0 text-navy" style="color: var(--upds-navy);">
                        Historial de Eventos de Seguridad y Auditoría
                    </h5>
                    <small class="text-muted">Registro institucional de cambios de clave, actualización de perfil y acciones administrativas</small>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-hover align-middle small mb-0">
                    <thead class="table-light">
                        <tr class="text-muted">
                            <th class="ps-3" style="min-width: 150px;">Fecha y Hora</th>
                            <th style="min-width: 140px;">Acción Realizada</th>
                            <th style="min-width: 160px;">Administrador Responsable</th>
                            <th style="min-width: 250px;">Motivo / Detalles de la Operación</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($historialAuditoria as $ha)
                            <tr>
                                <td class="ps-3 text-dark fw-medium">
                                    {{ $ha->created_at ? $ha->created_at->format('d/m/Y H:i') : '—' }}
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark border px-2 py-1">
                                        {{ $ha->accion }}
                                    </span>
                                </td>
                                <td>
                                    <span class="fw-semibold text-dark">{{ $ha->admin->name ?? 'Sistema / Admin' }}</span>
                                </td>
                                <td>
                                    <span class="text-secondary">{{ $ha->motivo ?: 'Sin detalles complementarios' }}</span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center py-5 text-muted">
                                    <i class="bi bi-shield-check fs-2 d-block mb-2 opacity-50"></i>
                                    No existen eventos de auditoría registrados para esta cuenta de usuario.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
