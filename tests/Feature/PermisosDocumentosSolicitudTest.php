<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\SolicitudPractica;
use App\Models\DocumentoSolicitud;
use App\Models\TipoDocumento;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;

class PermisosDocumentosSolicitudTest extends TestCase
{
    /**
     * RF-08: Coordinador, Tutor y Administrador NO pueden adjuntar documentos en nombre del estudiante (403)
     */
    public function test_coordinador_tutor_y_admin_bloqueados_para_adjuntar_documentos(): void
    {
        Storage::fake('local');
        $solicitud = SolicitudPractica::first();
        $this->assertNotNull($solicitud, 'Debe existir al menos una solicitud en BD.');

        $tipoDoc = TipoDocumento::first();
        $file = UploadedFile::fake()->create('documento_test.pdf', 500, 'application/pdf');

        // 1. Coordinador bloqueado
        $coordinador = User::where('rol_id', 2)->first();
        if ($coordinador) {
            $resp = $this->actingAs($coordinador)->post('/documentos', [
                'solicitud_id' => $solicitud->id,
                'tipo_documento_id' => $tipoDoc->id,
                'archivo' => $file,
            ]);
            $resp->assertStatus(403);
        }

        // 2. Tutor bloqueado
        $tutor = User::where('rol_id', 3)->first();
        if ($tutor) {
            $resp = $this->actingAs($tutor)->post('/documentos', [
                'solicitud_id' => $solicitud->id,
                'tipo_documento_id' => $tipoDoc->id,
                'archivo' => $file,
            ]);
            $resp->assertStatus(403);
        }

        // 3. Admin bloqueado
        $admin = User::where('rol_id', 1)->first();
        if ($admin) {
            $resp = $this->actingAs($admin)->post('/documentos', [
                'solicitud_id' => $solicitud->id,
                'tipo_documento_id' => $tipoDoc->id,
                'archivo' => $file,
            ]);
            $resp->assertStatus(403);
        }
    }

    /**
     * RF-08: En la vista de expediente, el botón "+ Adjuntar Documento" está oculto para Coordinador, Tutor y Admin
     */
    public function test_boton_adjuntar_documento_oculto_para_no_estudiantes(): void
    {
        $solicitud = SolicitudPractica::first();
        $this->assertNotNull($solicitud);

        // Coordinador ve el expediente pero NO el botón de adjuntar
        $coordinador = User::where('rol_id', 2)->first();
        if ($coordinador) {
            $resp = $this->actingAs($coordinador)->get("/solicitudes/{$solicitud->id}");
            $resp->assertStatus(200);
            $resp->assertDontSee('+ Adjuntar Documento');
            $resp->assertDontSee('Cargar o Reemplazar Documento del Expediente');
        }

        // Admin ve el expediente pero NO el botón de adjuntar
        $admin = User::where('rol_id', 1)->first();
        if ($admin) {
            $resp = $this->actingAs($admin)->get("/solicitudes/{$solicitud->id}");
            $resp->assertStatus(200);
            $resp->assertDontSee('+ Adjuntar Documento');
        }
    }

    /**
     * RF-08: Estudiante propietario SÍ puede adjuntar archivos a su solicitud y ve el botón
     */
    public function test_estudiante_propietario_puede_adjuntar_y_ve_boton(): void
    {
        DB::beginTransaction();
        try {
            Storage::fake('local');
            $solicitud = SolicitudPractica::whereIn('estado', ['presentada', 'en_revision', 'observada'])->first();
            if (!$solicitud) {
                $solicitud = SolicitudPractica::first();
                $solicitud->update(['estado' => 'presentada']);
            }

            $estudianteUser = $solicitud->estudiante->user;
            $this->assertNotNull($estudianteUser);

            // 1. En la vista ve el botón de adjuntar
            $respView = $this->actingAs($estudianteUser)->get("/solicitudes/{$solicitud->id}");
            $respView->assertStatus(200);
            $respView->assertSee('+ Adjuntar Documento');

            // 2. Puede subir un documento válido
            $tipoDoc = TipoDocumento::first();
            $file = UploadedFile::fake()->create('carta_compromiso_upds.pdf', 800, 'application/pdf');

            $respStore = $this->actingAs($estudianteUser)->post('/documentos', [
                'solicitud_id' => $solicitud->id,
                'tipo_documento_id' => $tipoDoc->id,
                'archivo' => $file,
            ]);

            $respStore->assertSessionHas('success');

            // 3. Documento registrado en BD con estado pendiente
            $this->assertDatabaseHas('documentos_solicitud', [
                'solicitud_id' => $solicitud->id,
                'tipo_documento_id' => $tipoDoc->id,
                'nombre_original' => 'carta_compromiso_upds.pdf',
                'estado' => 'pendiente',
            ]);
        } finally {
            DB::rollBack();
        }
    }

