@extends('layouts.app')

@section('title', 'Roles y Permisos del Sistema - Admin')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-1" style="color:var(--upds-navy);">
            <i class="bi bi-shield-check me-2 text-success"></i>Roles y Matriz de Permisos
        </h4>
        <small class="text-muted">Estructura institucional de acceso, autorización y responsabilidades por rol</small>
    </div>
    <a href="{{ route('admin.usuarios.index') }}" class="btn btn-outline-primary btn-sm">
        <i class="bi bi-people me-1"></i> Ver Usuarios por Rol
    </a>
</div>

<!-- Tarjetas de Roles -->
<div class="row g-4 mb-4">
    @foreach($roles as $r)
        <div class="col-12 col-md-6 col-lg-4">
            <div class="card border-0 shadow-sm rounded-4 p-4 h-100">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <span class="badge bg-primary-subtle text-primary border px-3 py-2 fs-6">
                        {{ $r->nombre }}
                    </span>
                    <span class="badge bg-secondary rounded-pill">
                        {{ $r->users_count }} cuentas
                    </span>
                </div>
                <p class="text-muted small mb-3">
                    {{ $r->descripcion ?: 'Rol institucional del sistema UPDSonline.' }}
                </p>
                <div class="mt-auto border-top pt-3 small text-secondary">
                    <div>Código interno: <code>{{ $r->codigo }}</code></div>
                    <div>Nivel de acceso: 
                        @if($r->codigo === 'administrador')
                            <strong class="text-danger">Gestión de Cuentas y Seguridad</strong>
                        @elseif($r->codigo === 'coordinador')
                            <strong class="text-warning text-dark">Gestión Académica Integral</strong>
                        @elseif($r->codigo === 'tutor')
                            <strong class="text-info text-dark">Seguimiento y Validación Pedagógica</strong>
                        @else
                            <strong class="text-primary">Consultas y Trámites del Estudiante</strong>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    @endforeach
</div>

<!-- Matriz Institucional de Responsabilidades (Section 2 & 8) -->
<div class="card border-0 shadow-sm rounded-4 p-4">
    <h5 class="fw-bold mb-3 text-navy" style="color: var(--upds-navy);">
        <i class="bi bi-ui-checks-grid text-primary me-2"></i>Matriz de Separación de Responsabilidades Institucionales
    </h5>
    <div class="table-responsive">
        <table class="table table-bordered align-middle small mb-0">
            <thead class="table-light">
                <tr class="text-center">
                    <th class="text-start">Funcionalidad / Operación</th>
                    <th>Estudiante</th>
                    <th>Docente Tutor</th>
                    <th>Coordinador Académico</th>
                    <th>Administrador</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td class="fw-semibold">Administración de usuarios y contraseñas</td>
                    <td class="text-center text-danger"><i class="bi bi-x-circle-fill"></i></td>
                    <td class="text-center text-danger"><i class="bi bi-x-circle-fill"></i></td>
                    <td class="text-center text-danger"><i class="bi bi-x-circle-fill"></i></td>
                    <td class="text-center text-success"><i class="bi bi-check-circle-fill"></i> Exclusivo</td>
                </tr>
                <tr>
                    <td class="fw-semibold">Gestión de períodos académicos y catálogos</td>
                    <td class="text-center text-danger"><i class="bi bi-x-circle-fill"></i></td>
                    <td class="text-center text-danger"><i class="bi bi-x-circle-fill"></i></td>
                    <td class="text-center text-muted">Consulta</td>
                    <td class="text-center text-success"><i class="bi bi-check-circle-fill"></i> Exclusivo</td>
                </tr>
                <tr>
                    <td class="fw-semibold">Creación y verificación de requisitos de fichas académicas</td>
                    <td class="text-center text-danger"><i class="bi bi-x-circle-fill"></i></td>
                    <td class="text-center text-danger"><i class="bi bi-x-circle-fill"></i></td>
                    <td class="text-center text-success"><i class="bi bi-check-circle-fill"></i> Exclusivo</td>
                    <td class="text-center text-danger"><i class="bi bi-x-circle-fill"></i></td>
                </tr>
                <tr>
                    <td class="fw-semibold">Aprobación / Observación de Solicitudes</td>
                    <td class="text-center text-danger"><i class="bi bi-x-circle-fill"></i></td>
                    <td class="text-center text-danger"><i class="bi bi-x-circle-fill"></i></td>
                    <td class="text-center text-success"><i class="bi bi-check-circle-fill"></i> Exclusivo</td>
                    <td class="text-center text-danger"><i class="bi bi-x-circle-fill"></i> Bloqueado</td>
                </tr>
                <tr>
                    <td class="fw-semibold">Asignación de Docente Tutor a Prácticas</td>
                    <td class="text-center text-danger"><i class="bi bi-x-circle-fill"></i></td>
                    <td class="text-center text-danger"><i class="bi bi-x-circle-fill"></i></td>
                    <td class="text-center text-success"><i class="bi bi-check-circle-fill"></i> Exclusivo</td>
                    <td class="text-center text-danger"><i class="bi bi-x-circle-fill"></i> Bloqueado</td>
                </tr>
                <tr>
                    <td class="fw-semibold">Validación o Rechazo de Jornadas de Horas (Regla 29)</td>
                    <td class="text-center text-danger"><i class="bi bi-x-circle-fill"></i></td>
                    <td class="text-center text-success"><i class="bi bi-check-circle-fill"></i> Exclusivo</td>
                    <td class="text-center text-danger"><i class="bi bi-x-circle-fill"></i> Bloqueado</td>
                    <td class="text-center text-danger"><i class="bi bi-x-circle-fill"></i> Bloqueado</td>
                </tr>
                <tr>
                    <td class="fw-semibold">Registro de Observaciones y Evaluaciones Parciales / Finales</td>
                    <td class="text-center text-danger"><i class="bi bi-x-circle-fill"></i></td>
                    <td class="text-center text-success"><i class="bi bi-check-circle-fill"></i> Exclusivo</td>
                    <td class="text-center text-danger"><i class="bi bi-x-circle-fill"></i> Bloqueado</td>
                    <td class="text-center text-danger"><i class="bi bi-x-circle-fill"></i> Bloqueado</td>
                </tr>
                <tr>
                    <td class="fw-semibold">Valoración Empresarial y Cierre Formal de Prácticas</td>
                    <td class="text-center text-danger"><i class="bi bi-x-circle-fill"></i></td>
                    <td class="text-center text-danger"><i class="bi bi-x-circle-fill"></i></td>
                    <td class="text-center text-success"><i class="bi bi-check-circle-fill"></i> Exclusivo</td>
                    <td class="text-center text-danger"><i class="bi bi-x-circle-fill"></i> Bloqueado</td>
                </tr>
            </tbody>
        </table>
    </div>
</div>
@endsection

