@extends('layouts.app')

@section('title', 'Panel Principal - UPDSonline')

@section('content')
<div class="mb-4">
    <h3 class="fw-bold text-navy" style="color: var(--upds-navy);">¡Bienvenido, {{ auth()->user()->name }}!</h3>
    <p class="text-muted mb-0">Gestión de prácticas y vinculación con el entorno profesional UPDS.</p>
</div>

@if(auth()->user()->isEstudiante())
    <!-- Encabezado con Información Académica del Estudiante -->
    <div class="card border-0 shadow-sm rounded-4 p-3 mb-4" style="background: linear-gradient(135deg, #002244 0%, #003b73 100%); color: #ffffff;">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
            <div class="d-flex align-items-center gap-3">
                <div style="width: 52px; height: 52px; border-radius: 50%; background: rgba(255,255,255,0.15); display: flex; align-items: center; justify-content: center; font-size: 1.5rem; font-weight: 700;">
                    {{ strtoupper(substr($estudiante?->nombres ?? auth()->user()->name, 0, 1)) }}
                </div>
                <div>
                    <h5 class="fw-bold mb-0 text-white">{{ $estudiante ? $estudiante->nombre_completo : auth()->user()->name }}</h5>
                    <small class="text-white-50">
                        RU: <strong class="text-white">{{ $estudiante?->ru ?? 'Sin asignar' }}</strong> &bull;
                        Carrera: <span class="text-white">{{ $estudiante?->carrera?->nombre ?? 'Sin carrera' }}</span> &bull;
                        Semestre: <span class="text-white">{{ $estudiante?->semestre ? $estudiante->semestre . '°' : '-' }}</span> &bull;
                        Estado: <span class="badge bg-success rounded-pill px-2">{{ ucfirst($estudiante?->estado ?? 'activo') }}</span>
                    </small>
                </div>
            </div>
            <div>
                <a href="{{ route('perfil.edit') }}" class="btn btn-sm btn-outline-light rounded-pill px-3">
                    <i class="bi bi-person-circle me-1"></i> Mi Perfil
                </a>
            </div>
        </div>
    </div>

    <!-- 5 KPI Cards Estudiante (Datos Reales desde MySQL) -->
    <div class="row g-3 mb-4">
        <div class="col-6 col-md">
            <div class="kpi-card d-flex align-items-center gap-3">
                <div class="kpi-icon" style="background-color: #e0f2fe; color: #0284c7;">
                    <i class="bi bi-file-earmark-text"></i>
                </div>
                <div>
                    <div class="kpi-number">{{ $kpi['solicitudes_total'] }}</div>
                    <div class="kpi-label">Mis solicitudes</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md">
            <div class="kpi-card d-flex align-items-center gap-3">
                <div class="kpi-icon" style="background-color: #fef3c7; color: #d97706;">
                    <i class="bi bi-hourglass-split"></i>
                </div>
                <div>
                    <div class="kpi-number">{{ $kpi['solicitudes_pendientes'] }}</div>
                    <div class="kpi-label">Sol. pendientes</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md">
            <div class="kpi-card d-flex align-items-center gap-3">
                <div class="kpi-icon" style="background-color: #dcfce7; color: #16a34a;">
                    <i class="bi bi-briefcase-fill"></i>
                </div>
                <div>
                    <div class="kpi-number">{{ $kpi['practica_activa'] }}</div>
                    <div class="kpi-label">Prácticas activas</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md">
            <div class="kpi-card d-flex align-items-center gap-3">
                <div class="kpi-icon" style="background-color: #ede9fe; color: #7c3aed;">
                    <i class="bi bi-clock-history"></i>
                </div>
                <div>
                    <div class="kpi-number">{{ number_format($kpi['horas_validadas'], 0) }}</div>
                    <div class="kpi-label">Horas validadas</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md">
            <div class="kpi-card d-flex align-items-center gap-3">
                <div class="kpi-icon" style="background-color: #fee2e2; color: #dc2626;">
                    <i class="bi bi-file-earmark-excel"></i>
                </div>
                <div>
                    <div class="kpi-number">{{ $kpi['documentos_pendientes'] }}</div>
                    <div class="kpi-label">Docs por revisar</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Acciones Rápidas -->
    <div class="mb-4">
        <h6 class="fw-bold text-muted text-uppercase mb-3" style="font-size: 0.8rem; letter-spacing: 0.5px;">Acciones rápidas</h6>
        <div class="row g-3">
            <div class="col-6 col-md-3">
                <a href="{{ route('solicitudes.create') }}" class="action-card">
                    <i class="bi bi-file-earmark-plus action-icon"></i>
                    <span class="fw-semibold small">Nueva solicitud</span>
                </a>
            </div>
            <div class="col-6 col-md-3">
                <a href="{{ route('solicitudes.index', ['tab' => 'documentos']) }}" class="action-card">
                    <i class="bi bi-cloud-arrow-up action-icon"></i>
                    <span class="fw-semibold small">Adjuntar documento</span>
                </a>
            </div>
            <div class="col-6 col-md-3">
                @if($practicaActiva && in_array($practicaActiva->estado, ['en_curso', 'planificada']))
                    <a href="{{ route('seguimiento.show', $practicaActiva->id) }}#registroHoras" class="action-card">
                        <i class="bi bi-stopwatch action-icon text-success"></i>
                        <span class="fw-semibold small">Registrar horas</span>
                    </a>
                @else
                    <div class="action-card text-muted opacity-75" title="Requiere práctica profesional activa">
                        <i class="bi bi-stopwatch action-icon text-muted"></i>
                        <span class="fw-semibold small">Registrar horas</span>
                    </div>
                @endif
            </div>
            <div class="col-6 col-md-3">
                @if($practicaActiva)
                    <a href="{{ route('seguimiento.show', $practicaActiva->id) }}" class="action-card">
                        <i class="bi bi-graph-up-arrow action-icon"></i>
                        <span class="fw-semibold small">Consultar seguimiento</span>
                    </a>
                @else
                    <a href="{{ route('seguimiento.index') }}" class="action-card">
                        <i class="bi bi-graph-up-arrow action-icon"></i>
                        <span class="fw-semibold small">Consultar seguimiento</span>
                    </a>
                @endif
            </div>
        </div>
    </div>

    <!-- Mi Práctica Actual y Estado de Solicitudes -->
    <div class="row g-4 mb-4">
        <div class="col-12 col-lg-8">
            <div class="card border-0 shadow-sm rounded-4 p-4 h-100">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="fw-bold mb-0 text-navy" style="color: var(--upds-navy);">Mi práctica actual</h5>
                    @if($practicaActiva)
                        <span class="badge-en_curso">
                            {{ ucfirst(str_replace('_', ' ', $practicaActiva->estado)) }}
                        </span>
                    @else
                        <span class="badge-egresado">Sin práctica activa</span>
                    @endif
                </div>

                @if($practicaActiva)
                    <div class="p-3 rounded-3 mb-3" style="background-color: #f8fafc; border: 1px solid #e2e8f0;">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="text-muted small">Organización receptora:</div>
                                <div class="fw-bold fs-6">{{ $practicaActiva->solicitud->organizacion->nombre }}</div>
                            </div>
                            <div class="col-md-6">
                                <div class="text-muted small">Docente Tutor Asignado:</div>
                                <div class="fw-semibold text-primary">
                                    <i class="bi bi-person-badge me-1"></i>
                                    {{ $practicaActiva->tutor ? $practicaActiva->tutor->nombre_completo : 'Pendiente de asignación' }}
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="text-muted small">Período de Práctica:</div>
                                <div class="fw-semibold">
                                    {{ $practicaActiva->fecha_inicio ? $practicaActiva->fecha_inicio->format('d/m/Y') : 'Pendiente' }}
                                    al {{ $practicaActiva->fecha_fin_prevista ? $practicaActiva->fecha_fin_prevista->format('d/m/Y') : 'Pendiente' }}
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="text-muted small">Cómputo de Horas:</div>
                                <div class="fw-bold">
                                    <span class="text-success">{{ $practicaActiva->horas_validadas }}</span> / {{ $practicaActiva->horas_objetivo }} hrs validadas
                                </div>
                            </div>
                        </div>

                        <!-- Barra de Progreso basada exclusivamente en horas validadas / objetivo -->
                        <div class="mt-3">
                            <div class="d-flex justify-content-between small text-muted mb-1">
                                <span>Porcentaje de avance verificado</span>
                                <span class="fw-bold text-dark">{{ $practicaActiva->porcentaje_horas }}%</span>
                            </div>
                            <div class="progress" style="height: 12px; border-radius: 8px;">
                                <div class="progress-bar bg-success progress-bar-striped" role="progressbar"
                                     style="width: {{ $practicaActiva->porcentaje_horas }}%"
                                     aria-valuenow="{{ $practicaActiva->porcentaje_horas }}" aria-valuemin="0" aria-valuemax="100"></div>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-between align-items-center">
                        <small class="text-muted">Estado actual: <strong>{{ ucfirst(str_replace('_', ' ', $practicaActiva->estado)) }}</strong></small>
                        <a href="{{ route('seguimiento.show', $practicaActiva->id) }}" class="btn btn-sm btn-outline-primary fw-semibold rounded-pill px-3">
                            Ver seguimiento completo <i class="bi bi-arrow-right ms-1"></i>
                        </a>
                    </div>
                @else
                    <div class="text-center py-5">
                        <i class="bi bi-briefcase text-muted opacity-50" style="font-size: 2.5rem;"></i>
                        <p class="text-muted mt-3 mb-3 fw-medium">Actualmente no tienes una práctica profesional activa.</p>
                        <a href="{{ route('solicitudes.create') }}" class="btn btn-primary btn-sm px-4 fw-semibold rounded-pill">
                            <i class="bi bi-plus-circle me-1"></i> Iniciar Solicitud de Práctica
                        </a>
                    </div>
                @endif
            </div>
        </div>

        <div class="col-12 col-lg-4">
            <div class="card border-0 shadow-sm rounded-4 p-4 h-100">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="fw-bold text-navy mb-0">Mis Solicitudes</h6>
                    <a href="{{ route('solicitudes.index') }}" class="small text-decoration-none">Ver todas</a>
                </div>

                @if($solicitudes->isEmpty())
                    <div class="text-center py-4 text-muted">
                        <i class="bi bi-inbox fs-2 opacity-50 d-block mb-2"></i>
                        <p class="small mb-2">Todavía no has presentado solicitudes de prácticas profesionales.</p>
                        <a href="{{ route('solicitudes.create') }}" class="btn btn-outline-primary btn-sm">Nueva solicitud</a>
                    </div>
                @else
                    <div class="list-group list-group-flush">
                        @foreach($solicitudes->take(4) as $s)
                            <a href="{{ route('solicitudes.show', $s->id) }}" class="list-group-item list-group-item-action px-0 py-2 border-bottom">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <div class="fw-semibold text-truncate" style="max-width: 170px; font-size: 0.88rem;">
                                            {{ $s->organizacion->nombre }}
                                        </div>
                                        <small class="text-muted">{{ $s->fecha_solicitud ? $s->fecha_solicitud->format('d/m/Y') : '' }}</small>
                                    </div>
                                    <span class="badge badge-{{ $s->estado }}" style="font-size: 0.72rem;">
                                        {{ ucfirst($s->estado) }}
                                    </span>
                                </div>
                            </a>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>

