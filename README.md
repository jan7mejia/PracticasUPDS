# Sistema de Gestión de Prácticas Profesionales y Seguimiento de Estudiantes - UPDS

Sistema web institucional desarrollado para la **Universidad Privada Domingo Savio (UPDS)**, Sede Cochabamba, para la materia **Ingeniería de Software II**.

El sistema administra integralmente el ciclo de vida de las prácticas profesionales y la vinculación empresarial, con cuatro roles de usuario claramente diferenciados, políticas de autorización estrictas, flujos de validación en tiempo real y persistencia en base de datos MySQL mediante Eloquent ORM.

---

## 👥 Roles del Sistema

### 1. 🎓 Rol Estudiante
- Consulta de empresas/organizaciones receptoras con convenio vigente.
- Solicitud de prácticas profesionales adjuntando documentación (Carta de Aceptación, Plan de Trabajo).
- Registro diario y control de horas de práctica en bitácora digital con cálculo de avance (acumulado sobre meta de horas).
- Entrega de informes periódicos y finales en formato PDF.
- Consulta de evaluaciones de desempeño emitidas por el tutor académico y el tutor institucional.
- Gestión de perfil de estudiante.

### 2. 👨‍🏫 Rol Docente Tutor
- Supervisión académica directa de los estudiantes asignados.
- Revisión, aprobación, observación y validación de bitácoras de horas en tiempo real.
- Registro de observaciones pedagógicas y de desempeño.
- Calificación y evaluación cuantitativa/cualitativa de prácticas profesionales.
- Generación de reportes de seguimiento académico.

### 3. 📋 Rol Coordinador Académico
- Gestión de convenios con organizaciones e instituciones receptoras.
- Revisión y aprobación formal de solicitudes de prácticas profesionales.
- Asignación oficial de docentes tutores académicos a los estudiantes aprobados.
- Validación y control de requisitos académicos mínimos por carrera.
- Supervisión global de bitácoras, evaluaciones y actas de cierre.
- Generación de reportes estadísticos, nóminas y actas institucionales.

### 4. ⚙️ Rol Administrador
- Dashboard administrativo con métricas globales del sistema.
- Gestión integral de usuarios (Estudiantes, Tutores, Coordinadores, Administradores).
- Activación, desactivación y control de acceso (sesión terminada en tiempo real para cuentas inactivas).
- Restablecimiento seguro de contraseñas de acceso.
- Administración de periodos académicos y catálogo de carreras universitarias.
- Módulo de auditoría de acciones administrativas con registro de IP, fecha y eventos.
- Separación estricta de responsabilidades (principio de menor privilegio: no interviene en evaluaciones ni aprobaciones académicas).

---

## 🛠️ Tecnologías y Arquitectura

- **Framework:** Laravel 10 (PHP 8.1+)
- **Base de Datos:** MySQL / MariaDB con Eloquent ORM
- **Frontend:** Blade Templates, Bootstrap 5, Bootstrap Icons, Vite
- **Arquitectura:** MVC (Model-View-Controller), Middleware de Roles (`CheckRole`), Form Requests y Policies
- **Entorno:** Compatible con Laragon / XAMPP en Windows y Linux

---

## 🚀 Instalación y Configuración

1. **Clonar el repositorio:**
   ```bash
   git clone https://github.com/jan7mejia/PracticasUPDS.git
   cd PracticasUPDS
   ```

2. **Instalar dependencias de PHP y JavaScript:**
   ```bash
   composer install
   npm install && npm run build
   ```

3. **Configurar el entorno:**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```
   *Configura los datos de conexión a MySQL en el archivo `.env`:*
   ```env
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=gestion_practicas_upds
   DB_USERNAME=root
   DB_PASSWORD=
   ```

4. **Ejecutar migraciones y datos iniciales:**
   ```bash
   php artisan migrate --seed
   ```

5. **Iniciar el servidor:**
   ```bash
   php artisan serve
   ```
   O acceder mediante Laragon: `http://localhost/GestionPracticasUPDSl/public`

---

## 🧪 Pruebas Automatizadas

El sistema cuenta con suites de pruebas completas:
```bash
# Pruebas con PHPUnit / Artisan
php artisan test

# Suite de pruebas específicas
php test_admin_suite.php
php test_coordinador_suite.php
php test_regression_roles.php
```

---

## 🏛️ Universidad Privada Domingo Savio (UPDS)
**Sede Cochabamba**  
Ingeniería de Software II • Gestión 2026
