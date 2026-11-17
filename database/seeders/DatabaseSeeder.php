<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use App\Models\Role;
use App\Models\User;
use App\Models\Carrera;
use App\Models\PeriodoAcademico;
use App\Models\TipoDocumento;
use App\Models\RequisitoAcademico;
use App\Models\OrganizacionReceptora;
use App\Models\ResponsableOrganizacion;
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
    public function run(): void
    {
        // 1. Roles
        $rolesData = [
            ['id' => 1, 'codigo' => 'administrador', 'nombre' => 'Administrador', 'descripcion' => 'Gestión general de usuarios y parámetros'],
            ['id' => 2, 'codigo' => 'coordinador', 'nombre' => 'Coordinador académico', 'descripcion' => 'Gestión de solicitudes y prácticas'],
            ['id' => 3, 'codigo' => 'tutor', 'nombre' => 'Docente tutor', 'descripcion' => 'Seguimiento y evaluación de estudiantes'],
            ['id' => 4, 'codigo' => 'estudiante', 'nombre' => 'Estudiante', 'descripcion' => 'Solicitudes, informes y consulta de sus prácticas'],
            ['id' => 5, 'codigo' => 'responsable_organizacion', 'nombre' => 'Responsable de organización', 'descripcion' => 'Rol opcional de validación empresarial'],
        ];
        foreach ($rolesData as $r) {
            Role::updateOrCreate(['id' => $r['id']], $r);
        }

        // 2. Carreras
        $carreras = [
            ['codigo' => 'SIS', 'nombre' => 'Ingeniería de Sistemas', 'activa' => true],
            ['codigo' => 'RED', 'nombre' => 'Ing. en Redes y Telecomunicaciones', 'activa' => true],
            ['codigo' => 'ADM', 'nombre' => 'Administración de Empresas', 'activa' => true],
            ['codigo' => 'COM', 'nombre' => 'Ingeniería Comercial', 'activa' => true],
            ['codigo' => 'DER', 'nombre' => 'Derecho', 'activa' => true],
        ];
        $carreraMap = [];
        foreach ($carreras as $c) {
            $created = Carrera::updateOrCreate(['codigo' => $c['codigo']], $c);
            $carreraMap[$c['codigo']] = $created->id;
        }

        // 3. Periodos
        $periodo = PeriodoAcademico::updateOrCreate(
            ['codigo' => '2026-I'],
            [
                'nombre' => 'Gestión Académica I/2026',
                'fecha_inicio' => '2026-02-01',
                'fecha_fin' => '2026-06-30',
                'activo' => true
            ]
        );
        $periodo2 = PeriodoAcademico::updateOrCreate(
            ['codigo' => '2026-II'],
            [
                'nombre' => 'Gestión Académica II/2026',
                'fecha_inicio' => '2026-08-01',
                'fecha_fin' => '2026-12-31',
                'activo' => false
            ]
        );

        // 4. Tipos de documento
        $tiposDoc = [
            ['codigo' => 'solicitud', 'nombre' => 'Carta de solicitud de práctica', 'obligatorio' => true, 'activo' => true],
            ['codigo' => 'convenio', 'nombre' => 'Convenio interinstitucional', 'obligatorio' => true, 'activo' => true],
            ['codigo' => 'aceptacion', 'nombre' => 'Carta de aceptación empresarial', 'obligatorio' => true, 'activo' => true],
            ['codigo' => 'plan_trabajo', 'nombre' => 'Plan de trabajo de práctica', 'obligatorio' => true, 'activo' => true],
            ['codigo' => 'informe_avance', 'nombre' => 'Informe de avance periódico', 'obligatorio' => false, 'activo' => true],
            ['codigo' => 'evaluacion_empresa', 'nombre' => 'Evaluación empresarial de desempeño', 'obligatorio' => false, 'activo' => true],
            ['codigo' => 'otro', 'nombre' => 'Otro documento complementario', 'obligatorio' => false, 'activo' => true],
        ];
        $tipoDocMap = [];
        foreach ($tiposDoc as $td) {
            $created = TipoDocumento::updateOrCreate(['codigo' => $td['codigo']], $td);
            $tipoDocMap[$td['codigo']] = $created->id;
        }

        // 5. Requisitos académicos
        $reqs = [
            ['nombre' => '7mo Semestre vencido o superior', 'descripcion' => 'Tener aprobado hasta el 6to semestre regular.', 'obligatorio' => true],
            ['nombre' => 'Materias de especialidad aprobadas', 'descripcion' => 'Acreditar aprobación de materias troncales de carrera.', 'obligatorio' => true],
            ['nombre' => 'Seguro universitario vigente', 'descripcion' => 'Cobertura médica y de accidentes estudiantil al día.', 'obligatorio' => true],
            ['nombre' => 'Sin deudas administrativas', 'descripcion' => 'Solvencia económica y académica institucional UPDS.', 'obligatorio' => true],
        ];
        $requisitosIds = [];
        foreach ($reqs as $rq) {
            $created = RequisitoAcademico::updateOrCreate(['nombre' => $rq['nombre']], $rq + ['activo' => true]);
            $requisitosIds[] = $created->id;
        }

        // 6. Organizaciones receptoras
        $orgs = [
            [
                'nombre' => 'Empresa ABC S.R.L.',
                'nit' => '1234567890123',
                'rubro' => 'Tecnología y Software',
                'direccion' => 'Av. Principal N° 123, Cochabamba',
                'telefono' => '77012345',
                'correo' => 'contacto@empresaabc.com',
                'estado' => 'activa',
                'responsable' => ['nombres' => 'Carlos', 'apellidos' => 'Méndez', 'cargo' => 'Gerente de Tecnología', 'correo' => 'cmendez@empresaabc.com', 'telefono' => '77012345']
            ],
            [
                'nombre' => 'Fundación Educación para Todos',
                'nit' => '9876543210123',
                'rubro' => 'ONG / Desarrollo Social',
                'direccion' => 'Calle Sucre N° 450, Cochabamba',
                'telefono' => '44215689',
                'correo' => 'info@educacionparatodos.org',
                'estado' => 'activa',
                'responsable' => ['nombres' => 'Patricia', 'apellidos' => 'Suárez', 'cargo' => 'Directora de Proyectos', 'correo' => 'psuarez@educacionparatodos.org', 'telefono' => '44215689']
            ],
            [
                'nombre' => 'Gobierno Autónomo Municipal de Cochabamba',
                'nit' => '1020304050607',
                'rubro' => 'Sector Público',
                'direccion' => 'Plaza 14 de Septiembre, Cochabamba',
                'telefono' => '4258900',
                'correo' => 'sistemas@cochabamba.bo',
                'estado' => 'activa',
                'responsable' => ['nombres' => 'Ing. Fernando', 'apellidos' => 'Guzmán', 'cargo' => 'Jefe de Sistemas', 'correo' => 'fguzman@cochabamba.bo', 'telefono' => '70712399']
            ],
            [
                'nombre' => 'Hospital Regional',
                'nit' => '5566778899001',
                'rubro' => 'Salud y Biomedicina',
                'direccion' => 'Av. Aniceto Arce N° 820',
                'telefono' => '4502300',
                'correo' => 'rrhh@hospitalregional.gob.bo',
                'estado' => 'activa',
                'responsable' => ['nombres' => 'Dra. Elena', 'apellidos' => 'Torrico', 'cargo' => 'Responsable de Capacitación', 'correo' => 'etorrico@hospitalregional.gob.bo', 'telefono' => '71728394']
            ],
            [
                'nombre' => 'Banco Unión S.A.',
                'nit' => '1015243689012',
                'rubro' => 'Banca y Servicios Financieros',
                'direccion' => 'Av. Ballivián N° 567 (El Prado)',
                'telefono' => '4259000',
                'correo' => 'talento@bancounion.com.bo',
                'estado' => 'activa',
                'responsable' => ['nombres' => 'Lic. Ramiro', 'apellidos' => 'Vargas', 'cargo' => 'Subgerente de Talento Humano', 'correo' => 'rvargas@bancounion.com.bo', 'telefono' => '72233445']
            ],
        ];

        $orgMap = [];
        foreach ($orgs as $oData) {
            $respData = $oData['responsable'];
            unset($oData['responsable']);
            $org = OrganizacionReceptora::updateOrCreate(['nombre' => $oData['nombre']], $oData);
            $orgMap[$org->nombre] = $org->id;

            ResponsableOrganizacion::updateOrCreate(
                ['organizacion_id' => $org->id, 'correo' => $respData['correo']],
                $respData + ['organizacion_id' => $org->id, 'activo' => true]
            );
        }

        // 7. Usuarios: Admin, Coordinador, Docente Tutor, Estudiantes
        // A) Admin
        $uAdmin = User::updateOrCreate(
            ['email' => 'admin@upds.edu.bo'],
            [
                'rol_id' => 1,
                'name' => 'Administrador General UPDS',
                'password' => Hash::make('12345678'),
                'activo' => true,
            ]
        );

        // B) Coordinador
        $uCoord = User::updateOrCreate(
            ['email' => 'coordinacion@upds.edu.bo'],
            [
                'rol_id' => 2,
                'name' => 'Lic. Carlos Meneses',
                'password' => Hash::make('12345678'),
                'activo' => true,
            ]
        );
        $coord = Coordinador::updateOrCreate(
            ['usuario_id' => $uCoord->id],
            [
                'nombres' => 'Carlos',
                'apellidos' => 'Meneses Quiroga',
                'telefono' => '70711223',
                'activo' => true,
            ]
        );

        // C) Tutores
        $uTutor1 = User::updateOrCreate(
            ['email' => 'tutor@upds.edu.bo'],
            [
                'rol_id' => 3,
                'name' => 'Ing. María Rodríguez',
                'password' => Hash::make('12345678'),
                'activo' => true,
            ]
        );
        $tutor1 = DocenteTutor::updateOrCreate(
            ['usuario_id' => $uTutor1->id],
            [
                'nombres' => 'María',
                'apellidos' => 'Rodríguez Soliz',
                'especialidad' => 'Desarrollo de Software y Gestión TI',
                'telefono' => '70788990',
                'activo' => true,
            ]
        );

        $uTutor2 = User::updateOrCreate(
            ['email' => 'roberto.calle@upds.edu.bo'],
            [
                'rol_id' => 3,
                'name' => 'Ing. Roberto Calle',
                'password' => Hash::make('12345678'),
                'activo' => true,
            ]
        );
        $tutor2 = DocenteTutor::updateOrCreate(
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
                'carrera_codigo' => 'SIS',
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
                'carrera_codigo' => 'SIS',
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
                'carrera_codigo' => 'SIS',
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
                'carrera_codigo' => 'SIS',
                'semestre' => 10,
                'telefono' => '75291834',
                'estado' => 'egresado'
            ],
        ];

        $estudiantesMap = [];
        foreach ($estudiantesData as $ed) {
            $uEst = User::updateOrCreate(
                ['email' => $ed['email']],
                [
                    'rol_id' => 4,
                    'name' => $ed['name'],
                    'password' => Hash::make('12345678'),
                    'activo' => true,
                ]
            );

            $est = Estudiante::updateOrCreate(
                ['usuario_id' => $uEst->id],
                [
                    'carrera_id' => $carreraMap[$ed['carrera_codigo']],
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

            // Asignar cumplimiento de requisitos
            foreach ($requisitosIds as $reqId) {
                EstudianteRequisito::updateOrCreate(
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

        // 8. Solicitudes y Prácticas para Juan Pérez (El estudiante del mockup)
        $estJuan = $estudiantesMap['estudiante@upds.edu.bo'];
        $orgABCId = $orgMap['Empresa ABC S.R.L.'];

        $solicitudJuan = SolicitudPractica::updateOrCreate(
            ['estudiante_id' => $estJuan->id, 'organizacion_id' => $orgABCId],
            [
                'periodo_id' => $periodo->id,
                'fecha_solicitud' => '2026-03-01 09:30:00',
                'estado' => 'aprobada',
                'observacion' => 'Documentación completa. Cumple requisitos del 8vo semestre.',
                'revisada_por_coordinador_id' => $coord->id,
                'fecha_revision' => '2026-03-05 14:20:00',
            ]
        );

        HistorialEstadoSolicitud::updateOrCreate(
            ['solicitud_id' => $solicitudJuan->id, 'estado_nuevo' => 'aprobada'],
            [
                'estado_anterior' => 'en_revision',
                'motivo' => 'Aprobación definitiva de solicitud de prácticas por Coordinación',
                'cambiado_por' => $uCoord->id,
                'fecha_cambio' => '2026-03-05 14:20:00',
            ]
        );

        // Documentos de Juan
        DocumentoSolicitud::updateOrCreate(
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

        DocumentoSolicitud::updateOrCreate(
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

        // Práctica de Juan (240 horas objetivo, en seguimiento con 120 horas validadas = 50%)
        $practicaJuan = Practica::updateOrCreate(
            ['solicitud_id' => $solicitudJuan->id],
            [
                'coordinador_id' => $coord->id,
                'tutor_id' => $tutor1->id,
                'fecha_inicio' => '2026-03-10',
                'fecha_fin_prevista' => '2026-06-15',
                'horas_objetivo' => 240.00,
                'estado' => 'en_curso',
                'observacion_cierre' => null,
            ]
        );

        // Cronograma de actividades para Juan
        $act1 = CronogramaActividad::updateOrCreate(
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

        $act2 = CronogramaActividad::updateOrCreate(
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

        // Registros de horas (suman 120 horas validadas)
        $horasData = [
            ['fecha' => '2026-03-14', 'horas' => 20.00, 'desc' => 'Levantamiento de especificaciones técnicas con clientes'],
            ['fecha' => '2026-03-21', 'horas' => 20.00, 'desc' => 'Diseño de diagramas de entidad-relación y casos de uso'],
            ['fecha' => '2026-03-28', 'horas' => 20.00, 'desc' => 'Modelado físico en MySQL e implementación de migraciones'],
            ['fecha' => '2026-04-07', 'horas' => 20.00, 'desc' => 'Construcción de módulos de autenticación y roles de usuario'],
            ['fecha' => '2026-04-14', 'horas' => 20.00, 'desc' => 'Desarrollo de controladores y endpoints de consultas'],
            ['fecha' => '2026-04-21', 'horas' => 20.00, 'desc' => 'Integración de vistas Blade y pruebas unitarias'],
        ];

        foreach ($horasData as $hd) {
            RegistroHora::updateOrCreate(
                ['practica_id' => $practicaJuan->id, 'fecha' => $hd['fecha']],
                [
                    'actividad_id' => $act1->id,
                    'horas' => $hd['horas'],
                    'descripcion' => $hd['desc'],
                    'estado' => 'validada',
                    'revisado_por_tutor_id' => $tutor1->id,
                    'fecha_revision' => date('Y-m-d H:i:s', strtotime($hd['fecha'] . ' +1 day')),
                    'observacion_revision' => 'Horas verificadas y aprobadas por tutor.',
                ]
            );
        }

        // Informes de avance para Juan
        InformeAvance::updateOrCreate(
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

        InformeAvance::updateOrCreate(
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

        // Observaciones del tutor para Juan
        ObservacionTutor::updateOrCreate(
            ['practica_id' => $practicaJuan->id, 'detalle' => 'El estudiante cumple con las actividades asignadas y muestra buen desempeño y puntualidad en sus entregas.'],
            [
                'tutor_id' => $tutor1->id,
                'fecha' => '2026-04-15 10:30:00',
            ]
        );

        // 9. Otras solicitudes (Ana Gómez, Luis Torres, María Castro)
        $estAna = $estudiantesMap['ana.gomez@upds.edu.bo'];
        SolicitudPractica::updateOrCreate(
            ['estudiante_id' => $estAna->id, 'organizacion_id' => $orgMap['Banco Unión S.A.']],
            [
                'periodo_id' => $periodo->id,
                'fecha_solicitud' => '2026-03-15 10:00:00',
                'estado' => 'en_revision',
                'observacion' => 'Pendiente de confirmación de convenio por parte de la empresa.',
            ]
        );

        $estLuis = $estudiantesMap['luis.torres@upds.edu.bo'];
        $solLuis = SolicitudPractica::updateOrCreate(
            ['estudiante_id' => $estLuis->id, 'organizacion_id' => $orgMap['Gobierno Autónomo Municipal de Cochabamba']],
            [
                'periodo_id' => $periodo->id,
                'fecha_solicitud' => '2026-01-10 08:30:00',
                'estado' => 'aprobada',
                'observacion' => 'Práctica completada con honores.',
                'revisada_por_coordinador_id' => $coord->id,
                'fecha_revision' => '2026-01-12 11:00:00',
            ]
        );

        $pracLuis = Practica::updateOrCreate(
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

        EvaluacionFinal::updateOrCreate(
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
