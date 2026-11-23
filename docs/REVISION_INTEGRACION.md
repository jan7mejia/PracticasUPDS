# REVISIÓN DE INTEGRACIÓN Y AUTOMATIZACIÓN DE PRÁCTICAS PROFESIONALES
## Sistema GestionPracticasUPDS — Universidad Privada Domingo Savio (Sede Cochabamba)
**Fecha:** 20 de Noviembre de 2026  
**Proyecto:** Proyecto Final de Ingeniería de Software II  

---

## 1. RESUMEN EJECUTIVO
Se implementó de manera integral y segura la transición hacia el modelo automatizado de seguimiento y evaluación de prácticas profesionales (P0, P1 y P2), garantizando la **protección absoluta de la base de datos de producción** (`gestion_practicas_upds_final`), la **conservación íntegra de registros históricos** (Juan Pérez #1 y Luis Torres #2) y la no sobreescritura de contraseñas de las 4 cuentas institucionales.

Todas las pruebas automatizadas se ejecutaron exclusivamente en un entorno aislado (`gp_upds_testing`).

---

## 2. ESTADO DE IMPLEMENTACIÓN POR ETAPAS

### P0 — Protección y Reglas Principales (100% COMPLETADO)
- **Copia de seguridad verificable:**
  - Dump SQL completo generado en `storage/backups/gestion_practicas_upds_final_20261120_backup.sql` (162.7 KB).
  - Respaldo de expedientes y documentos privados en `storage/backups/app_files_backup/`.
- **Protección de DatabaseSeeder:**
  - Separación de catálogos indispensables en `database/seeders/CatalogosBaseSeeder.php`.
  - Reescritura de `database/seeders/DatabaseSeeder.php` sustituyendo `updateOrCreate` por `firstOrCreate`.
  - Protección de hashes de contraseñas y datos reales de prueba frente a ejecuciones accidentales del seeder.
- **Compatibilidad histórica (v1 vs v2):**
  - Prácticas existentes marcadas con `version_reglamento = 'v1_historico'`. Mantienen sus 240h objetivo y registros de horas sin bloqueos retroactivos.
- **Duración y asistencia en nuevas prácticas:**
  - Duración de exactamente 3 meses calendario calculada automáticamente (`calcularFechaFin3Meses`).
  - Cómputo de horas requeridas = Días hábiles (Lunes a Viernes) × 4.0 horas/día (`calcularHorasRequeridasV2`).
  - Límite diario estricto de 4 horas por jornada (`config/practicas.php`), validado en servidor y protegido contra concurrencia con `lockForUpdate()`.
- **Excepciones y control de incumplimiento:**
  - Tabla `justificaciones_asistencia` para gestionar faltas (0h) y jornadas incompletas (1h a 3.5h) con motivo y evidencia física.
  - Límite máximo de 2 faltas y 2 incompletas autorizadas por Coordinación.
  - Generación automática de incidencia grave impeditiva en `incidencias_practica` ante la 3ra falta o 3ra incompleta.
- **Cronograma del tutor y aprobación:**
  - Planificación de actividades por el Tutor asignado y por Coordinación con fecha de inicio, fecha fin (dentro del periodo de 3 meses), objetivo y entregable formal.
  - Aprobación formal del cronograma por Coordinación Académica (`aprobarCronograma`), requerida antes de habilitar el registro de horas del estudiante en prácticas nuevas.

### P1 — Automatización Académica (100% COMPLETADO)
- **Informes vinculados a actividades:**
  - Campo `actividad_id` y `version` en `informes_avance` para control de versiones en entregas periódicas.
  - Observaciones del tutor y calificaciones visibles al practicante.
- **Rúbricas de evaluación por actividad:**
  - Tablas `rubricas_actividad` y `criterios_rubrica`.
  - Regla estricta en servidor: la suma de las ponderaciones de los criterios debe ser exactamente 100%.
  - Registro de evaluaciones por criterio (`evaluaciones_criterio`) exclusivo del docente tutor asignado.
  - Cálculo automático de la nota de la actividad mediante promedio ponderado:
    $$\text{Nota} = \sum \left( \text{Puntuación} \times \frac{\text{Ponderación}}{100} \right)$$
  - Determinación automática del resultado («Aprobado» / «Reprobado») y cambio de estado de la actividad a «Completada» sin intervención manual arbitraria.
  - Cálculo automático de la nota final del tutor una vez completadas las rúbricas del cronograma.

### P2 — Integración y Cierre Institucional (100% COMPLETADO)
- **Valoración de la organización receptora:**
  - Registro de valoración proveniente del responsable institucional identificado de la empresa, fecha de recepción, desempeño, comentarios y evidencia adjunta (`EvaluacionOrganizacion`).
- **Cierre institucional exclusivo de Coordinación:**
  - Validación en servidor de todos los requisitos concurrentes para declarar «Concluida» una práctica:
    1. Horas reales validadas $\ge 90\%$ (en v2) o $100\%$ (en v1 histórico).
    2. Máximo 2 faltas justificadas respetado.
    3. Máximo 2 jornadas incompletas justificadas respetado.
    4. Cero incidencias graves impeditivas pendientes de resolución.
    5. Evaluación final del tutor registrada con resultado aprobatorio.
    6. Informes de avance revisados y conformes.
    7. Valoración de la organización receptora registrada.
- **Auditoría y trazabilidad:**
  - Registro histórico transaccional en `historial_estados_practica` para cada cambio de estado, aprobación, abandono o conclusión.

---

## 3. ARCHIVOS MODIFICADOS Y CREADOS

### Archivos Creados
1. `database/migrations/2026_11_20_000001_add_sistema_integral_practicas_p0_p1_p2.php` (Migración incremental no destructiva).
2. `database/seeders/CatalogosBaseSeeder.php` (Catálogos institucionales con `firstOrCreate`).
3. `app/Models/JustificacionAsistencia.php` (Modelo Eloquent para justificaciones de asistencia).
4. `app/Models/IncidenciaPractica.php` (Modelo Eloquent para incidencias impeditivas).
5. `app/Models/RubricaActividad.php` (Modelo Eloquent para rúbricas).
6. `app/Models/CriterioRubrica.php` (Modelo Eloquent para criterios con ponderación).
7. `app/Models/EvaluacionCriterio.php` (Modelo Eloquent para calificaciones por criterio).
8. `tests/Feature/IntegracionSistemaPracticasTest.php` (Suite completa de 13 pruebas Feature automatizadas).
9. `storage/backups/gestion_practicas_upds_final_20261120_backup.sql` (Respaldo verificado de MySQL).
10. `storage/backups/app_files_backup/` (Respaldo de documentos físicos de solicitudes e informes).
11. `docs/REVISION_INTEGRACION.md` (Este informe técnico).

### Archivos Modificados
1. `database/seeders/DatabaseSeeder.php` (Totalmente idempotente y seguro).
2. `config/practicas.php` (Parámetros centralizados de faltas, incompletas, porcentaje mínimo y 4h).
3. `config/database.php` (Conexión segura `testing` hacia `gp_upds_testing`).
4. `phpunit.xml` (Aislamiento de tests en base de pruebas `gp_upds_testing`).
5. `app/Models/Practica.php` (Relaciones, métodos de cálculo de días hábiles, 3 meses, nota final y compatibilidad v1/v2).
6. `app/Models/CronogramaActividad.php` (Relación con rúbricas, entregable esperado y cálculo automático de nota).
7. `app/Models/InformeAvance.php` (Relación con actividad del cronograma, versionado y calificaciones).
8. `app/Http/Controllers/SolicitudController.php` (Creación de prácticas v2 con 3 meses y horas según días hábiles).
9. `app/Http/Controllers/SeguimientoController.php` (Endpoints de cronograma, justificaciones, incidencias, rúbricas y cierre estricto).
10. `routes/web.php` (Rutas seguras para rúbricas, justificaciones, incidencias y aprobación de cronograma).
11. `resources/views/seguimiento/show.blade.php` (Integración visual en Blade de contadores, rúbricas, justificaciones y modales).

---

## 4. TABLAS Y CAMPOS AÑADIDOS EN MYSQL

| Tabla | Tipo de Modificación | Campo / Detalle |
| :--- | :--- | :--- |
| `practicas` | Columnas incrementales | `version_reglamento`, `cronograma_aprobado`, `cronograma_aprobado_at`, `cronograma_aprobado_por`, `horas_programadas`, `faltas_justificadas`, `incompletas_justificadas` |
| `cronograma_actividades` | Columnas incrementales | `entregable_esperado`, `aprobada_coordinacion`, `rubrica_definida` |
| `informes_avance` | Columnas incrementales | `actividad_id`, `version`, `observaciones_tutor`, `calificacion` |
| `justificaciones_asistencia` | **Nueva Tabla** | ID, practica_id, estudiante_id, fecha, tipo (falta/incompleta), horas_reales, motivo, ruta_documento, estado, resolucion, revisada_por |
| `incidencias_practica` | **Nueva Tabla** | ID, practica_id, tipo (tercera_falta, etc.), descripcion, bloquea_cierre, resuelta, resolucion, registrada_por |
| `rubricas_actividad` | **Nueva Tabla** | ID, actividad_id, titulo, descripcion |
| `criterios_rubrica` | **Nueva Tabla** | ID, rubrica_id, nombre, descripcion, ponderacion, puntuacion_maxima |
| `evaluaciones_criterio` | **Nueva Tabla** | ID, criterio_id, informe_avance_id, practica_id, tutor_id, puntuacion, comentario |

---

## 5. REGISTROS HISTÓRICOS CONSERVADOS

- **Práctica #1 — Juan Pérez Morales (`estudiante@upds.edu.bo`):**
  - Estado: `en_curso`.
  - Horas objetivo: `240.00h`.
  - Horas validadas: `122.00h` (50.8%).
  - Versión asignada: `v1_historico`.
  - Cronograma aprobado: `true`.
  - Expedientes, informes y bitácora intactos.
- **Práctica #2 — Luis Torres Valdez (`luis.torres@upds.edu.bo`):**
  - Estado: `concluida`.
  - Horas objetivo: `240.00h`.
  - Evaluación final original: `95.00 / Aprobado`.
  - Versión asignada: `v1_historico`.

---

## 6. PRUEBAS AUTOMATIZADAS EJECUTADAS

Se ejecutó la suite Feature en `gp_upds_testing` con resultado exitoso:
```text
PHPUnit 10.5.66 by Sebastian Bergmann and contributors.
Runtime:       PHP 8.1.10
Configuration: C:\laragon\www\GestionPracticasUPDSl\phpunit.xml

.............                                                     13 / 13 (100%)

Time: 00:07.498, Memory: 36.00 MB
OK (13 tests, 48 assertions)
```

Suite específica de mejoras `test_rf11_rf13_rf14_mejoras.php`:
```text
════════════════════════════════════════════════════════════════════════
  RESULTADO: 11 pasaron | 0 fallaron (100% PASS)
════════════════════════════════════════════════════════════════════════
```

---

## 7. CREDENCIALES DE ACCESO CONFIRMADAS
Todas las credenciales institucionales se encuentran funcionales y verificadas:
- **Administrador:** `admin@upds.edu.bo` / `12345678`
- **Coordinador Académico:** `coordinacion@upds.edu.bo` / `12345678`
- **Docente Tutor:** `tutor@upds.edu.bo` / `12345678`
- **Estudiante:** `estudiante@upds.edu.bo` / `12345678`

