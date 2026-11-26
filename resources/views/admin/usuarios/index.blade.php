@extends('layouts.app')

@section('title', 'Gestión de Usuarios - Admin')

@section('content')
@if(session('temp_password_info'))
    <div class="alert alert-warning border-0 shadow-sm rounded-4 p-4 mb-4">
        <div class="d-flex align-items-center gap-3">
            <div style="width: 48px; height: 48px; border-radius: 50%; background: #fef08a; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; color: #854d0e;">
                <i class="bi bi-key-fill"></i>
            </div>
            <div>
                <h6 class="fw-bold text-dark mb-1">Credenciales Temporales Generadas con Éxito</h6>
                <div class="small text-muted mb-2">
                    Usuario: <strong>{{ session('temp_password_info')['usuario'] }}</strong> ({{ session('temp_password_info')['email'] }})
                </div>
                <div class="d-flex align-items-center gap-2">
                    <span class="small text-muted">Contraseña provisoria:</span>
                    <code class="fs-6 px-3 py-1 bg-white border rounded fw-bold text-danger">{{ session('temp_password_info')['temp_password'] }}</code>
                    <small class="text-secondary fst-italic ms-2">&bull; Entrega esta clave al usuario por canal seguro para su primer inicio de sesión.</small>
                </div>
            </div>
        </div>
    </div>
@endif

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-1" style="color:var(--upds-navy);">
            <i class="bi bi-people-fill me-2 text-warning"></i>Gestión de Usuarios
        </h4>
        <small class="text-muted">Administra las cuentas de acceso y credenciales del sistema universitario UPDS</small>
    </div>
    <a href="{{ route('admin.usuarios.create') }}" class="btn btn-primary fw-semibold">
        <i class="bi bi-person-plus-fill me-1"></i> Registrar Nueva Cuenta
    </a>
</div>