    /**
     * RF-08: Estudiante NO puede adjuntar archivos a solicitudes de otros estudiantes (403)
     */
    public function test_estudiante_bloqueado_en_solicitudes_ajenas(): void
    {
        Storage::fake('local');
        $solicitudes = SolicitudPractica::all();
        if ($solicitudes->count() >= 2) {
            $solicitud1 = $solicitudes[0];
            $solicitud2 = $solicitudes[1];

            $user1 = $solicitud1->estudiante->user;
            $tipoDoc = TipoDocumento::first();
            $file = UploadedFile::fake()->create('hack_doc.pdf', 300, 'application/pdf');

            // Usuario 1 intenta subir a solicitud 2
            $resp = $this->actingAs($user1)->post('/documentos', [
                'solicitud_id' => $solicitud2->id,
                'tipo_documento_id' => $tipoDoc->id,
                'archivo' => $file,
            ]);

            $resp->assertStatus(403);
        }
    }

    /**
     * RF-08 / RF-09: Solo Coordinación Académica puede validar o rechazar documentos (bloquea estudiante, tutor y admin)
     */
    public function test_validacion_de_documentos_restringida_exclusivamente_a_coordinacion(): void
    {
        DB::beginTransaction();
        try {
            $doc = DocumentoSolicitud::first();
            $this->assertNotNull($doc);

            // 1. Estudiante bloqueado
            $estudiante = User::where('rol_id', 4)->first();
            if ($estudiante) {
                $resp = $this->actingAs($estudiante)->post("/documentos/{$doc->id}/validar", [
                    'estado' => 'validado',
                ]);
                $resp->assertStatus(403);
            }

            // 2. Tutor bloqueado
            $tutor = User::where('rol_id', 3)->first();
            if ($tutor) {
                $resp = $this->actingAs($tutor)->post("/documentos/{$doc->id}/validar", [
                    'estado' => 'validado',
                ]);
                $resp->assertStatus(403);
            }

            // 3. Admin bloqueado
            $admin = User::where('rol_id', 1)->first();
            if ($admin) {
                $resp = $this->actingAs($admin)->post("/documentos/{$doc->id}/validar", [
                    'estado' => 'validado',
                ]);
                $resp->assertStatus(403);
            }

            // 4. Coordinador puede validar
            $coord = User::where('rol_id', 2)->first();
            $this->assertNotNull($coord);

            $respCoord = $this->actingAs($coord)->post("/documentos/{$doc->id}/validar", [
                'estado' => 'validado',
                'observacion' => 'Documento verificado y conforme.',
            ]);
            $respCoord->assertSessionHas('success');
            $this->assertEquals('validado', $doc->fresh()->estado);

            // 5. Al rechazar u observar, exige motivo obligatorio en español
            $respSinMotivo = $this->actingAs($coord)->post("/documentos/{$doc->id}/validar", [
                'estado' => 'observado',
                'observacion' => '',
            ]);
            $respSinMotivo->assertSessionHasErrors(['observacion']);

            // 6. Al rechazar con motivo válido en español, se registra correctamente
            $motivo = 'El documento no cuenta con la firma del representante legal.';
            $respConMotivo = $this->actingAs($coord)->post("/documentos/{$doc->id}/validar", [
                'estado' => 'observado',
                'observacion' => $motivo,
            ]);
            $respConMotivo->assertSessionHas('success');
            $this->assertEquals('observado', $doc->fresh()->estado);
            $this->assertEquals($motivo, $doc->fresh()->observacion_validacion);
        } finally {
            DB::rollBack();
        }
    }
}