@elseif(auth()->user()->isTutor())
    <!-- Encabezado Docente Tutor -->
    <div class="card border-0 shadow-sm rounded-4 p-3 mb-4" style="background: linear-gradient(135deg, #002244 0%, #003b73 100%); color: #ffffff;">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
            <div class="d-flex align-items-center gap-3">
                <div style="width: 52px; height: 52px; border-radius: 50%; background: rgba(255,255,255,0.15); display: flex; align-items: center; justify-content: center; font-size: 1.5rem; font-weight: 700;">
                    {{ strtoupper(substr($tutor?->nombres ?? auth()->user()->name, 0, 1)) }}
                </div>
                <div>
                    <h5 class="fw-bold mb-0 text-white">{{ $tutor ? $tutor->nombre_completo : auth()->user()->name }}</h5>
                    <small class="text-white-50">
                        Rol: <span class="text-white fw-semibold">Docente Tutor</span> &bull;
                        Especialidad: <span class="text-white">{{ $tutor?->especialidad ?? 'Docente Académico' }}</span> &bull;
                        Estado: <span class="badge bg-success rounded-pill px-2">{{ $tutor?->activo ? 'Activo' : 'Inactivo' }}</span>
                    </small>
                </div>
            </div>
            <div>
                <a href="{{ route('perfil.edit') }}" class="btn btn-sm btn-outline-light rounded-pill px-3">
                    <i class="bi bi-person-circle me-1"></i> Mi Perfil
                </a>
            </div>
        </div>
    </div>

    <!-- 6 KPI Cards Tutor (Datos Reales desde MySQL) -->
    <div class="row g-3 mb-4">
        <div class="col-6 col-lg-2">
            <div class="kpi-card d-flex align-items-center gap-3">
                <div class="kpi-icon" style="background-color: #e0f2fe; color: #0284c7;">
                    <i class="bi bi-people-fill"></i>
                </div>
                <div>
                    <div class="kpi-number">{{ $kpi['estudiantes_asignados'] }}</div>
                    <div class="kpi-label">Estudiantes</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-2">
            <div class="kpi-card d-flex align-items-center gap-3">
                <div class="kpi-icon" style="background-color: #dcfce7; color: #16a34a;">
                    <i class="bi bi-play-circle-fill"></i>
                </div>
                <div>
                    <div class="kpi-number">{{ $kpi['practicas_en_curso'] }}</div>
                    <div class="kpi-label">En curso</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-2">
            <div class="kpi-card d-flex align-items-center gap-3">
                <div class="kpi-icon" style="background-color: #fef3c7; color: #d97706;">
                    <i class="bi bi-hourglass-split"></i>
                </div>
                <div>
                    <div class="kpi-number">{{ $kpi['horas_pendientes'] }}</div>
                    <div class="kpi-label">Horas pendientes</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-2">
            <div class="kpi-card d-flex align-items-center gap-3">
                <div class="kpi-icon" style="background-color: #ede9fe; color: #7c3aed;">
                    <i class="bi bi-file-earmark-medical"></i>
                </div>
                <div>
                    <div class="kpi-number">{{ $kpi['informes_pendientes'] }}</div>
                    <div class="kpi-label">Informes pendientes</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-2">
            <div class="kpi-card d-flex align-items-center gap-3">
                <div class="kpi-icon" style="background-color: #fee2e2; color: #dc2626;">
                    <i class="bi bi-clipboard-check"></i>
                </div>
                <div>
                    <div class="kpi-number">{{ $kpi['evaluaciones_pendientes'] }}</div>
                    <div class="kpi-label">Eval. pendientes</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-2">
            <div class="kpi-card d-flex align-items-center gap-3">
                <div class="kpi-icon" style="background-color: #f1f5f9; color: #475569;">
                    <i class="bi bi-check2-all"></i>
                </div>
                <div>
                    <div class="kpi-number">{{ $kpi['practicas_finalizadas'] }}</div>
                    <div class="kpi-label">Concluidas</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Lista de Estudiantes Asignados al Tutor -->
    <div class="card border-0 shadow-sm rounded-4 p-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="fw-bold mb-0 text-navy" style="color: var(--upds-navy);">Mis estudiantes en seguimiento</h5>
            <a href="{{ route('seguimiento.index') }}" class="btn btn-sm btn-outline-primary">Ver todos</a>
        </div>
        <div class="table-responsive">
            <table class="table align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Estudiante</th>
                        <th>RU</th>
                        <th>Carrera</th>
                        <th>Organización</th>
                        <th>Horas Validadas</th>
                        <th>Avance</th>
                        <th>Estado</th>
                        <th class="text-end">Acción</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($practicas as $p)
                        <tr>
                            <td class="fw-semibold">{{ $p->solicitud->estudiante->nombre_completo }}</td>
                            <td><code>{{ $p->solicitud->estudiante->ru }}</code></td>
                            <td>{{ $p->solicitud->estudiante->carrera?->nombre ?? '-' }}</td>
                            <td>{{ $p->solicitud->organizacion->nombre }}</td>
                            <td><strong>{{ $p->horas_validadas }}</strong> / {{ $p->horas_objetivo }} hrs</td>
                            <td style="width: 160px;">
                                <div class="progress" style="height: 8px;">
                                    <div class="progress-bar bg-success" style="width: {{ $p->porcentaje_horas }}%"></div>
                                </div>
                                <small class="text-muted">{{ $p->porcentaje_horas }}%</small>
                            </td>
                            <td><span class="badge-{{ $p->estado }}">{{ ucfirst(str_replace('_', ' ', $p->estado)) }}</span></td>
                            <td class="text-end">
                                <a href="{{ route('seguimiento.show', $p->id) }}" class="btn btn-sm btn-upds">
                                    <i class="bi bi-eye"></i> Revisar
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted py-5">
                                <i class="bi bi-person-x fs-1 text-muted opacity-50 d-block mb-2"></i>
                                Actualmente no tienes estudiantes asignados para seguimiento.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

