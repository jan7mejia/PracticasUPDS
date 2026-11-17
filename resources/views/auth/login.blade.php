<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión - UPDSonline</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        :root {
            --upds-navy: #002244;
            --upds-primary: #003b73;
            --upds-blue: #0056b3;
            --upds-accent: #00a8e8;
        }

        body {
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
            background: linear-gradient(135deg, rgba(0, 26, 56, 0.82) 0%, rgba(0, 15, 35, 0.92) 100%),
                        url("{{ asset('images/campus-bg.jpg') }}") center center / cover no-repeat fixed;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1.5rem;
        }

        .login-card-wrapper {
            width: 100%;
            max-width: 950px;
            background: #ffffff;
            border-radius: 22px;
            overflow: hidden;
            box-shadow: 0 25px 60px rgba(0, 10, 25, 0.55);
            display: flex;
            min-height: 540px;
            border: 1px solid rgba(255, 255, 255, 0.25);
        }

        .login-left-banner {
            flex: 1;
            background: linear-gradient(145deg, #001f3f 0%, #003366 50%, #004b93 100%);
            color: #ffffff;
            padding: 3rem 2.5rem;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            position: relative;
            overflow: hidden;
        }

        .login-left-banner::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 160px;
            background: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 1440 320'%3E%3Cpath fill='%23001830' fill-opacity='0.4' d='M0,192L48,197.3C96,203,192,213,288,197.3C384,181,480,139,576,144C672,149,768,203,864,208C960,213,1056,171,1152,144C1248,117,1344,107,1392,101.3L1440,96L1440,320L1392,320C1344,320,1248,320,1152,320C1056,320,960,320,864,320C768,320,672,320,576,320C480,320,384,320,288,320C192,320,96,320,48,320L0,320Z'%3E%3C/path%3E%3C/svg%3E") bottom center no-repeat;
            background-size: cover;
            pointer-events: none;
        }

        .login-right-form {
            flex: 1.1;
            padding: 3rem 2.5rem;
            display: flex;
            flex-direction: column;
            justify-content: center;
            background: #ffffff;
        }

        .form-floating > .form-control:focus ~ label,
        .form-floating > .form-control:not(:placeholder-shown) ~ label {
            color: var(--upds-primary);
        }

        .form-control:focus {
            border-color: var(--upds-accent);
            box-shadow: 0 0 0 0.25rem rgba(0, 168, 232, 0.2);
        }

        .btn-login {
            background-color: var(--upds-primary);
            color: white;
            font-weight: 600;
            padding: 0.8rem;
            border-radius: 10px;
            border: none;
            transition: all 0.25s;
        }

        .btn-login:hover {
            background-color: var(--upds-blue);
            color: white;
            box-shadow: 0 6px 16px rgba(0, 59, 115, 0.3);
            transform: translateY(-1px);
        }

        .logo-upds-wrapper {
            display: inline-block;
            background: rgba(255,255,255,0.12);
            backdrop-filter: blur(8px);
            padding: 16px 22px;
            border-radius: 18px;
            border: 1px solid rgba(255,255,255,0.22);
        }
    </style>
</head>
<body>

<div class="login-card-wrapper">
    <!-- Left Banner con Logo Oficial UPDS -->
    <div class="login-left-banner d-none d-md-flex">
        <div>
            <!-- Logo Oficial UPDS en contenedor blanco elegante -->
            <div class="mb-4">
                <div class="mb-3">
                    <img src="{{ asset('images/logo-upds-card.png') }}"
                         alt="Universidad Privada Domingo Savio"
                         style="max-height: 145px; width: auto; object-fit: contain; filter: drop-shadow(0 10px 25px rgba(0,0,0,0.25)); border-radius: 20px;">
                </div>
                <h2 class="fw-bold mb-2 text-white" style="font-size:1.6rem; letter-spacing:-0.5px;">
                    UPDS<span style="color: var(--upds-accent);">online</span>
                </h2>
                <p class="text-light mb-0" style="font-size: 0.88rem; opacity:0.82; line-height:1.6;">
                    Sistema de Consulta Académica<br>
                    y Gestión de Prácticas Profesionales
                </p>
            </div>
        </div>

        <div style="z-index: 2;">
            <div class="p-3 rounded-3" style="background: rgba(255, 255, 255, 0.08); backdrop-filter: blur(5px); border: 1px solid rgba(255,255,255,0.12);">
                <div class="d-flex align-items-center gap-2 mb-1">
                    <i class="bi bi-shield-check text-info fs-5"></i>
                    <strong style="font-size: 0.88rem;">Entorno Académico Seguro</strong>
                </div>
                <small class="text-white-50" style="font-size: 0.76rem;">Ingeniería de Sistemas &bull; Sede Cochabamba &bull; Gestión 2026</small>
            </div>
        </div>
    </div>

    <!-- Right Form -->
    <div class="login-right-form">
        <!-- Logo en versión móvil -->
        <div class="d-md-none text-center mb-3">
            <img src="{{ asset('images/logo-upds-card.png') }}" alt="UPDS" style="max-height: 80px; width: auto;">
        </div>

        <div class="text-center text-md-start mb-4">
            <h3 class="fw-bold text-navy" style="color: var(--upds-navy);">Iniciar sesión</h3>
            <p class="text-muted" style="font-size: 0.9rem;">Accede al sistema de seguimiento de prácticas y vinculación.</p>
        </div>

        @if(isset($errors) && $errors->any())
            <div class="alert alert-danger py-2 px-3 mb-3" style="font-size: 0.85rem;">
                <i class="bi bi-exclamation-circle me-1"></i> {{ $errors->first() }}
            </div>
        @endif

        <form action="{{ route('login') }}" method="POST">
            @csrf
            <div class="mb-3">
                <label class="form-label text-muted small fw-semibold">Correo electrónico</label>
                <div class="input-group">
                    <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-envelope"></i></span>
                    <input type="email" name="email" class="form-control border-start-0 ps-0" placeholder="nombre.apellido@upds.edu.bo" value="{{ old('email') }}" required autofocus>
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label text-muted small fw-semibold">Contraseña</label>
                <div class="input-group">
                    <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-lock"></i></span>
                    <input type="password" id="passwordInput" name="password" class="form-control border-start-0 border-end-0 ps-0" placeholder="••••••••" required>
                    <span class="input-group-text bg-light border-start-0 text-muted" style="cursor: pointer;" onclick="togglePassword()"><i class="bi bi-eye" id="eyeIcon"></i></span>
                </div>
            </div>

            <div class="d-flex justify-content-between align-items-center mb-4">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="remember" id="rememberMe">
                    <label class="form-check-label text-muted small" for="rememberMe">Recordarme</label>
                </div>
                <a href="#" class="small text-decoration-none" style="color: var(--upds-primary);">¿Olvidaste tu contraseña?</a>
            </div>

            <button type="submit" class="btn btn-login w-100 mb-4">
                <i class="bi bi-box-arrow-in-right me-2"></i> Iniciar sesión
            </button>
        </form>

    </div>
</div>

<script>
    function togglePassword() {
        const input = document.getElementById('passwordInput');
        const icon = document.getElementById('eyeIcon');
        if (input.type === 'password') {
            input.type = 'text';
            icon.classList.replace('bi-eye', 'bi-eye-slash');
        } else {
            input.type = 'password';
            icon.classList.replace('bi-eye-slash', 'bi-eye');
        }
    }
</script>

</body>
</html>
