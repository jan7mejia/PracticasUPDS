<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\Carrera;
use App\Models\PeriodoAcademico;
use App\Models\TipoDocumento;
use App\Models\RequisitoAcademico;
use App\Models\OrganizacionReceptora;
use App\Models\Estudiante;
use App\Models\Coordinador;
use App\Models\DocenteTutor;
use App\Models\SolicitudPractica;
use App\Models\DocumentoSolicitud;
use App\Models\HistorialEstadoSolicitud;
use App\Models\Practica;
use App\Models\CronogramaActividad;
use App\Models\RegistroHora;
use App\Models\InformeAvance;
use App\Models\ObservacionTutor;
use App\Models\EvaluacionFinal;
use App\Models\EstudianteRequisito;

class DatabaseSeeder extends Seeder
{
    /**
     * DatabaseSeeder IDEMPOTENTE Y SEGURO.
     * NUNCA sobreescribe usuarios existentes, contraseñas ni evaluaciones de prueba.
     * Utiliza firstOrCreate en todas las entidades.
     */
    public function run(): void
    {
        // 1. Cargar catálogos base indispensables
        $this->call(CatalogosBaseSeeder::class);

        // Si ya existen usuarios en el sistema, no sobreescribir ni duplicar datos
        $demoPass = env('DEMO_USER_PASSWORD', '12345678');

        // Periodo por defecto
        $periodo = PeriodoAcademico::where('codigo', '2026-I')->first();
        $orgABC = OrganizacionReceptora::where('nombre', 'Empresa ABC S.R.L.')->first();
        $carreraSis = Carrera::where('codigo', 'SIS')->first();
        $requisitosIds = RequisitoAcademico::pluck('id')->toArray();
        $tipoDocMap = TipoDocumento::pluck('id', 'codigo')->toArray();

        // 2. Usuarios Base del Sistema (firstOrCreate protege contraseñas existentes)
        // A) Administrador
        $uAdmin = User::firstOrCreate(
            ['email' => 'admin@upds.edu.bo'],
            [
                'rol_id' => 1,
                'name' => 'Administrador General UPDS',
                'password' => Hash::make($demoPass),
                'activo' => true,
            ]
        );

        // B) Coordinador
        $uCoord = User::firstOrCreate(
            ['email' => 'coordinacion@upds.edu.bo'],
            [
                'rol_id' => 2,
                'name' => 'Lic. Carlos Meneses',
                'password' => Hash::make($demoPass),
                'activo' => true,
            ]
        );
        $coord = Coordinador::firstOrCreate(
            ['usuario_id' => $uCoord->id],
            [
                'nombres' => 'Carlos',
                'apellidos' => 'Meneses Quiroga',
                'telefono' => '70711223',
                'activo' => true,
            ]
        );

        // C) Tutores
        $uTutor1 = User::firstOrCreate(
            ['email' => 'tutor@upds.edu.bo'],
            [
                'rol_id' => 3,
                'name' => 'Ing. María Rodríguez',
                'password' => Hash::make($demoPass),
                'activo' => true,
            ]
        );
        $tutor1 = DocenteTutor::firstOrCreate(
            ['usuario_id' => $uTutor1->id],
            [
                'nombres' => 'María',
                'apellidos' => 'Rodríguez Soliz',
                'especialidad' => 'Desarrollo de Software y Gestión TI',
                'telefono' => '70788990',
                'activo' => true,
            ]
        );

        $uTutor2 = User::firstOrCreate(
            ['email' => 'roberto.calle@upds.edu.bo'],
            [
                'rol_id' => 3,
                'name' => 'Ing. Roberto Calle',
                'password' => Hash::make($demoPass),
                'activo' => true,
            ]
        );
        DocenteTutor::firstOrCreate(
            ['usuario_id' => $uTutor2->id],
            [
                'nombres' => 'Roberto',
                'apellidos' => 'Calle Flores',
                'especialidad' => 'Redes, Ciberseguridad y Telecomunicaciones',
                'telefono' => '71755443',
                'activo' => true,
            ]
        );

        // D) Estudiantes
        $estudiantesData = [
            [
                'email' => 'estudiante@upds.edu.bo',
                'name' => 'Juan Pérez Morales',
                'nombres' => 'Juan',
                'apellidos' => 'Pérez Morales',
                'ru' => '3200456',
                'ci' => '8493021 CB',
                'carrera_id' => $carreraSis->id ?? 1,
                'semestre' => 8,
                'telefono' => '79784512',
                'estado' => 'activo'
            ],
            [
                'email' => 'ana.gomez@upds.edu.bo',
                'name' => 'Ana Gómez Rojas',
                'nombres' => 'Ana',
                'apellidos' => 'Gómez Rojas',
                'ru' => '3200460',
                'ci' => '7829104 CB',
                'carrera_id' => $carreraSis->id ?? 1,
                'semestre' => 7,
                'telefono' => '76451298',
                'estado' => 'activo'
            ],
            [
                'email' => 'luis.torres@upds.edu.bo',
                'name' => 'Luis Torres Valdez',
                'nombres' => 'Luis',
                'apellidos' => 'Torres Valdez',
                'ru' => '3200465',
                'ci' => '6938472 CB',
                'carrera_id' => $carreraSis->id ?? 1,
                'semestre' => 9,
                'telefono' => '71239845',
                'estado' => 'activo'
            ],
            [
                'email' => 'maria.castro@upds.edu.bo',
                'name' => 'María Castro Soto',
                'nombres' => 'María',
                'apellidos' => 'Castro Soto',
                'ru' => '3200470',
                'ci' => '9182736 CB',
                'carrera_id' => $carreraSis->id ?? 1,
                'semestre' => 10,
                'telefono' => '75291834',
                'estado' => 'egresado'
            ],
        ];

        $estudiantesMap = [];
        foreach ($estudiantesData as $ed) {
            $uEst = User::firstOrCreate(
                ['email' => $ed['email']],
                [
                    'rol_id' => 4,
                    'name' => $ed['name'],
                    'password' => Hash::make($demoPass),
                    'activo' => true,
                ]
            );

            $est = Estudiante::firstOrCreate(
                ['usuario_id' => $uEst->id],
                [
                    'carrera_id' => $ed['carrera_id'],
                    'ru' => $ed['ru'],
                    'ci' => $ed['ci'],
                    'nombres' => $ed['nombres'],
                    'apellidos' => $ed['apellidos'],
                    'telefono' => $ed['telefono'],
                    'semestre' => $ed['semestre'],
                    'estado' => $ed['estado'],
                ]
            );
            $estudiantesMap[$ed['email']] = $est;

            foreach ($requisitosIds as $reqId) {
                EstudianteRequisito::firstOrCreate(
                    ['estudiante_id' => $est->id, 'requisito_id' => $reqId],
                    [
                        'estado' => 'cumple',
                        'observacion' => 'Validado con kardex académico oficial UPDS',
                        'verificado_por' => $uCoord->id,
                        'fecha_verificacion' => now(),
                    ]
                );
            }
        }

        // 3. Práctica de Demostración para Juan Pérez (Solo si no existe ya)
        $estJuan = $estudiantesMap['estudiante@upds.edu.bo'] ?? null;
        if ($estJuan && $orgABC && $periodo) {
            $solicitudJuan = SolicitudPractica::firstOrCreate(
                ['estudiante_id' => $estJuan->id, 'organizacion_id' => $orgABC->id],
                [
                    'periodo_id' => $periodo->id,
                    'fecha_solicitud' => '2026-03-01 09:30:00',
                    'estado' => 'aprobada',
                    'observacion' => 'Documentación completa. Cumple requisitos del 8vo semestre.',
                    'revisada_por_coordinador_id' => $coord->id,
                    'fecha_revision' => '2026-03-05 14:20:00',
                ]
            );

            HistorialEstadoSolicitud::firstOrCreate(
                ['solicitud_id' => $solicitudJuan->id, 'estado_nuevo' => 'aprobada'],
                [
                    'estado_anterior' => 'en_revision',
                    'motivo' => 'Aprobación definitiva de solicitud de prácticas por Coordinación',
                    'cambiado_por' => $uCoord->id,
                    'fecha_cambio' => '2026-03-05 14:20:00',
                ]
            );

            if (isset($tipoDocMap['plan_trabajo'])) {
                DocumentoSolicitud::firstOrCreate(
                    ['solicitud_id' => $solicitudJuan->id, 'tipo_documento_id' => $tipoDocMap['plan_trabajo']],
                    [
                        'nombre_original' => 'Plan de trabajo.pdf',
                        'ruta_privada' => 'solicitudes/' . $solicitudJuan->id . '/plan_de_trabajo.pdf',
                        'tipo_mime' => 'application/pdf',
                        'tamano_bytes' => 1048576,
                        'version_documento' => 1,
                        'estado' => 'validado',
                        'observacion_validacion' => 'Revisado y validado conforme al cronograma.',
                        'validado_por' => $uTutor1->id,
                        'fecha_validacion' => '2026-03-06 10:00:00',
                    ]
                );
            }

            if (isset($tipoDocMap['aceptacion'])) {
                DocumentoSolicitud::firstOrCreate(
                    ['solicitud_id' => $solicitudJuan->id, 'tipo_documento_id' => $tipoDocMap['aceptacion']],
                    [
                        'nombre_original' => 'Carta_Aceptacion_Empresa_ABC.pdf',
                        'ruta_privada' => 'solicitudes/' . $solicitudJuan->id . '/carta_aceptacion.pdf',
                        'tipo_mime' => 'application/pdf',
                        'tamano_bytes' => 524288,
                        'version_documento' => 1,
                        'estado' => 'validado',
                        'observacion_validacion' => 'Firma y sello autorizados por Gerencia.',
                        'validado_por' => $uCoord->id,
                        'fecha_validacion' => '2026-03-04 11:30:00',
                    ]
                );
            }

            $practicaJuan = Practica::firstOrCreate(
                ['solicitud_id' => $solicitudJuan->id],
                [
                    'coordinador_id' => $coord->id,
                    'tutor_id' => $tutor1->id,
                    'fecha_inicio' => '2026-03-10',
                    'fecha_fin_prevista' => '2026-12-31',
                    'horas_objetivo' => 240.00,
                    'estado' => 'en_curso',
                    'observacion_cierre' => null,
                ]
            );

            $act1 = CronogramaActividad::firstOrCreate(
                ['practica_id' => $practicaJuan->id, 'titulo' => 'Análisis de requerimientos y diseño de base de datos'],
                [
                    'objetivo' => 'Modelar y documentar la arquitectura de datos del nuevo módulo de clientes',
                    'descripcion' => 'Reuniones con el equipo técnico y elaboración del modelo relacional.',
                    'fecha_inicio' => '2026-03-10',
                    'fecha_fin' => '2026-03-31',
                    'fecha_completada' => '2026-03-31',
                    'horas_planificadas' => 60.00,
                    'estado' => 'completada',
                    'observacion' => 'Completada a satisfacción según tutor.',
                ]
            );

            $act2 = CronogramaActividad::firstOrCreate(
                ['practica_id' => $practicaJuan->id, 'titulo' => 'Desarrollo de servicios API e interfaces web'],
                [
                    'objetivo' => 'Implementar endpoints REST y vistas Blade con Bootstrap',
                    'descripcion' => 'Programación de controladores, validaciones y conexión con frontend.',
                    'fecha_inicio' => '2026-04-01',
                    'fecha_fin' => '2026-05-15',
                    'horas_planificadas' => 80.00,
                    'estado' => 'en_proceso',
                    'observacion' => 'En desarrollo continuo.',
                ]
            );

            // Informes de avance
            InformeAvance::firstOrCreate(
                ['practica_id' => $practicaJuan->id, 'titulo' => 'Reporte N° 1 - 28/03/2026'],
                [
                    'periodo_inicio' => '2026-03-10',
                    'periodo_fin' => '2026-03-28',
                    'resumen' => 'Conclusión exitosa de la etapa de análisis y modelado de datos.',
                    'estado' => 'revisado',
                    'fecha_envio' => '2026-03-29 18:00:00',
                    'revisado_por_tutor_id' => $tutor1->id,
                    'fecha_revision' => '2026-03-30 09:15:00',
                ]
            );

            InformeAvance::firstOrCreate(
                ['practica_id' => $practicaJuan->id, 'titulo' => 'Reporte N° 2 - 11/04/2026'],
                [
                    'periodo_inicio' => '2026-03-29',
                    'periodo_fin' => '2026-04-11',
                    'resumen' => 'Avance en la programación de vistas y servicios backend.',
                    'estado' => 'revisado',
                    'fecha_envio' => '2026-04-12 19:30:00',
                    'revisado_por_tutor_id' => $tutor1->id,
                    'fecha_revision' => '2026-04-13 11:00:00',
                ]
            );

            ObservacionTutor::firstOrCreate(
                ['practica_id' => $practicaJuan->id, 'detalle' => 'El estudiante cumple con las actividades asignadas y muestra buen desempeño y puntualidad en sus entregas.'],
                [
                    'tutor_id' => $tutor1->id,
                    'fecha' => '2026-04-15 10:30:00',
                ]
            );
        }

        // 4. Solicitud y Práctica concluida de Luis Torres
        $estLuis = $estudiantesMap['luis.torres@upds.edu.bo'] ?? null;
        $orgGAMC = OrganizacionReceptora::where('nombre', 'Gobierno Autónomo Municipal de Cochabamba')->first();

        if ($estLuis && $orgGAMC && $periodo) {
            $solLuis = SolicitudPractica::firstOrCreate(
                ['estudiante_id' => $estLuis->id, 'organizacion_id' => $orgGAMC->id],
                [
                    'periodo_id' => $periodo->id,git add .
                    'fecha_solicitud' => '2026-01-10 08:30:00',
                    'estado' => 'aprobada',
                    'observacion' => 'Práctica completada con honores.',
                    'revisada_por_coordinador_id' => $coord->id,
                    'fecha_revision' => '2026-01-12 11:00:00',
                ]
            );

            $pracLuis = Practica::firstOrCreate(
                ['solicitud_id' => $solLuis->id],
                [
                    'coordinador_id' => $coord->id,
                    'tutor_id' => $tutor1->id,
                    'fecha_inicio' => '2026-01-15',
                    'fecha_fin_prevista' => '2026-04-15',
                    'fecha_fin_real' => '2026-04-15',
                    'horas_objetivo' => 240.00,
                    'estado' => 'concluida',
                    'observacion_cierre' => 'Cumplimiento del 100% de horas y objetivos con excelencia.',
                ]
            );

            EvaluacionFinal::firstOrCreate(
                ['practica_id' => $pracLuis->id],
                [
                    'tutor_id' => $tutor1->id,
                    'fecha' => '2026-04-16',
                    'calificacion' => 95.00,
                    'resultado' => 'Aprobado',
                    'comentario' => 'Excelente desempeño profesional y alto compromiso institucional.',
                ]
            );
        }
    }
}