@elseif(auth()->user()->isAdmin())
    <!-- Vista Panel de Administración del Sistema -->
    <div class="card border-0 shadow-sm rounded-4 p-3 mb-4" style="background: linear-gradient(135deg, #002244 0%, #003b73 100%); color: #ffffff;">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
            <div class="d-flex align-items-center gap-3">
                <div style="width: 52px; height: 52px; border-radius: 50%; background: rgba(255,255,255,0.15); display: flex; align-items: center; justify-content: center; font-size: 1.5rem; font-weight: 700;">
                    <i class="bi bi-shield-lock-fill"></i>
                </div>
                <div>
                    <h5 class="fw-bold mb-0 text-white">{{ auth()->user()->name }}</h5>
                    <small class="text-white-50">
                        Rol: <strong class="text-white">Administrador del Sistema</strong> &bull;
                        Entorno: <span class="text-white">UPDSonline Cochabamba</span> &bull;
                        Período activo: <span class="badge bg-info text-dark rounded-pill px-2">{{ $kpi['periodo_vigente'] }}</span>
                    </small>
                </div>
            </div>
            <div class="d-flex gap-2">
                <a href="{{ route('admin.usuarios.create') }}" class="btn btn-sm btn-light text-navy fw-semibold rounded-pill px-3">
                    <i class="bi bi-person-plus-fill me-1"></i> Nuevo Usuario
                </a>
                <a href="{{ route('admin.usuarios.index') }}" class="btn btn-sm btn-outline-light rounded-pill px-3">
                    <i class="bi bi-people-fill me-1"></i> Gestionar Usuarios
                </a>
            </div>
        </div>
    </div>

    <!-- 10 Indicadores Administrativos Reales (MySQL) -->
    <div class="row g-3 mb-3">
        <div class="col-6 col-md-4 col-lg">
            <div class="kpi-card d-flex align-items-center gap-3 p-3">
                <div class="kpi-icon" style="background-color: #e0f2fe; color: #0284c7;">
                    <i class="bi bi-people-fill"></i>
                </div>
                <div>
                    <div class="kpi-number">{{ $kpi['total_usuarios'] }}</div>
                    <div class="kpi-label">Total usuarios</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-4 col-lg">
            <div class="kpi-card d-flex align-items-center gap-3 p-3">
                <div class="kpi-icon" style="background-color: #dcfce7; color: #16a34a;">
                    <i class="bi bi-person-check-fill"></i>
                </div>
                <div>
                    <div class="kpi-number">{{ $kpi['usuarios_activos'] }}</div>
                    <div class="kpi-label">Cuentas activas</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-4 col-lg">
            <div class="kpi-card d-flex align-items-center gap-3 p-3">
                <div class="kpi-icon" style="background-color: #fee2e2; color: #dc2626;">
                    <i class="bi bi-person-dash-fill"></i>
                </div>
                <div>
                    <div class="kpi-number">{{ $kpi['usuarios_inactivos'] }}</div>
                    <div class="kpi-label">Inactivos</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-4 col-lg">
            <div class="kpi-card d-flex align-items-center gap-3 p-3">
                <div class="kpi-icon" style="background-color: #ede9fe; color: #7c3aed;">
                    <i class="bi bi-mortarboard-fill"></i>
                </div>
                <div>
                    <div class="kpi-number">{{ $kpi['total_estudiantes'] }}</div>
                    <div class="kpi-label">Estudiantes</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-4 col-lg">
            <div class="kpi-card d-flex align-items-center gap-3 p-3">
                <div class="kpi-icon" style="background-color: #fdf2f8; color: #be185d;">
                    <i class="bi bi-person-badge-fill"></i>
                </div>
                <div>
                    <div class="kpi-number">{{ $kpi['total_tutores'] }}</div>
                    <div class="kpi-label">Docentes tutores</div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-6 col-md-4 col-lg">
            <div class="kpi-card d-flex align-items-center gap-3 p-3">
                <div class="kpi-icon" style="background-color: #fff7ed; color: #c2410c;">
                    <i class="bi bi-person-gear"></i>
                </div>
                <div>
                    <div class="kpi-number">{{ $kpi['total_coordinadores'] }}</div>
                    <div class="kpi-label">Coordinadores</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-4 col-lg">
            <div class="kpi-card d-flex align-items-center gap-3 p-3">
                <div class="kpi-icon" style="background-color: #e0e7ff; color: #4338ca;">
                    <i class="bi bi-building"></i>
                </div>
                <div>
                    <div class="kpi-number">{{ $kpi['organizaciones_activas'] }}</div>
                    <div class="kpi-label">Empresas activas</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-4 col-lg">
            <div class="kpi-card d-flex align-items-center gap-3 p-3">
                <div class="kpi-icon" style="background-color: #f0fdf4; color: #15803d;">
                    <i class="bi bi-calendar-check-fill"></i>
                </div>
                <div>
                    <div class="kpi-number fs-6 text-truncate" style="max-width: 140px;">{{ $kpi['periodo_vigente'] }}</div>
                    <div class="kpi-label">Período vigente</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-4 col-lg">
            <div class="kpi-card d-flex align-items-center gap-3 p-3">
                <div class="kpi-icon" style="background-color: #fef3c7; color: #d97706;">
                    <i class="bi bi-file-earmark-text-fill"></i>
                </div>
                <div>
                    <div class="kpi-number">{{ $kpi['solicitudes_total'] }}</div>
                    <div class="kpi-label">Solicitudes tot.</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-4 col-lg">
            <div class="kpi-card d-flex align-items-center gap-3 p-3">
                <div class="kpi-icon" style="background-color: #ecfdf5; color: #059669;">
                    <i class="bi bi-briefcase-fill"></i>
                </div>
                <div>
                    <div class="kpi-number">{{ $kpi['practicas_total'] }}</div>
                    <div class="kpi-label">Prácticas tot.</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Acciones Rápidas Administrativas -->
    <div class="mb-4">
        <h6 class="fw-bold mb-3 text-navy" style="color: var(--upds-navy);">
            <i class="bi bi-lightning-charge-fill text-warning me-1"></i>Acciones de Administración
        </h6>
        <div class="row g-3">
            <div class="col-6 col-md-3 col-lg">
                <a href="{{ route('admin.usuarios.create') }}" class="action-card text-decoration-none">
                    <i class="bi bi-person-plus-fill action-icon text-primary"></i>
                    <span class="fw-semibold small text-dark">Registrar usuario</span>
                </a>
            </div>
            <div class="col-6 col-md-3 col-lg">
                <a href="{{ route('admin.usuarios.index') }}" class="action-card text-decoration-none">
                    <i class="bi bi-people action-icon text-primary"></i>
                    <span class="fw-semibold small text-dark">Gestionar cuentas</span>
                </a>
            </div>
            <div class="col-6 col-md-3 col-lg">
                <a href="{{ route('admin.roles.index') }}" class="action-card text-decoration-none">
                    <i class="bi bi-shield-check action-icon text-success"></i>
                    <span class="fw-semibold small text-dark">Roles y permisos</span>
                </a>
            </div>
            <div class="col-6 col-md-3 col-lg">
                <a href="{{ route('admin.periodos.index') }}" class="action-card text-decoration-none">
                    <i class="bi bi-calendar3 action-icon text-info"></i>
                    <span class="fw-semibold small text-dark">Períodos académicos</span>
                </a>
            </div>
            <div class="col-6 col-md-3 col-lg">
                <a href="{{ route('organizaciones.index') }}" class="action-card text-decoration-none">
                    <i class="bi bi-buildings action-icon text-secondary"></i>
                    <span class="fw-semibold small text-dark">Organizaciones</span>
                </a>
            </div>
            <div class="col-6 col-md-3 col-lg">
                <a href="{{ route('reportes.index') }}" class="action-card text-decoration-none">
                    <i class="bi bi-bar-chart-line action-icon text-dark"></i>
                    <span class="fw-semibold small text-dark">Reportes generales</span>
                </a>
            </div>
            <div class="col-6 col-md-3 col-lg">
                <a href="{{ route('admin.auditoria.index') }}" class="action-card text-decoration-none">
                    <i class="bi bi-journal-text action-icon text-danger"></i>
                    <span class="fw-semibold small text-dark">Auditoría</span>
                </a>
            </div>
        </div>
    </div>

    <!-- Actividad Reciente y Cuentas Creadas -->
    <div class="row g-4 mb-4">
        <!-- Registro de Auditoría Administrativa -->
        <div class="col-12 col-lg-7">
            <div class="card border-0 shadow-sm rounded-4 p-4 h-100">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="fw-bold mb-0 text-navy" style="color: var(--upds-navy);">
                        <i class="bi bi-clock-history text-primary me-2"></i>Actividad Administrativa Reciente
                    </h6>
                    <a href="{{ route('admin.auditoria.index') }}" class="btn btn-sm btn-outline-primary">Ver auditoría completa</a>
                </div>
                <div class="table-responsive">
                    <table class="table table-sm align-middle small mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Fecha/Hora</th>
                                <th>Acción</th>
                                <th>Usuario Afectado</th>
                                <th>Motivo / Resumen</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($auditoriaReciente as $aud)
                                <tr>
                                    <td class="text-muted" style="font-size: 0.75rem;">{{ $aud->created_at->format('d/m/Y H:i') }}</td>
                                    <td>
                                        <span class="badge bg-light text-dark border">{{ $aud->accion }}</span>
                                    </td>
                                    <td>
                                        @if($aud->usuarioAfectado)
                                            <span class="fw-semibold">{{ $aud->usuarioAfectado->name }}</span>
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>
                                    <td class="text-truncate" style="max-width: 180px;" title="{{ $aud->motivo }}">
                                        {{ $aud->motivo ?: 'Sin observaciones' }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center py-4 text-muted">
                                        <i class="bi bi-info-circle me-1"></i>No hay registros de auditoría recientes.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Usuarios Registrados Recientemente -->
        <div class="col-12 col-lg-5">
            <div class="card border-0 shadow-sm rounded-4 p-4 h-100">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="fw-bold mb-0 text-navy" style="color: var(--upds-navy);">
                        <i class="bi bi-people text-primary me-2"></i>Cuentas Recientes
                    </h6>
                    <a href="{{ route('admin.usuarios.index') }}" class="btn btn-sm btn-outline-primary">Ver todas</a>
                </div>
                <div class="list-group list-group-flush small">
                    @forelse($usuariosRecientes as $ur)
                        <div class="list-group-item d-flex justify-content-between align-items-center px-0 py-2">
                            <div>
                                <div class="fw-semibold text-dark">{{ $ur->name }}</div>
                                <div class="text-muted" style="font-size: 0.75rem;">{{ $ur->email }}</div>
                            </div>
                            <div class="text-end">
                                <span class="badge bg-primary-subtle text-primary border">{{ $ur->rol->nombre ?? 'Usuario' }}</span>
                                <div class="mt-1">
                                    @if($ur->activo)
                                        <span class="badge bg-success-subtle text-success" style="font-size: 0.68rem;">Activo</span>
                                    @else
                                        <span class="badge bg-danger-subtle text-danger" style="font-size: 0.68rem;">Inactivo</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-3 text-muted">No hay usuarios registrados.</div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

@else
    <!-- Vista Coordinador Académico -->
    <div class="card border-0 shadow-sm rounded-4 p-3 mb-4" style="background: linear-gradient(135deg, #002244 0%, #003b73 100%); color: #ffffff;">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
            <div class="d-flex align-items-center gap-3">
                <div style="width: 52px; height: 52px; border-radius: 50%; background: rgba(255,255,255,0.15); display: flex; align-items: center; justify-content: center; font-size: 1.5rem; font-weight: 700;">
                    <i class="bi bi-mortarboard-fill"></i>
                </div>
                <div>
                    <h5 class="fw-bold mb-0 text-white">{{ auth()->user()->name }}</h5>
                    <small class="text-white-50">
                        Rol: <strong class="text-white">{{ auth()->user()->rol->nombre ?? 'Coordinador Académico' }}</strong> &bull;
                        Área: <span class="text-white">Ingeniería de Sistemas / Prácticas Profesionales</span> &bull;
                        Estado: <span class="badge bg-success rounded-pill px-2">Activo</span>
                    </small>
                </div>
            </div>
            <div class="d-flex gap-2">
                <a href="{{ route('solicitudes.index') }}" class="btn btn-sm btn-outline-light rounded-pill px-3">
                    <i class="bi bi-inbox me-1"></i> Bandeja Solicitudes
                </a>
                <a href="{{ route('reportes.index') }}" class="btn btn-sm btn-light text-navy fw-semibold rounded-pill px-3">
                    <i class="bi bi-bar-chart-line me-1"></i> Reportes
                </a>
            </div>
        </div>
    </div>

    <!-- 10 Indicadores KPI Principales de Coordinación (MySQL Real) -->
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-4 col-lg">
            <div class="kpi-card d-flex align-items-center gap-3 p-3">
                <div class="kpi-icon" style="background-color: #e0f2fe; color: #0284c7;">
                    <i class="bi bi-people-fill"></i>
                </div>
                <div>
                    <div class="kpi-number">{{ $kpi['total_estudiantes'] }}</div>
                    <div class="kpi-label">Estudiantes</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-4 col-lg">
            <div class="kpi-card d-flex align-items-center gap-3 p-3">
                <div class="kpi-icon" style="background-color: #fee2e2; color: #dc2626;">
                    <i class="bi bi-hourglass-split"></i>
                </div>
                <div>
                    <div class="kpi-number">{{ $kpi['solicitudes_pendientes'] }}</div>
                    <div class="kpi-label">Sol. pendientes</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-4 col-lg">
            <div class="kpi-card d-flex align-items-center gap-3 p-3">
                <div class="kpi-icon" style="background-color: #fef3c7; color: #d97706;">
                    <i class="bi bi-exclamation-triangle"></i>
                </div>
                <div>
                    <div class="kpi-number">{{ $kpi['solicitudes_observadas'] }}</div>
                    <div class="kpi-label">Sol. observadas</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-4 col-lg">
            <div class="kpi-card d-flex align-items-center gap-3 p-3">
                <div class="kpi-icon" style="background-color: #dcfce7; color: #16a34a;">
                    <i class="bi bi-check-circle-fill"></i>
                </div>
                <div>
                    <div class="kpi-number">{{ $kpi['solicitudes_aprobadas'] }}</div>
                    <div class="kpi-label">Sol. aprobadas</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-4 col-lg">
            <div class="kpi-card d-flex align-items-center gap-3 p-3">
                <div class="kpi-icon" style="background-color: #ede9fe; color: #7c3aed;">
                    <i class="bi bi-briefcase-fill"></i>
                </div>
                <div>
                    <div class="kpi-number">{{ $kpi['practicas_activas'] }}</div>
                    <div class="kpi-label">Prácticas activas</div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-6 col-md-4 col-lg">
            <div class="kpi-card d-flex align-items-center gap-3 p-3">
                <div class="kpi-icon" style="background-color: #ecfdf5; color: #059669;">
                    <i class="bi bi-award-fill"></i>
                </div>
                <div>
                    <div class="kpi-number">{{ $kpi['practicas_finalizadas'] }}</div>
                    <div class="kpi-label">Concluidas</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-4 col-lg">
            <div class="kpi-card d-flex align-items-center gap-3 p-3">
                <div class="kpi-icon" style="background-color: #f1f5f9; color: #475569;">
                    <i class="bi bi-x-octagon-fill"></i>
                </div>
                <div>
                    <div class="kpi-number">{{ $kpi['practicas_abandonadas'] }}</div>
                    <div class="kpi-label">Abandonadas / Canc.</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-4 col-lg">
            <div class="kpi-card d-flex align-items-center gap-3 p-3">
                <div class="kpi-icon" style="background-color: #e0e7ff; color: #4338ca;">
                    <i class="bi bi-building"></i>
                </div>
                <div>
                    <div class="kpi-number">{{ $kpi['organizaciones_activas'] }}</div>
                    <div class="kpi-label">Empresas activas</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-4 col-lg">
            <div class="kpi-card d-flex align-items-center gap-3 p-3">
                <div class="kpi-icon" style="background-color: #fdf2f8; color: #be185d;">
                    <i class="bi bi-person-badge-fill"></i>
                </div>
                <div>
                    <div class="kpi-number">{{ $kpi['tutores_con_practicas'] }}</div>
                    <div class="kpi-label">Tutores activos</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-4 col-lg">
            <div class="kpi-card d-flex align-items-center gap-3 p-3">
                <div class="kpi-icon" style="background-color: #fff7ed; color: #c2410c;">
                    <i class="bi bi-file-earmark-check"></i>
                </div>
                <div>
                    <div class="kpi-number">{{ $kpi['documentos_pendientes'] }}</div>
                    <div class="kpi-label">Docs por validar</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Alertas Académicas Reales -->
    @if(count($alertas['sin_tutor']) > 0 || count($alertas['por_vencer']) > 0 || $kpi['solicitudes_pendientes'] > 0 || $kpi['documentos_pendientes'] > 0)
        <div class="card border-0 shadow-sm rounded-4 p-4 mb-4" style="border-left: 4px solid var(--upds-accent) !important;">
            <h6 class="fw-bold mb-3" style="color: var(--upds-navy);">
                <i class="bi bi-bell-fill text-warning me-2"></i>Alertas Académicas de Seguimiento
            </h6>
            <div class="row g-2">
                @if($kpi['solicitudes_pendientes'] > 0)
                    <div class="col-md-6">
                        <div class="p-3 bg-light rounded-3 d-flex justify-content-between align-items-center">
                            <div>
                                <i class="bi bi-file-earmark-text text-danger me-2"></i>
                                <strong>{{ $kpi['solicitudes_pendientes'] }} solicitudes</strong> pendientes de revisión inicial.
                            </div>
                            <a href="{{ route('solicitudes.index') }}" class="btn btn-xs btn-outline-danger">Atender</a>
                        </div>
                    </div>
                @endif
                @if($kpi['documentos_pendientes'] > 0)
                    <div class="col-md-6">
                        <div class="p-3 bg-light rounded-3 d-flex justify-content-between align-items-center">
                            <div>
                                <i class="bi bi-file-earmark-check text-warning me-2"></i>
                                <strong>{{ $kpi['documentos_pendientes'] }} documentos</strong> pendientes de verificación documental.
                            </div>
                            <a href="{{ route('solicitudes.index', ['tab' => 'documentos']) }}" class="btn btn-xs btn-outline-warning text-dark">Validar</a>
                        </div>
                    </div>
                @endif
                @foreach($alertas['sin_tutor'] as $pst)
                    <div class="col-md-6">
                        <div class="p-3 bg-light rounded-3 d-flex justify-content-between align-items-center">
                            <div>
                                <i class="bi bi-person-x text-primary me-2"></i>
                                Práctica de <strong>{{ $pst->solicitud->estudiante->nombre_completo }}</strong> sin tutor asignado.
                            </div>
                            <a href="{{ route('seguimiento.show', $pst->id) }}" class="btn btn-xs btn-outline-primary">Asignar</a>
                        </div>
                    </div>
                @endforeach
                @foreach($alertas['por_vencer'] as $ppv)
                    <div class="col-md-6">
                        <div class="p-3 bg-light rounded-3 d-flex justify-content-between align-items-center">
                            <div>
                                <i class="bi bi-clock-history text-warning me-2"></i>
                                Práctica de <strong>{{ $ppv->solicitud->estudiante->nombre_completo }}</strong> próxima a culminar ({{ $ppv->fecha_fin_prevista->format('d/m/Y') }}).
                            </div>
                            <a href="{{ route('seguimiento.show', $ppv->id) }}" class="btn btn-xs btn-outline-secondary">Supervisar</a>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <!-- Solicitudes Recientes a Revisar -->
    <div class="card border-0 shadow-sm rounded-4 p-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="fw-bold mb-0 text-navy" style="color: var(--upds-navy);">Solicitudes recientes recibidas</h5>
            <a href="{{ route('solicitudes.index') }}" class="btn btn-sm btn-outline-primary">Ver todas</a>
        </div>
        <div class="table-responsive">
            <table class="table align-middle">
                <thead class="table-light">
                    <tr>
                        <th>N°</th>
                        <th>Estudiante</th>
                        <th>Carrera</th>
                        <th>Organización Receptora</th>
                        <th>Fecha</th>
                        <th>Estado</th>
                        <th class="text-end">Acción</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($solicitudesRecientes as $sol)
                        <tr>
                            <td><strong>S-00{{ $sol->id }}</strong></td>
                            <td class="fw-semibold">{{ $sol->estudiante->nombre_completo }}</td>
                            <td>{{ $sol->estudiante->carrera->nombre }}</td>
                            <td>{{ $sol->organizacion->nombre }}</td>
                            <td class="small text-muted">{{ $sol->fecha_solicitud->format('d/m/Y') }}</td>
                            <td><span class="badge-{{ $sol->estado }}">{{ ucfirst(str_replace('_', ' ', $sol->estado)) }}</span></td>
                            <td class="text-end">
                                <a href="{{ route('solicitudes.show', $sol->id) }}" class="btn btn-sm btn-upds">
                                    <i class="bi bi-check2-square me-1"></i> Revisar
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-4 text-muted">No hay solicitudes presentadas recientemente.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endif
@endsection

