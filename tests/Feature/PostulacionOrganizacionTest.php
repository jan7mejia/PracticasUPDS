<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\OrganizacionReceptora;
use App\Models\PeriodoAcademico;

class PostulacionOrganizacionTest extends TestCase
{
    public function test_estudiante_puede_iniciar_solicitud_con_organizacion_preseleccionada()
    {
        $estudiante = User::whereHas('rol', function ($q) {
            $q->where('codigo', 'estudiante');
        })->whereHas('estudiante')->first();

        $this->assertNotNull($estudiante, 'Debe existir un usuario estudiante en la BD');

        $organizacion = OrganizacionReceptora::where('estado', 'activa')->first();
        $this->assertNotNull($organizacion, 'Debe existir una organización activa en la BD');

        $response = $this->actingAs($estudiante)->get(route('solicitudes.create', ['organizacion_id' => $organizacion->id]));

        $response->assertStatus(200);
        $response->assertViewHas('organizacionPreseleccionada', function ($org) use ($organizacion) {
            return $org && $org->id === $organizacion->id;
        });

        // Verificar presencia del contenedor institucional y el ID oculto
        $response->assertSee($organizacion->nombre);
        $response->assertSee('value="' . $organizacion->id . '"', false);
        $response->assertSee('Organización Receptora Seleccionada');
        $response->assertSee('Esta empresa fue preseleccionada desde tu consulta de organizaciones.');
    }

    public function test_estudiante_sin_organizacion_ve_selector_desplegable()
    {
        $estudiante = User::whereHas('rol', function ($q) {
            $q->where('codigo', 'estudiante');
        })->whereHas('estudiante')->first();

        $this->assertNotNull($estudiante);

        $response = $this->actingAs($estudiante)->get(route('solicitudes.create'));

        $response->assertStatus(200);
        $response->assertViewHas('organizacionPreseleccionada', null);
        $response->assertSee('-- Elige una organización autorizada con convenio vigente --');
    }
}
