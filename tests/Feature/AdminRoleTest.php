<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Role;
use App\Models\PeriodoAcademico;
use App\Models\Carrera;
use App\Models\AuditoriaAdministrativa;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class AdminRoleTest extends TestCase
{
    public function test_admin_dashboard_metrics_and_access(): void
    {
        $admin = User::where('email', 'admin@upds.edu.bo')->first();
        $this->assertNotNull($admin);

        $response = $this->actingAs($admin)->get('/dashboard');
        $response->assertStatus(200);
        $response->assertSee('Total usuarios');
        $response->assertSee('Cuentas activas');
    }

    public function test_student_and_tutor_blocked_from_admin_module(): void
    {
        $estudiante = User::where('email', 'estudiante@upds.edu.bo')->first();
        if ($estudiante) {
            $response = $this->actingAs($estudiante)->get('/admin/usuarios');
            $response->assertStatus(403);
        }

        $tutor = User::where('email', 'tutor@upds.edu.bo')->first();
        if ($tutor) {
            $response = $this->actingAs($tutor)->get('/admin/usuarios');
            $response->assertStatus(403);
        }

        $coord = User::where('email', 'coordinacion@upds.edu.bo')->first();
        if ($coord) {
            $response = $this->actingAs($coord)->get('/admin/usuarios');
            $response->assertStatus(403);
        }
    }

    public function test_admin_can_manage_users_and_audit(): void
    {
        DB::beginTransaction();
        try {
            $admin = User::where('email', 'admin@upds.edu.bo')->first();
            $testEmail = 'test_feature_' . rand(1000, 9999) . '@upds.edu.bo';

            $createResp = $this->actingAs($admin)->post('/admin/usuarios', [
                'name' => 'Usuario Feature Test',
                'email' => $testEmail,
                'rol_id' => 4,
                'password' => 'Password123!',
                'password_confirmation' => 'Password123!',
                'activo' => 1,
            ]);
            $createResp->assertRedirect('/admin/usuarios');

            $user = User::where('email', $testEmail)->first();
            $this->assertNotNull($user);
            $this->assertEquals(4, $user->rol_id);

            // Audit record exists
            $audit = AuditoriaAdministrativa::where('usuario_afectado_id', $user->id)->first();
            $this->assertNotNull($audit);

            // Deactivate
            $toggleResp = $this->actingAs($admin)->post("/admin/usuarios/{$user->id}/toggle", [
                'motivo' => 'Prueba feature desactivar'
            ]);
            $user->refresh();
            $this->assertFalse((bool)$user->activo);

        } finally {
            DB::rollBack();
        }
    }

    public function test_admin_blocked_from_academic_hour_validation(): void
    {
        $admin = User::where('email', 'admin@upds.edu.bo')->first();
        $hora = \App\Models\RegistroHora::first();

        if ($hora) {
            $response = $this->actingAs($admin)->post("/seguimiento/horas/{$hora->id}/validar", [
                'estado' => 'validada'
            ]);
            $response->assertStatus(403);
        }
    }
}