<!-- Filtros -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body p-3">
        <form method="GET" action="{{ route('admin.usuarios.index') }}" class="row g-2 align-items-end">
            <div class="col-md-3">
                <label class="form-label small fw-semibold text-muted mb-1">Buscar por nombre o correo</label>
                <input type="text" name="buscar" class="form-control form-control-sm" placeholder="Ej: Carlos Meneses o @upds.edu.bo..." value="{{ $search }}" data-auto-search autocomplete="off">
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-semibold text-muted mb-1">Rol institucional</label>
                <select name="rol_id" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">— Todos los roles —</option>
                    @foreach($roles as $rol)
                        <option value="{{ $rol->id }}" {{ $rolFilter == $rol->id ? 'selected' : '' }}>{{ $rol->nombre }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small fw-semibold text-muted mb-1">Estado de cuenta</label>
                <select name="estado" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">— Todos —</option>
                    <option value="1" {{ isset($estadoFilter) && $estadoFilter === '1' ? 'selected' : '' }}>Activos</option>
                    <option value="0" {{ isset($estadoFilter) && $estadoFilter === '0' ? 'selected' : '' }}>Inactivos</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small fw-semibold text-muted mb-1">Por página</label>
                <select name="per_page" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="10" {{ request('per_page', 10) == 10 ? 'selected' : '' }}>10 usuarios</option>
                    <option value="25" {{ request('per_page') == 25 ? 'selected' : '' }}>25 usuarios</option>
                    <option value="50" {{ request('per_page') == 50 ? 'selected' : '' }}>50 usuarios</option>
                </select>
            </div>
            <div class="col-md-2 text-md-end">
                <a href="{{ route('admin.usuarios.index') }}" class="btn btn-outline-secondary btn-sm px-3 w-100">
                    <i class="bi bi-x-circle me-1"></i> Limpiar
                </a>
            </div>
        </form>
    </div>
</div>

<!-- Tabla -->
<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead style="background:#f8fafc;border-bottom:2px solid #e2e8f0;">
                    <tr>
                        <th class="ps-4 py-3 text-muted small fw-semibold">#</th>
                        <th class="py-3 text-muted small fw-semibold">USUARIO</th>
                        <th class="py-3 text-muted small fw-semibold">CORREO</th>
                        <th class="py-3 text-muted small fw-semibold">ROL</th>
                        <th class="py-3 text-muted small fw-semibold">ESTADO</th>
                        <th class="py-3 text-muted small fw-semibold">REGISTRO</th>
                        <th class="py-3 text-muted small fw-semibold text-end pe-4">ACCIONES</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($usuarios as $u)
                    <tr>
                        <td class="ps-4 text-muted small fw-semibold">
                            {{ $loop->iteration + ($usuarios->currentPage() - 1) * $usuarios->perPage() }}
                        </td>
                        <td>
                            <div class="d-flex align-items-center gap-3">
                                <div style="width:36px;height:36px;border-radius:50%;background:var(--upds-primary);display:flex;align-items:center;justify-content:center;color:#fff;font-weight:700;font-size:.85rem;flex-shrink:0;">
                                    {{ strtoupper(substr($u->name,0,1)) }}
                                </div>
                                <div>
                                    <span class="fw-semibold text-dark">{{ $u->name }}</span>
                                    @if($u->rol_id == 4 && $u->estudiante)
                                        <small class="d-block text-muted" style="font-size: 0.75rem;">RU: {{ $u->estudiante->ru }} &bull; {{ $u->estudiante->carrera->nombre ?? '' }}</small>
                                    @endif
                                </div>
                            </div>
                        </td>
                        <td class="text-muted small"><code>{{ $u->email }}</code></td>
                        <td>
                            @php
                                $badgeClass = match($u->rol->codigo ?? '') {
                                    'administrador' => 'bg-danger text-white',
                                    'coordinador'   => 'bg-warning text-dark',
                                    'tutor'         => 'bg-info text-dark',
                                    default         => 'bg-primary text-white',
                                };
                            @endphp
                            <span class="badge {{ $badgeClass }} rounded-pill">{{ $u->rol->nombre ?? '—' }}</span>
                        </td>
                        <td>
                            @if($u->activo)
                                <span class="badge text-bg-success rounded-pill">Activo</span>
                            @else
                                <span class="badge text-bg-secondary rounded-pill">Inactivo</span>
                            @endif
                        </td>
                        <td class="text-muted small">{{ $u->created_at ? $u->created_at->format('d/m/Y') : '—' }}</td>
                        <td class="text-end pe-4">
                            <div class="d-flex justify-content-end gap-1">
                                <a href="{{ route('admin.usuarios.show', $u) }}" class="btn btn-sm btn-outline-info p-1 px-2" title="Consultar expediente">
                                    <i class="bi bi-eye"></i>
                                </a>
                                <a href="{{ route('admin.usuarios.edit', $u) }}" class="btn btn-sm btn-outline-primary p-1 px-2" title="Editar información">
                                    <i class="bi bi-pencil-fill"></i>
                                </a>
                                <button type="button" class="btn btn-sm btn-outline-secondary p-1 px-2" data-bs-toggle="modal" data-bs-target="#modalResetPassword{{ $u->id }}" title="Restablecer acceso temporal">
                                    <i class="bi bi-key"></i>
                                </button>
                                <button type="button" class="btn btn-sm {{ $u->activo ? 'btn-outline-warning' : 'btn-outline-success' }} p-1 px-2" 
                                        data-bs-toggle="modal" data-bs-target="#modalToggle{{ $u->id }}"
                                        title="{{ $u->activo ? 'Desactivar cuenta' : 'Activar cuenta' }}">
                                    <i class="bi bi-{{ $u->activo ? 'toggle-on' : 'toggle-off' }}"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="text-center py-5 text-muted">
                            <i class="bi bi-people fs-1 d-block mb-2 opacity-25"></i>
                            No se encontraron usuarios con esos criterios de búsqueda.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($usuarios->hasPages() || $usuarios->total() > 0)
        <div class="px-4 py-3 border-top d-flex flex-column flex-md-row justify-content-between align-items-center gap-2">
            <span class="small text-muted">
                Mostrando <strong>{{ $usuarios->firstItem() ?? 0 }}</strong>–<strong>{{ $usuarios->lastItem() ?? 0 }}</strong> de <strong>{{ $usuarios->total() }}</strong> usuarios registrados
            </span>
            <div>
                {{ $usuarios->links('pagination::bootstrap-5') }}
            </div>
        </div>
        @endif
    </div>
</div>

<!-- Resumen Estadístico de Cuentas -->
<div class="row g-3 mt-2">
    @foreach($roles as $rol)
    @php $cnt = \App\Models\User::where('rol_id', $rol->id)->count(); @endphp
    <div class="col-md">
        <div class="card border-0 shadow-sm text-center p-3">
            <div class="fs-4 fw-bold text-primary">{{ $cnt }}</div>
            <div class="small text-muted">{{ $rol->nombre }}</div>
        </div>
    </div>
    @endforeach
</div>

<!-- Modales de Toggle y Restablecimiento para cada usuario -->
@foreach($usuarios as $u)
    <!-- Modal Confirmar Toggle Activo / Inactivo -->
    <div class="modal fade" id="modalToggle{{ $u->id }}" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow rounded-4">
                <div class="modal-header {{ $u->activo ? 'bg-warning text-dark' : 'bg-success text-white' }}">
                    <h6 class="modal-title fw-bold">
                        <i class="bi bi-{{ $u->activo ? 'exclamation-circle' : 'check-circle' }} me-2"></i>
                        {{ $u->activo ? 'Desactivar Cuenta de Usuario' : 'Activar Cuenta de Usuario' }}
                    </h6>
                    <button type="button" class="btn-close {{ $u->activo ? '' : 'btn-close-white' }}" data-bs-dismiss="modal"></button>
                </div>
                <form action="{{ route('admin.usuarios.toggle', $u) }}" method="POST">
                    @csrf
                    <div class="modal-body">
                        <p class="small text-muted mb-2">
                            Usuario: <strong>{{ $u->name }}</strong> ({{ $u->email }}) &bull; Rol: <span class="badge bg-light text-dark border">{{ $u->rol->nombre ?? 'Usuario' }}</span>
                        </p>
                        @if($u->activo)
                            <div class="alert alert-warning py-2 small mb-3">
                                Al desactivar la cuenta, el usuario perderá acceso inmediato a UPDSonline y sus sesiones activas serán invalidadas. El historial académico se conservará íntegramente.
                            </div>
                        @else
                            <div class="alert alert-success py-2 small mb-3">
                                Al activar la cuenta, el usuario podrá volver a ingresar al sistema con sus credenciales autorizadas.
                            </div>
                        @endif
                        <div class="mb-2">
                            <label class="form-label small fw-semibold">Motivo de la acción (Auditoría) *</label>
                            <input type="text" name="motivo" class="form-control form-control-sm" required placeholder="Ej: Solicitud de baja temporal / Reactivación institucional...">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-sm btn-light" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-sm {{ $u->activo ? 'btn-warning text-dark fw-semibold' : 'btn-success fw-semibold' }}">
                            Confirmar {{ $u->activo ? 'Desactivación' : 'Activación' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Restablecimiento Seguro de Contraseña -->
    <div class="modal fade" id="modalResetPassword{{ $u->id }}" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow rounded-4">
                <div class="modal-header bg-primary text-white">
                    <h6 class="modal-title fw-bold">
                        <i class="bi bi-key-fill me-2"></i>Restablecer Acceso - {{ $u->name }}
                    </h6>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form action="{{ route('admin.usuarios.reset-password', $u) }}" method="POST">
                    @csrf
                    <div class="modal-body">
                        <p class="small text-muted mb-2">
                            Cuenta: <strong>{{ $u->email }}</strong> &bull; Rol: {{ $u->rol->nombre ?? 'Usuario' }}
                        </p>
                        <div class="alert alert-info py-2 small mb-3">
                            <i class="bi bi-info-circle me-1"></i>
                            El sistema generará una clave temporal de un solo uso protegida con hash para que el usuario restablezca su acceso. La operación quedará auditada.
                        </div>
                        <div class="mb-2">
                            <label class="form-label small fw-semibold">Motivo del restablecimiento *</label>
                            <input type="text" name="motivo" class="form-control form-control-sm" required placeholder="Ej: Olvido de contraseña informado por coordinación...">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-sm btn-light" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-sm btn-primary fw-semibold">
                            Generar Clave Temporal
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endforeach
@endsection

