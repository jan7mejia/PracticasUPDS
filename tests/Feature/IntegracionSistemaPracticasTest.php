<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Practica;
use App\Models\Estudiante;
use App\Models\DocenteTutor;
use App\Models\Coordinador;
use App\Models\CronogramaActividad;
use App\Models\RegistroHora;
use App\Models\InformeAvance;
use App\Models\JustificacionAsistencia;
use App\Models\IncidenciaPractica;
use App\Models\RubricaActividad;
use App\Models\CriterioRubrica;
use App\Models\EvaluacionFinal;
use App\Models\EvaluacionOrganizacion;
use App\Models\ResponsableOrganizacion;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class IntegracionSistemaPracticasTest extends TestCase
{
    /**
     * Test 1: Los registros existentes permanecen intactos.
     */
    public function test_01_registros_existentes_permanecen_intactos()
    {
        $practicaJuan = Practica::find(1);
        $this->assertNotNull($practicaJuan, 'La práctica #1 de Juan debe existir');
        $this->assertEquals('en_curso', $practicaJuan->estado);
        $this->assertEquals(240.00, (float)$practicaJuan->horas_objetivo);
        $this->assertEquals('v1_historico', $practicaJuan->version_reglamento);

        $practicaLuis = Practica::find(2);
        $this->assertNotNull($practicaLuis, 'La práctica #2 de Luis debe existir');
        $this->assertEquals('concluida', $practicaLuis->estado);
    }

    /**
     * Test 2: DatabaseSeeder es idempotente y no sobreescribe contraseñas ni datos existentes.
     */
    public function test_02_seeder_no_sobrescribe_contrasenas_ni_datos()
    {
        $userCoord = User::where('email', 'coordinacion@upds.edu.bo')->first();
        $this->assertNotNull($userCoord);
        $passwordOriginalHash = $userCoord->password;

        // Ejecutar seeder
        $seeder = new DatabaseSeeder();
        $seeder->run();

        $userCoord->refresh();
        $this->assertEquals($passwordOriginalHash, $userCoord->password, 'La contraseña no debe ser sobreescrita por el seeder');
        $this->assertTrue(Hash::check('12345678', $userCoord->password));
    }

    /**
     * Test 3: Una práctica nueva dura exactamente tres meses calendario.
     */
    public function test_03_practica_nueva_dura_exactamente_tres_meses()
    {
        $fechaInicio = '2026-03-01';
        $fechaFin = Practica::calcularFechaFin3Meses($fechaInicio);
        $this->assertEquals('2026-06-01', $fechaFin, '3 meses calendario desde 01/03/2026 debe ser 01/06/2026');

        $fechaInicio2 = '2026-02-15';
        $fechaFin2 = Practica::calcularFechaFin3Meses($fechaInicio2);
        $this->assertEquals('2026-05-15', $fechaFin2);
    }

    /**
     * Test 4: El total de horas se calcula según los días hábiles programados (L-V * 4h).
     */
    public function test_04_horas_calculadas_segun_dias_programados()
    {
        // Rango de 1 semana laboral: Lunes 2026-03-02 a Viernes 2026-03-06 (5 días)
        $dias = Practica::contarDiasHabiles('2026-03-02', '2026-03-06');
        $this->assertEquals(5, $dias);

        $horas = Practica::calcularHorasRequeridasV2('2026-03-02', '2026-03-06');
        $this->assertEquals(20.00, $horas, '5 días hábiles a 4h/día = 20 horas');
    }

    /**
     * Test 5: Máximo 4 horas por jornada diaria y control de acumulación en el mismo día.
     */
    public function test_05_limite_maximo_4_horas_diarias()
    {
        $practica = Practica::find(1);
        $estudianteUser = $practica->solicitud->estudiante->user;

        $fechaPrueba = '2026-05-20';
        RegistroHora::where('practica_id', $practica->id)->whereDate('fecha', $fechaPrueba)->delete();

        // 1. Intentar registrar 5 horas en una sola sesión (debe ser rechazado)
        $response = $this->actingAs($estudianteUser)->post(route('seguimiento.horas.store', $practica->id), [
            'fecha' => $fechaPrueba,
            'horas' => 5.0,
            'descripcion' => 'Intento de 5 horas',
        ]);
        $response->assertSessionHasErrors('horas');

        // 2. Registrar 2 horas (permitido)
        $respOk1 = $this->actingAs($estudianteUser)->post(route('seguimiento.horas.store', $practica->id), [
            'fecha' => $fechaPrueba,
            'horas' => 2.0,
            'descripcion' => 'Primera sesión del día de 2h',
        ]);
        $respOk1->assertSessionHas('success');

        // 3. Registrar 2 horas más el mismo día (permitido: suma 4h)
        $respOk2 = $this->actingAs($estudianteUser)->post(route('seguimiento.horas.store', $practica->id), [
            'fecha' => $fechaPrueba,
            'horas' => 2.0,
            'descripcion' => 'Segunda sesión del día de 2h',
        ]);
        $respOk2->assertSessionHas('success');

        // 4. Intentar registrar 1 hora más el mismo día (rechazado: superaría las 4h)
        $respFail = $this->actingAs($estudianteUser)->post(route('seguimiento.horas.store', $practica->id), [
            'fecha' => $fechaPrueba,
            'horas' => 1.0,
            'descripcion' => 'Tercera sesión excedente',
        ]);
        $respFail->assertSessionHasErrors('horas');

        // Limpiar
        RegistroHora::where('practica_id', $practica->id)->whereDate('fecha', $fechaPrueba)->delete();
    }

    /**
     * Test 6: Dos faltas justificadas son permitidas.
     */
    public function test_06_dos_faltas_justificadas_permitidas()
    {
        $practica = Practica::find(1);
        $coordUser = User::where('email', 'coordinacion@upds.edu.bo')->first();

        $practica->update(['faltas_justificadas' => 0]);

        $j1 = JustificacionAsistencia::create([
            'practica_id' => $practica->id,
            'estudiante_id' => $practica->solicitud->estudiante_id,
            'fecha' => '2026-05-02',
            'tipo' => 'falta',
            'horas_reales' => 0,
            'motivo' => 'Falta por salud comprobada 1',
            'estado' => 'pendiente',
        ]);

        $this->actingAs($coordUser)->post(route('seguimiento.justificaciones.resolver', $j1->id), [
            'estado' => 'aprobada',
            'observacion_resolucion' => 'Aprobada con baja médica',
        ]);

        $practica->refresh();
        $this->assertEquals(1, $practica->faltas_justificadas);

        $j2 = JustificacionAsistencia::create([
            'practica_id' => $practica->id,
            'estudiante_id' => $practica->solicitud->estudiante_id,
            'fecha' => '2026-05-03',
            'tipo' => 'falta',
            'horas_reales' => 0,
            'motivo' => 'Falta por salud comprobada 2',
            'estado' => 'pendiente',
        ]);

        $this->actingAs($coordUser)->post(route('seguimiento.justificaciones.resolver', $j2->id), [
            'estado' => 'aprobada',
            'observacion_resolucion' => 'Segunda falta autorizada',
        ]);

        $practica->refresh();
        $this->assertEquals(2, $practica->faltas_justificadas);

        // Sin incidencias generadas aún
        $incidencias = IncidenciaPractica::where('practica_id', $practica->id)->where('tipo', 'tercera_falta')->count();
        $this->assertEquals(0, $incidencias);
    }

    /**
     * Test 7: Dos jornadas incompletas justificadas son permitidas.
     */
    public function test_07_dos_jornadas_incompletas_permitidas()
    {
        $practica = Practica::find(1);
        $coordUser = User::where('email', 'coordinacion@upds.edu.bo')->first();

        $practica->update(['incompletas_justificadas' => 0]);

        $j1 = JustificacionAsistencia::create([
            'practica_id' => $practica->id,
            'estudiante_id' => $practica->solicitud->estudiante_id,
            'fecha' => '2026-05-04',
            'tipo' => 'incompleta',
            'horas_reales' => 2.0,
            'motivo' => 'Jornada incompleta por trámite universitario 1',
            'estado' => 'pendiente',
        ]);

        $this->actingAs($coordUser)->post(route('seguimiento.justificaciones.resolver', $j1->id), [
            'estado' => 'aprobada',
            'observacion_resolucion' => 'Aprobado trámite',
        ]);

        $practica->refresh();
        $this->assertEquals(1, $practica->incompletas_justificadas);

        $j2 = JustificacionAsistencia::create([
            'practica_id' => $practica->id,
            'estudiante_id' => $practica->solicitud->estudiante_id,
            'fecha' => '2026-05-05',
            'tipo' => 'incompleta',
            'horas_reales' => 2.5,
            'motivo' => 'Jornada incompleta por trámite universitario 2',
            'estado' => 'pendiente',
        ]);

        $this->actingAs($coordUser)->post(route('seguimiento.justificaciones.resolver', $j2->id), [
            'estado' => 'aprobada',
            'observacion_resolucion' => 'Aprobado trámite 2',
        ]);

        $practica->refresh();
        $this->assertEquals(2, $practica->incompletas_justificadas);
    }

    /**
     * Test 8: Tercera falta genera incidencia grave que bloquea el cierre.
     */
    public function test_08_tercera_falta_genera_incidencia_grave()
    {
        $practica = Practica::find(1);
        $coordUser = User::where('email', 'coordinacion@upds.edu.bo')->first();

        $practica->update(['faltas_justificadas' => 2]);

        $j3 = JustificacionAsistencia::create([
            'practica_id' => $practica->id,
            'estudiante_id' => $practica->solicitud->estudiante_id,
            'fecha' => '2026-05-06',
            'tipo' => 'falta',
            'horas_reales' => 0,
            'motivo' => 'Tercera falta',
            'estado' => 'pendiente',
        ]);

        $this->actingAs($coordUser)->post(route('seguimiento.justificaciones.resolver', $j3->id), [
            'estado' => 'aprobada',
            'observacion_resolucion' => 'Aprobada pero genera incidencia',
        ]);

        $practica->refresh();
        $this->assertEquals(3, $practica->faltas_justificadas);

        $incidencia = IncidenciaPractica::where('practica_id', $practica->id)->where('tipo', 'tercera_falta')->first();
        $this->assertNotNull($incidencia);
        $this->assertTrue((bool)$incidencia->bloquea_cierre);
        $this->assertFalse((bool)$incidencia->resuelta);

        // Limpiar para siguientes tests
        $incidencia->delete();
        $practica->update(['faltas_justificadas' => 0, 'incompletas_justificadas' => 0]);
    }

    /**
     * Test 9: Cronograma elaborado por tutor y formalmente aprobado por coordinación.
     */
    public function test_09_cronograma_elaborado_por_tutor_y_aprobado_por_coordinacion()
    {
        $practica = Practica::find(1);
        $tutorUser = $practica->tutor->user;
        $coordUser = User::where('email', 'coordinacion@upds.edu.bo')->first();

        // 1. Tutor asignado crea actividad en el cronograma
        $respTutor = $this->actingAs($tutorUser)->post(route('seguimiento.actividades.store', $practica->id), [
            'titulo' => 'Actividad de prueba planificada por tutor',
            'objetivo' => 'Objetivo técnico',
            'entregable_esperado' => 'Documento entregable',
            'fecha_inicio' => '2026-04-01',
            'fecha_fin' => '2026-04-10',
            'horas_planificadas' => 20,
        ]);
        $respTutor->assertSessionHas('success');

        $actividad = CronogramaActividad::where('practica_id', $practica->id)->where('titulo', 'Actividad de prueba planificada por tutor')->first();
        $this->assertNotNull($actividad);
        $this->assertEquals('Documento entregable', $actividad->entregable_esperado);

        // 2. Coordinación aprueba cronograma
        $respAprobar = $this->actingAs($coordUser)->post(route('seguimiento.aprobar-cronograma', $practica->id));
        $respAprobar->assertSessionHas('success');

        $practica->refresh();
        $this->assertTrue((bool)$practica->cronograma_aprobado);

        // Limpiar actividad de test
        $actividad->delete();
    }

    /**
     * Test 10: Informes asociados a actividades con control de versiones.
     */
    public function test_10_informes_asociados_a_actividades_con_versionado()
    {
        $practica = Practica::find(1);
        $estudianteUser = $practica->solicitud->estudiante->user;
        $actividad = $practica->actividades()->first();

        // Crear informe v1
        $inf1 = InformeAvance::create([
            'practica_id' => $practica->id,
            'actividad_id' => $actividad->id,
            'version' => 1,
            'titulo' => 'Informe Actividad 1 (v1)',
            'periodo_inicio' => '2026-03-10',
            'periodo_fin' => '2026-03-20',
            'resumen' => 'Avance v1 del informe',
            'estado' => 'enviado',
        ]);
        $this->assertEquals(1, $inf1->version);
        $this->assertEquals($actividad->id, $inf1->actividad_id);

        $inf1->delete();
    }

    /**
     * Test 11: Rúbricas por actividad (suma 100%) y notas calculadas automáticamente.
     */
    public function test_11_rubricas_por_actividad_y_notas_automaticas()
    {
        $practica = Practica::find(1);
        $tutorUser = $practica->tutor->user;
        $actividad = $practica->actividades()->first();

        // 1. Configurar rúbrica donde la suma no dé 100% (debe fallar)
        $respFail = $this->actingAs($tutorUser)->post(route('seguimiento.actividades.rubrica.store', $actividad->id), [
            'titulo' => 'Rúbrica de prueba inválida',
            'criterios' => [
                ['nombre' => 'Criterio 1', 'ponderacion' => 50],
                ['nombre' => 'Criterio 2', 'ponderacion' => 30], // suma 80% != 100%
            ],
        ]);
        $respFail->assertSessionHasErrors('criterios');

        // 2. Configurar rúbrica válida al 100%
        $respOk = $this->actingAs($tutorUser)->post(route('seguimiento.actividades.rubrica.store', $actividad->id), [
            'titulo' => 'Rúbrica de prueba válida',
            'criterios' => [
                ['nombre' => 'Criterio 1', 'ponderacion' => 60],
                ['nombre' => 'Criterio 2', 'ponderacion' => 40],
            ],
        ]);
        $respOk->assertSessionHas('success');

        $rubrica = RubricaActividad::where('actividad_id', $actividad->id)->first();
        $this->assertNotNull($rubrica);
        $criterios = $rubrica->criterios;
        $this->assertCount(2, $criterios);

        // 3. Evaluar rúbrica: Criterio 1 = 80 pts (60% pond = 48), Criterio 2 = 90 pts (40% pond = 36) -> Total: 84 pts
        $c1 = $criterios[0];
        $c2 = $criterios[1];

        $respEval = $this->actingAs($tutorUser)->post(route('seguimiento.rubricas.evaluar', $rubrica->id), [
            'puntuaciones' => [
                $c1->id => 80,
                $c2->id => 90,
            ],
        ]);
        $respEval->assertSessionHas('success');

        $actividad->refresh();
        $this->assertEquals(84.00, $actividad->calcularNota());
        $this->assertEquals('completada', $actividad->estado);
    }

    /**
     * Test 12: Cierre rechazado si faltan requisitos (horas < 90% o sin evaluación final).
     */
    public function test_12_cierre_rechazado_cuando_faltan_requisitos()
    {
        $practica = Practica::find(1);
        $coordUser = User::where('email', 'coordinacion@upds.edu.bo')->first();

        // Practica 1 tiene 122h de 240h (50.8%), requiere 100% por ser histórica o 90% por v2
        $respCierre = $this->actingAs($coordUser)->post(route('seguimiento.cierre', $practica->id), [
            'nuevo_estado' => 'concluida',
            'motivo' => 'Cierre forzado de prueba',
        ]);
        $respCierre->assertSessionHasErrors('nuevo_estado');
    }

    /**
     * Test 13: Separación estricta de roles y permisos.
     */
    public function test_13_separacion_estricta_de_roles_y_permisos()
    {
        $practica = Practica::find(1);
        $estudianteUser = $practica->solicitud->estudiante->user;
        $coordUser = User::where('email', 'coordinacion@upds.edu.bo')->first();
        $adminUser = User::where('email', 'admin@upds.edu.bo')->first();

        // 1. Estudiante NO puede validar horas
        $hora = $practica->registrosHoras()->first();
        if ($hora) {
            $respEstVal = $this->actingAs($estudianteUser)->post(route('seguimiento.horas.validar', $hora->id), [
                'estado' => 'validada',
            ]);
            $this->assertEquals(403, $respEstVal->getStatusCode());
        }

        // 2. Coordinador NO puede evaluar parciales en nombre del tutor
        $respCoordParcial = $this->actingAs($coordUser)->post(route('seguimiento.evaluacion-parcial.store', $practica->id), [
            'periodo' => '1er Parcial',
            'fecha' => '2026-04-10',
            'calificacion' => 85,
            'resultado' => 'Aprobado',
        ]);
        $this->assertEquals(403, $respCoordParcial->getStatusCode());

        // 3. Administrador NO puede evaluar finales de tutor
        $respAdminFinal = $this->actingAs($adminUser)->post(route('seguimiento.evaluacion.store', $practica->id), [
            'calificacion' => 90,
            'resultado' => 'Aprobado',
            'comentario' => 'Evaluación de admin no permitida',
        ]);
        $this->assertEquals(403, $respAdminFinal->getStatusCode());
    }
}

