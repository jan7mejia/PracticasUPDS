<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'UPDSonline - Sistema de Consulta Académica')</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <style>
        :root {
            --upds-navy: #002244;
            --upds-primary: #003b73;
            --upds-blue: #0056b3;
            --upds-accent: #00a8e8;
            --upds-bg: #f4f7fb;
            --upds-card-border: #e2e8f0;
            --upds-text-dark: #1e293b;
            --upds-text-muted: #64748b;
        }

        body {
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
            background-color: var(--upds-bg);
            color: var(--upds-text-dark);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            margin: 0;
            padding: 0;
        }

        /* Top Bar UPDS - Siempre arriba y sticky */
        .upds-navbar {
            order: 0;
            flex-shrink: 0;
            position: sticky;
            top: 0;
            z-index: 1030;
            background-color: var(--upds-navy);
            padding: 0.65rem 1.5rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.15);
        }

        .upds-brand-logo {
            display: flex;
            align-items: center;
            gap: 12px;
            color: #ffffff;
            text-decoration: none;
        }

        .upds-brand-title {
            font-size: 1.25rem;
            font-weight: 700;
            letter-spacing: -0.5px;
            color: #ffffff;
        }

        .upds-brand-title span {
            color: var(--upds-accent);
        }

        .upds-brand-subtitle {
            font-size: 0.8rem;
            color: #cbd5e1;
            margin-left: 10px;
            padding-left: 10px;
            border-left: 1px solid rgba(255,255,255,0.25);
            font-weight: 400;
        }

        /* Nav links */
        .navbar-nav .nav-link {
            color: #e2e8f0 !important;
            font-weight: 500;
            font-size: 0.92rem;
            padding: 0.5rem 0.9rem !important;
            border-radius: 6px;
            transition: all 0.2s ease;
        }

        .navbar-nav .nav-link:hover, .navbar-nav .nav-link.active {
            color: #ffffff !important;
            background-color: rgba(255, 255, 255, 0.12);
        }

        /* User Dropdown Pill */
        .user-pill {
            background-color: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.15);
            border-radius: 30px;
            padding: 4px 14px 4px 8px;
            color: white;
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 0.88rem;
            cursor: pointer;
            transition: background 0.2s;
        }

        .user-pill:hover {
            background-color: rgba(255, 255, 255, 0.18);
        }

        .user-avatar {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background-color: var(--upds-blue);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 0.85rem;
        }

        /* KPI Card */
        .kpi-card {
            background: #ffffff;
            border-radius: 12px;
            border: 1px solid var(--upds-card-border);
            padding: 1.3rem;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .kpi-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(0,0,0,0.08);
        }

        .kpi-icon {
            width: 46px;
            height: 46px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.4rem;
        }

        .kpi-number {
            font-size: 1.85rem;
            font-weight: 700;
            color: var(--upds-navy);
            line-height: 1.1;
        }

        .kpi-label {
            font-size: 0.85rem;
            color: var(--upds-text-muted);
            font-weight: 500;
        }

        /* Action buttons card */
        .action-card {
            background: #ffffff;
            border-radius: 12px;
            border: 1px solid var(--upds-card-border);
            padding: 1rem;
            text-align: center;
            text-decoration: none;
            color: var(--upds-text-dark);
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 8px;
            transition: all 0.2s ease;
        }

        .action-card:hover {
            border-color: var(--upds-blue);
            color: var(--upds-blue);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 86, 179, 0.12);
        }

        .action-icon {
            font-size: 1.6rem;
            color: var(--upds-primary);
        }

        /* Badges */
        .badge-activo, .badge-aprobada, .badge-validada, .badge-validado, .badge-completada {
            background-color: #d1fae5;
            color: #065f46;
            font-weight: 600;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.78rem;
        }

        .badge-en_revision, .badge-presentada, .badge-pendiente, .badge-en_proceso {
            background-color: #fef3c7;
            color: #92400e;
            font-weight: 600;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.78rem;
        }

        .badge-observada, .badge-observado, .badge-rechazada, .badge-rechazado, .badge-cancelada, .badge-abandonada {
            background-color: #fee2e2;
            color: #991b1b;
            font-weight: 600;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.78rem;
        }

        .badge-en_curso, .badge-en_practica, .badge-planificada {
            background-color: #dbeafe;
            color: #1e40af;
            font-weight: 600;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.78rem;
        }

        .badge-egresado, .badge-inactiva, .badge-inactivo {
            background-color: #f1f5f9;
            color: #475569;
            font-weight: 600;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.78rem;
        }

        /* Demo Role Banner - removed */

        .btn-upds {
            background-color: var(--upds-primary);
            color: white;
            font-weight: 600;
            border-radius: 8px;
            padding: 8px 18px;
            border: none;
            transition: all 0.2s;
        }

        .btn-upds:hover {
            background-color: var(--upds-blue);
            color: white;
            box-shadow: 0 4px 10px rgba(0, 86, 179, 0.25);
        }

        main {
            order: 1;
            flex: 1 0 auto;
            width: 100%;
        }

        footer {
            order: 2;
            flex-shrink: 0;
            margin-top: auto;
            background: #ffffff;
            border-top: 1px solid var(--upds-card-border);
            padding: 1rem 0;
            font-size: 0.82rem;
            color: var(--upds-text-muted);
        }

        /* Separación uniforme y generosa de tarjetas y rejillas */
        .card {
            border-radius: 1rem;
            border: 1px solid rgba(226, 232, 240, 0.9);
            box-shadow: 0 4px 16px rgba(15, 23, 42, 0.04);
            transition: box-shadow 0.2s ease, transform 0.2s ease;
        }
        .card-header {
            border-top-left-radius: 1rem !important;
            border-top-right-radius: 1rem !important;
        }

        /* Separación vertical automática cuando hay tarjetas apiladas */
        .card + .card {
            margin-top: 1.75rem;
        }

        /* Espaciado generoso de 28px en filas con g-4 */
        .row.g-4 {
            --bs-gutter-x: 1.75rem;
            --bs-gutter-y: 1.75rem;
        }

        /* Margen inferior estándar para secciones de contenido */
        .mb-4 {
            margin-bottom: 1.75rem !important;
        }
    </style>
    @yield('styles')
