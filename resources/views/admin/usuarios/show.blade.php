@extends('layouts.app')

@section('title', 'Detalles de Usuario - Admin')

@section('content')
<div class="mb-4">
    <a href="{{ route('admin.usuarios.index') }}" class="btn btn-sm btn-outline-secondary mb-3">
        <i class="bi bi-arrow-left me-1"></i> Volver a Usuarios
    </a>
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h4 class="fw-bold mb-1" style="color:var(--upds-navy);">
                <i class="bi bi-person-badge-fill me-2 text-primary"></i>Expediente de Cuenta: {{ $usuario->name }}
            </h4>
            <small class="text-muted">Consulta integral de credenciales, roles, vínculos académicos y auditoría</small>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.usuarios.edit', $usuario) }}" class="btn btn-primary btn-sm">
                <i class="bi bi-pencil-square me-1"></i> Editar Cuenta
            </a>
        </div>
    </div>
</div>

<div class="row g-4">
    <!-- Columna Izquierda: Información de Cuenta y Vínculo -->
    <div class="col-lg-5">
        <div class="card border-0 shadow-sm rounded-4 p-4 mb-4">
            <div class="d-flex align-items-center gap-3 mb-4">
                <div style="width: 56px; height: 56px; border-radius: 50%; background: var(--upds-primary); color: white; display: flex; align-items: center; justify-content: center; font-size: 1.6rem; font-weight: 700;">
                    {{ strtoupper(substr($usuario->name, 0, 1)) }}
                </div>
                <div>
                    <h5 class="fw-bold mb-0 text-navy">{{ $usuario->name }}</h5>
                    <div class="text-muted small"><code>{{ $usuario->email }}</code></div>
                    <div class="mt-1">
                        <span class="badge bg-primary rounded-pill">{{ $usuario->rol->nombre ?? 'Sin Rol' }}</span>
                        @if($usuario->activo)
                            <span class="badge bg-success rounded-pill ms-1">Cuenta Activa</span>
                        @else
                            <span class="badge bg-danger rounded-pill ms-1">Cuenta Inactiva</span>
                        @endif
                    </div>
                </div>
            </div>

            <div class="border-top pt-3 small">
                <div class="row g-2">
                    <div class="col-5 text-muted">ID de Usuario:</div>
                    <div class="col-7 fw-bold">#{{ $usuario->id }}</div>

                    <div class="col-5 text-muted">Fecha Registro:</div>
                    <div class="col-7">{{ $usuario->created_at ? $usuario->created_at->format('d/m/Y H:i') : '—' }}</div>

                    <div class="col-5 text-muted">Última Actualización:</div>
                    <div class="col-7">{{ $usuario->updated_at ? $usuario->updated_at->format('d/m/Y H:i') : '—' }}</div>
                </div>
            </div>
        </div>

        <!-- Ficha Académica Asociada -->
        <div class="card border-0 shadow-sm rounded-4 p-4">
            <h6 class="fw-bold mb-3 text-navy" style="color: var(--upds-navy);">
                <i class="bi bi-mortarboard text-primary me-2"></i>Vinculación Académica
            </h6>
            @if($usuario->rol_id == 4)
                @if($usuario->estudiante)
                    <div class="p-3 bg-light rounded-3 small">
                        <div class="row g-2">
                            <div class="col-5 text-muted">Estudiante:</div>
                            <div class="col-7 fw-bold">{{ $usuario->estudiante->nombre_completo }}</div>

                            <div class="col-5 text-muted">Código RU:</div>
                            <div class="col-7"><code>{{ $usuario->estudiante->ru }}</code></div>

                            <div class="col-5 text-muted">Carrera:</div>
                            <div class="col-7">{{ $usuario->estudiante->carrera->nombre ?? '—' }}</div>

                            <div class="col-5 text-muted">Semestre:</div>
                            <div class="col-7">{{ $usuario->estudiante->semestre }}° Semestre</div>
                        </div>
                    </div>
                @else
                    <div class="alert alert-warning py-2 small mb-0">
                        <i class="bi bi-exclamation-triangle me-1"></i>Cuenta estudiantil sin ficha académica vinculada. La coordinación debe crear su ficha con RU institucional.
                    </div>
                @endif
            @elseif($usuario->rol_id == 3)
                @if($usuario->tutor)
                    <div class="p-3 bg-light rounded-3 small">
                        <div class="row g-2">
                            <div class="col-5 text-muted">Docente Tutor:</div>
                            <div class="col-7 fw-bold">{{ $usuario->tutor->nombre_completo }}</div>

                            <div class="col-5 text-muted">Especialidad:</div>
                            <div class="col-7">{{ $usuario->tutor->especialidad }}</div>

                            <div class="col-5 text-muted">Prácticas:</div>
                            <div class="col-7"><span class="badge bg-primary">{{ $usuario->tutor->practicas->count() }} asignadas</span></div>
                        </div>
                    </div>
                @else
                    <div class="text-muted small">Sin ficha docente creada.</div>
                @endif
            @elseif($usuario->rol_id == 2)
                @if($usuario->coordinador)
                    <div class="p-3 bg-light rounded-3 small">
                        <div class="row g-2">
                            <div class="col-5 text-muted">Coordinador:</div>
                            <div class="col-7 fw-bold">{{ $usuario->coordinador->nombre_completo }}</div>

                            <div class="col-5 text-muted">Teléfono:</div>
                            <div class="col-7">{{ $usuario->coordinador->telefono ?: 'No registrado' }}</div>
                        </div>
                    </div>
                @endif
            @else
                <div class="text-muted small">Cuenta de administración de plataforma.</div>
            @endif
        </div>
    </div>

    <!-- Columna Derecha: Auditoría de Acciones sobre el Usuario -->
    <div class="col-lg-7">
        <div class="card border-0 shadow-sm rounded-4 p-4">
            <h6 class="fw-bold mb-3 text-navy" style="color: var(--upds-navy);">
                <i class="bi bi-clock-history text-primary me-2"></i>Historial de Eventos de Seguridad y Auditoría
            </h6>
            <div class="table-responsive">
                <table class="table table-sm align-middle small mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Fecha</th>
                            <th>Acción</th>
                            <th>Administrador</th>
                            <th>Motivo / Detalles</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($historialAuditoria as $ha)
                            <tr>
                                <td class="text-muted">{{ $ha->created_at->format('d/m/Y H:i') }}</td>
                                <td><span class="badge bg-light text-dark border">{{ $ha->accion }}</span></td>
                                <td>{{ $ha->admin->name ?? 'Admin' }}</td>
                                <td>{{ $ha->motivo ?: 'Sin detalles' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center py-4 text-muted">No existen eventos registrados para este usuario.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

