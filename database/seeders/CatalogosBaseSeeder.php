<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Role;
use App\Models\Carrera;
use App\Models\PeriodoAcademico;
use App\Models\TipoDocumento;
use App\Models\RequisitoAcademico;
use App\Models\OrganizacionReceptora;
use App\Models\ResponsableOrganizacion;

class CatalogosBaseSeeder extends Seeder
{
    /**
     * Catálogos indispensables del sistema UPDS.
     * Idempotente y seguro: utiliza firstOrCreate() para nunca sobreescribir datos existentes.
     */
    public function run(): void
    {
        // 1. Roles institucionales
        $rolesData = [
            ['id' => 1, 'codigo' => 'administrador', 'nombre' => 'Administrador', 'descripcion' => 'Gestión general de usuarios y parámetros'],
            ['id' => 2, 'codigo' => 'coordinador', 'nombre' => 'Coordinador académico', 'descripcion' => 'Gestión de solicitudes y prácticas'],
            ['id' => 3, 'codigo' => 'tutor', 'nombre' => 'Docente tutor', 'descripcion' => 'Seguimiento y evaluación de estudiantes'],
            ['id' => 4, 'codigo' => 'estudiante', 'nombre' => 'Estudiante', 'descripcion' => 'Solicitudes, informes y consulta de sus prácticas'],
            ['id' => 5, 'codigo' => 'responsable_organizacion', 'nombre' => 'Responsable de organización', 'descripcion' => 'Rol opcional de validación empresarial'],
        ];
        foreach ($rolesData as $r) {
            Role::firstOrCreate(['id' => $r['id']], $r);
        }

        // 2. Carreras
        $carreras = [
            ['codigo' => 'SIS', 'nombre' => 'Ingeniería de Sistemas', 'activa' => true],
            ['codigo' => 'RED', 'nombre' => 'Ing. en Redes y Telecomunicaciones', 'activa' => true],
            ['codigo' => 'ADM', 'nombre' => 'Administración de Empresas', 'activa' => true],
            ['codigo' => 'COM', 'nombre' => 'Ingeniería Comercial', 'activa' => true],
            ['codigo' => 'DER', 'nombre' => 'Derecho', 'activa' => true],
        ];
        foreach ($carreras as $c) {
            Carrera::firstOrCreate(['codigo' => $c['codigo']], $c);
        }

        // 3. Periodos Académicos
        PeriodoAcademico::firstOrCreate(
            ['codigo' => '2026-I'],
            [
                'nombre' => 'Gestión Académica I/2026',
                'fecha_inicio' => '2026-02-01',
                'fecha_fin' => '2026-06-30',
                'activo' => true
            ]
        );
        PeriodoAcademico::firstOrCreate(
            ['codigo' => '2026-II'],
            [
                'nombre' => 'Gestión Académica II/2026',
                'fecha_inicio' => '2026-08-01',
                'fecha_fin' => '2026-12-31',
                'activo' => false
            ]
        );

        // 4. Tipos de Documento
        $tiposDoc = [
            ['codigo' => 'solicitud', 'nombre' => 'Carta de solicitud de práctica', 'obligatorio' => true, 'activo' => true],
            ['codigo' => 'convenio', 'nombre' => 'Convenio interinstitucional', 'obligatorio' => true, 'activo' => true],
            ['codigo' => 'aceptacion', 'nombre' => 'Carta de aceptación empresarial', 'obligatorio' => true, 'activo' => true],
            ['codigo' => 'plan_trabajo', 'nombre' => 'Plan de trabajo de práctica', 'obligatorio' => true, 'activo' => true],
            ['codigo' => 'informe_avance', 'nombre' => 'Informe de avance periódico', 'obligatorio' => false, 'activo' => true],
            ['codigo' => 'evaluacion_empresa', 'nombre' => 'Evaluación empresarial de desempeño', 'obligatorio' => false, 'activo' => true],
            ['codigo' => 'otro', 'nombre' => 'Otro documento complementario', 'obligatorio' => false, 'activo' => true],
        ];
        foreach ($tiposDoc as $td) {
            TipoDocumento::firstOrCreate(['codigo' => $td['codigo']], $td);
        }

        // 5. Requisitos Académicos
        $reqs = [
            ['nombre' => '7mo Semestre vencido o superior', 'descripcion' => 'Tener aprobado hasta el 6to semestre regular.', 'obligatorio' => true, 'activo' => true],
            ['nombre' => 'Materias de especialidad aprobadas', 'descripcion' => 'Acreditar aprobación de materias troncales de carrera.', 'obligatorio' => true, 'activo' => true],
            ['nombre' => 'Seguro universitario vigente', 'descripcion' => 'Cobertura médica y de accidentes estudiantil al día.', 'obligatorio' => true, 'activo' => true],
            ['nombre' => 'Sin deudas administrativas', 'descripcion' => 'Solvencia económica y académica institucional UPDS.', 'obligatorio' => true, 'activo' => true],
        ];
        foreach ($reqs as $rq) {
            RequisitoAcademico::firstOrCreate(['nombre' => $rq['nombre']], $rq);
        }

        // 6. Organizaciones Receptoras iniciales
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

        foreach ($orgs as $oData) {
            $respData = $oData['responsable'];
            unset($oData['responsable']);
            $org = OrganizacionReceptora::firstOrCreate(['nombre' => $oData['nombre']], $oData);

            ResponsableOrganizacion::firstOrCreate(
                ['organizacion_id' => $org->id, 'correo' => $respData['correo']],
                $respData + ['organizacion_id' => $org->id, 'activo' => true]
            );
        }
    }
}