</head>
<body>

    <!-- Main Navigation Bar -->
    <nav class="navbar navbar-expand-lg upds-navbar">
        <div class="container-fluid px-lg-4">
            <!-- Brand -->
            <a class="upds-brand-logo" href="{{ route('dashboard') }}">
                <div class="d-flex align-items-center">
                    <div class="upds-brand-title">UPDS<span>online</span></div>
                    <div class="upds-brand-subtitle d-none d-md-block">Sistema de Gestión de Prácticas</div>
                </div>
            </a>

            <!-- Mobile toggle -->
            <button class="navbar-toggler border-0 text-white" type="button" data-bs-toggle="collapse" data-bs-target="#updsNav">
                <i class="bi bi-list fs-3"></i>
            </button>

            <!-- Links -->
            <div class="collapse navbar-collapse" id="updsNav">
                <ul class="navbar-nav mx-auto mb-2 mb-lg-0">
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}">
                            <i class="bi bi-house-door me-1"></i> Inicio
                        </a>
                    </li>
                    @if(auth()->user()->isAdmin())
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('admin.usuarios.*') ? 'active' : '' }}" href="{{ route('admin.usuarios.index') }}">
                                <i class="bi bi-people me-1"></i> Usuarios
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('admin.roles.*') ? 'active' : '' }}" href="{{ route('admin.roles.index') }}">
                                <i class="bi bi-shield-check me-1"></i> Roles
                            </a>
                        </li>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle {{ request()->routeIs('admin.periodos.*') || request()->routeIs('admin.carreras.*') ? 'active' : '' }}" href="#" id="catalogosDropdown" role="button" data-bs-toggle="dropdown">
                                <i class="bi bi-collection me-1"></i> Catálogos
                            </a>
                            <ul class="dropdown-menu shadow border-0" aria-labelledby="catalogosDropdown">
                                <li>
                                    <a class="dropdown-item" href="{{ route('admin.periodos.index') }}">
                                        <i class="bi bi-calendar3 me-2 text-info"></i> Períodos Académicos
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item" href="{{ route('admin.carreras.index') }}">
                                        <i class="bi bi-journal-bookmark me-2 text-primary"></i> Carreras Universitarias
                                    </a>
                                </li>
                            </ul>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('organizaciones.*') ? 'active' : '' }}" href="{{ route('organizaciones.index') }}">
                                <i class="bi bi-building me-1"></i> Organizaciones
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('reportes.*') ? 'active' : '' }}" href="{{ route('reportes.index') }}">
                                <i class="bi bi-bar-chart-line me-1"></i> Reportes
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('admin.auditoria.*') ? 'active' : '' }}" href="{{ route('admin.auditoria.index') }}">
                                <i class="bi bi-journal-text me-1"></i> Auditoría
                            </a>
                        </li>
                    @else
                        @if(auth()->user()->isCoordinador() || auth()->user()->isTutor())
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('estudiantes.*') ? 'active' : '' }}" href="{{ route('estudiantes.index') }}">
                                <i class="bi bi-people me-1"></i> {{ auth()->user()->isTutor() ? 'Mis Estudiantes' : 'Estudiantes' }}
                            </a>
                        </li>
                        @endif
                        @if(auth()->user()->isCoordinador())
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('organizaciones.*') ? 'active' : '' }}" href="{{ route('organizaciones.index') }}">
                                <i class="bi bi-building me-1"></i> Organizaciones
                            </a>
                        </li>
                        @endif
                        @if(!auth()->user()->isTutor())
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('solicitudes.*') ? 'active' : '' }}" href="{{ route('solicitudes.index') }}">
                                <i class="bi bi-file-earmark-text me-1"></i> Solicitudes
                                @php
                                    $pendientes = 0;
                                    try {
                                        if(auth()->user()->isCoordinador()) {
                                            $pendientes = \App\Models\SolicitudPractica::whereIn('estado', ['presentada','en_revision'])->count();
                                        } elseif(auth()->user()->isEstudiante()) {
                                            $pendientes = \App\Models\SolicitudPractica::where('estado','observada')
                                                ->whereHas('estudiante', fn($q) => $q->where('user_id', auth()->id()))->count();
                                        }
                                    } catch(\Exception $e) { $pendientes = 0; }
                                @endphp
                                @if($pendientes > 0)
                                    <span class="badge rounded-pill ms-1" style="background:#ef4444;font-size:0.68rem;">{{ $pendientes }}</span>
                                @endif
                            </a>
                        </li>
                        @endif
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('seguimiento.*') ? 'active' : '' }}" href="{{ route('seguimiento.index') }}">
                                <i class="bi bi-clock-history me-1"></i> {{ auth()->user()->isEstudiante() ? 'Mi Seguimiento' : (auth()->user()->isTutor() ? 'Seguimiento Académico' : 'Seguimiento') }}
                            </a>
                        </li>
                        @if(!auth()->user()->isEstudiante())
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('reportes.*') ? 'active' : '' }}" href="{{ route('reportes.index') }}">
                                <i class="bi bi-bar-chart-line me-1"></i> {{ auth()->user()->isTutor() ? 'Mis Reportes' : 'Reportes' }}
                            </a>
                        </li>
                        @endif
                    @endif
                </ul>

                <!-- User profile / logout -->
                <div class="d-flex align-items-center gap-3">
                    <div class="dropdown">
                        <div class="user-pill dropdown-toggle" id="userMenu" data-bs-toggle="dropdown" aria-expanded="false">
                            <div class="user-avatar">
                                {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                            </div>
                            <div class="d-none d-sm-block text-start">
                                <div class="fw-semibold text-truncate" style="max-width: 140px; line-height: 1.2;">{{ auth()->user()->name }}</div>
                                <small class="text-white-50" style="font-size: 0.72rem;">{{ auth()->user()->rol->nombre ?? 'Usuario' }}</small>
                            </div>
                        </div>
                        <ul class="dropdown-menu dropdown-menu-end shadow border-0 mt-2" aria-labelledby="userMenu">
                            <li class="px-3 py-2 border-bottom">
                                <div class="fw-bold">{{ auth()->user()->name }}</div>
                                <small class="text-muted">{{ auth()->user()->email }}</small>
                                <div class="mt-1"><span class="badge bg-primary">{{ auth()->user()->rol->nombre }}</span></div>
                            </li>
                            <li><hr class="dropdown-divider my-1"></li>
                            <li>
                                <a class="dropdown-item" href="{{ route('perfil.edit') }}">
                                    <i class="bi bi-person-circle me-2 text-primary"></i> Mi Perfil
                                </a>
                            </li>
                            @if(auth()->user()->isAdmin())
                            <li>
                                <a class="dropdown-item" href="{{ route('admin.usuarios.index') }}">
                                    <i class="bi bi-people-fill me-2 text-warning"></i> Gestión de Usuarios
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="{{ route('admin.roles.index') }}">
                                    <i class="bi bi-shield-check me-2 text-success"></i> Roles y Permisos
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="{{ route('admin.periodos.index') }}">
                                    <i class="bi bi-calendar3 me-2 text-info"></i> Períodos Académicos
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="{{ route('admin.auditoria.index') }}">
                                    <i class="bi bi-journal-text me-2 text-danger"></i> Registro de Auditoría
                                </a>
                            </li>
                            @endif
                            <li><hr class="dropdown-divider my-1"></li>
                            <li>
                                <form action="{{ route('logout') }}" method="POST">
                                    @csrf
                                    <button type="submit" class="dropdown-item text-danger">
                                        <i class="bi bi-box-arrow-right me-2"></i> Cerrar sesión
                                    </button>
                                </form>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content Area -->
    <main class="container-fluid px-lg-4 py-4">
        <!-- Notificaciones Flotantes Tipo Toast (Modernas, breves y con auto-cierre) -->
        <div class="toast-container position-fixed top-0 end-0 p-3" style="z-index: 1090;">
            @if(session('success'))
                <div class="toast align-items-center text-bg-success border-0 shadow-lg rounded-4 show" role="alert" aria-live="assertive" aria-atomic="true" data-bs-delay="4000">
                    <div class="d-flex">
                        <div class="toast-body d-flex align-items-center gap-2 py-3 fs-6">
                            <i class="bi bi-check-circle-fill fs-5"></i>
                            <span class="fw-medium">{{ session('success') }}</span>
                        </div>
                        <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Cerrar"></button>
                    </div>
                </div>
            @endif

            @if(session('info'))
                <div class="toast align-items-center text-bg-primary border-0 shadow-lg rounded-4 show" role="alert" aria-live="assertive" aria-atomic="true" data-bs-delay="4000">
                    <div class="d-flex">
                        <div class="toast-body d-flex align-items-center gap-2 py-3 fs-6">
                            <i class="bi bi-info-circle-fill fs-5"></i>
                            <span class="fw-medium">{{ session('info') }}</span>
                        </div>
                        <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Cerrar"></button>
                    </div>
                </div>
            @endif

            @if(session('warning'))
                <div class="toast align-items-center text-bg-warning border-0 shadow-lg rounded-4 show" role="alert" aria-live="assertive" aria-atomic="true" data-bs-delay="5000">
                    <div class="d-flex">
                        <div class="toast-body d-flex align-items-center gap-2 py-3 fs-6 text-dark">
                            <i class="bi bi-exclamation-triangle-fill fs-5 text-dark"></i>
                            <span class="fw-medium">{{ session('warning') }}</span>
                        </div>
                        <button type="button" class="btn-close me-2 m-auto" data-bs-dismiss="toast" aria-label="Cerrar"></button>
                    </div>
                </div>
            @endif
        </div>

        <!-- Errores de Validación o Formulario (Permanece visible hasta corrección) -->
        @if(session('error') || (isset($errors) && $errors->any()))
            <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm rounded-4 mb-4" role="alert">
                <div class="d-flex align-items-start gap-2">
                    <i class="bi bi-x-circle-fill fs-5 text-danger mt-1"></i>
                    <div class="flex-grow-1">
                        <div class="fw-bold">{{ session('error') ?? 'Completa o corrige los siguientes campos:' }}</div>
                        @if(isset($errors) && $errors->any())
                            <ul class="mb-0 mt-1 ps-3 small">
                                @foreach($errors->all() as $err)
                                    <li>{{ $err }}</li>
                                @endforeach
                            </ul>
                        @endif
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
                </div>
            </div>
        @endif

        @yield('content')
    </main>

    <!-- Modal Reutilizable de Confirmación Global -->
    <div class="modal fade" id="modalConfirmacionGlobal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
                <div class="modal-header border-0 pb-0">
                    <h6 class="modal-title fw-bold text-dark d-flex align-items-center gap-2" id="confirmModalTitle">
                        <i class="bi bi-question-circle-fill text-warning fs-5"></i>
                        <span id="confirmModalTitleText">Confirmar acción</span>
                    </h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body py-3">
                    <p class="small text-muted mb-0" id="confirmModalMessage">¿Estás seguro de realizar esta operación?</p>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-sm btn-light rounded-pill px-3" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-sm btn-primary rounded-pill px-3 fw-semibold" id="confirmModalButton">Confirmar</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer>
        <div class="container-fluid px-lg-4 d-flex flex-column flex-md-row justify-content-between align-items-center">
            <div>
                &copy; {{ date('Y') }} <strong>Universidad Privada Domingo Savio (UPDS)</strong> - Sede Cochabamba.
            </div>
            <div class="mt-2 mt-md-0">
                <span class="text-muted">Ingeniería de Sistemas &bull; Ingeniería de Software II &bull; Grupo 2</span>
            </div>
        </div>
    </footer>

    <!-- Bootstrap 5 Bundle JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // 1. Inicialización automática de Toasts flotantes
            var toastElList = [].slice.call(document.querySelectorAll('.toast'));
            toastElList.map(function(toastEl) {
                var toast = new bootstrap.Toast(toastEl, { autohide: true });
                toast.show();
            });

            // 2. Manejador global para acciones de confirmación modal
            document.querySelectorAll('[data-confirm]').forEach(function(element) {
                element.addEventListener('click', function(e) {
                    e.preventDefault();
                    var targetForm = this.closest('form');
                    var msg = this.getAttribute('data-confirm') || '¿Deseas confirmar esta operación?';
                    var title = this.getAttribute('data-confirm-title') || 'Confirmar acción';
                    var btnText = this.getAttribute('data-confirm-btn') || 'Confirmar';
                    var btnClass = this.getAttribute('data-confirm-class') || 'btn-primary';

                    document.getElementById('confirmModalMessage').innerText = msg;
                    document.getElementById('confirmModalTitleText').innerText = title;
                    var confirmBtn = document.getElementById('confirmModalButton');
                    confirmBtn.innerText = btnText;
                    confirmBtn.className = 'btn btn-sm rounded-pill px-3 fw-semibold ' + btnClass;

                    var modal = new bootstrap.Modal(document.getElementById('modalConfirmacionGlobal'));
                    confirmBtn.onclick = function() {
                        modal.hide();
                        if (targetForm) {
                            targetForm.submit();
                        } else if (element.tagName === 'A' && element.href) {
                            window.location.href = element.href;
                        }
                    };
                    modal.show();
                });
            });

            // 3. Buscador inteligente con retardo (debounce 350ms), control de longitud >= 2 y soporte Enter
            document.querySelectorAll('input[data-auto-search]').forEach(function(input) {
                var timer = null;
                var form = input.closest('form');
                if (!form) return;

                // Enviar inmediatamente si el usuario presiona Enter
                input.addEventListener('keydown', function(e) {
                    if (e.key === 'Enter') {
                        clearTimeout(timer);
                        var pageInput = form.querySelector('input[name="page"], input[name="sol_page"], input[name="doc_page"]');
                        if (pageInput) pageInput.value = 1;
                        form.submit();
                    }
                });

                // Control reactivo con debounce y validación de longitud (mínimo 2 caracteres o vacío)
                input.addEventListener('input', function() {
                    clearTimeout(timer);
                    var query = input.value.trim();

                    // Si escribe 1 carácter, no enviar búsquedas incompletas al servidor
                    if (query.length === 1) {
                        return;
                    }

                    timer = setTimeout(function() {
                        var pageInput = form.querySelector('input[name="page"], input[name="sol_page"], input[name="doc_page"]');
                        if (pageInput) pageInput.value = 1;

                        if (query.length >= 2 || query.length === 0) {
                            form.submit();
                        }
                    }, 350);
                });
            });

            // Mantener el foco y cursor al final del campo de búsqueda activo
            var activeSearchInput = document.querySelector('input[data-auto-search]:focus, input[data-auto-search]');
            if (activeSearchInput && activeSearchInput.value && document.activeElement === activeSearchInput) {
                var val = activeSearchInput.value;
                activeSearchInput.setSelectionRange(val.length, val.length);
            }
        });
    </script>
    @yield('scripts')
</body>
</html>
