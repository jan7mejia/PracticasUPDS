<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\SolicitudPractica;

class BuscadoresSistemaTest extends TestCase
{
    public function test_estudiante_puede_buscar_sus_solicitudes_por_organizacion_y_codigo()
    {
        // Estudiante Juan Pérez (User ID 5, Estudiante ID 1)
        $estudiante = User::whereHas('rol', fn($q) => $q->where('codigo', 'estudiante'))
            ->whereHas('estudiante', fn($q) => $q->where('id', 1))
            ->first();

        $this->assertNotNull($estudiante);

        // 1. Buscar 'Ban' -> Banco Unión (S-0022)
        $response = $this->actingAs($estudiante)->get('/solicitudes?buscar=Ban');
        $response->assertStatus(200);
        $response->assertSee('Banco Unión');
        $response->assertDontSee('Gobierno Autónomo'); // De otro estudiante

        // 2. Buscar 'Emp' -> Empresa ABC
        $response = $this->actingAs($estudiante)->get('/solicitudes?buscar=Emp');
        $response->assertStatus(200);
        $response->assertSee('Empresa ABC S.R.L.');

        // 3. Buscar 'S-0022' -> exactamente S-0022
        $response = $this->actingAs($estudiante)->get('/solicitudes?buscar=S-0022');
        $response->assertStatus(200);
        $response->assertSee('S-0022');

        // 4. Buscar término inexistente -> Cero resultados con aviso amigable
        $response = $this->actingAs($estudiante)->get('/solicitudes?buscar=TerminoInexistente999');
        $response->assertStatus(200);
        $response->assertSee('No se encontraron resultados');

        // 5. Borrar término -> Restaurar listado
        $response = $this->actingAs($estudiante)->get('/solicitudes?buscar=');
        $response->assertStatus(200);
        $response->assertSee('Empresa ABC S.R.L.');
        $response->assertSee('Banco Unión');
    }

    public function test_busqueda_organizaciones_y_estudiantes()
    {
        $coord = User::whereHas('rol', fn($q) => $q->where('codigo', 'coordinador'))->first();
        $this->assertNotNull($coord);

        // Buscar organizaciones por nombre
        $response = $this->actingAs($coord)->get('/organizaciones?buscar=Banco');
        $response->assertStatus(200);
        $response->assertSee('Banco Unión');

        // Buscar estudiantes por nombre
        $response = $this->actingAs($coord)->get('/estudiantes?buscar=Juan');
        $response->assertStatus(200);
        $response->assertSee('Juan');
    }
}

