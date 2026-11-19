<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use App\Models\DocumentoSolicitud;
use App\Models\SolicitudPractica;

class DocumentoController extends Controller
{
    /**
     * Adjuntar o reemplazar un documento en el expediente de solicitud.
     * Restringido exclusivamente al estudiante postulante propietario de la solicitud.
     */
    public function store(Request $request)
    {
        $request->validate([
            'solicitud_id' => 'required|exists:solicitudes_practica,id',
            'tipo_documento_id' => 'required|exists:tipos_documento,id',
            'archivo' => 'required|file|mimes:pdf,doc,docx|max:10240', // Solo PDF y Word (10MB)
        ], [
            'archivo.required' => 'Selecciona un archivo digital para adjuntar.',
            'archivo.file' => 'El elemento seleccionado no es un archivo válido.',
            'archivo.mimes' => 'Formato no permitido. Para documentos académicos formales, usa únicamente archivos PDF o Word (.pdf, .doc, .docx).',
            'archivo.max' => 'El archivo supera el límite máximo permitido de 10 MB.',
        ]);

        $solicitud = SolicitudPractica::findOrFail($request->solicitud_id);
        $user = Auth::user();

        // RF-08: Coordinación, tutores y administradores NO adjuntan documentos en nombre del estudiante
        if (!$user->isEstudiante()) {
            abort(403, 'Acción no autorizada. Únicamente el estudiante postulante puede adjuntar documentos a su expediente de solicitud.');
        }

        // RF-08: El estudiante solo puede adjuntar archivos a su propia solicitud
        if ($user->estudiante?->id !== $solicitud->estudiante_id) {
            abort(403, 'No tienes permiso para subir documentos a esta solicitud.');
        }

        // RF-08 / RF-09: Solo se permite adjuntar cuando el estado de la solicitud lo permita
        if (!in_array($solicitud->estado, ['presentada', 'en_revision', 'observada'])) {
            return back()->withErrors([
                'archivo' => "No es posible adjuntar ni modificar documentos porque la solicitud se encuentra en estado '{$solicitud->estado}'.",
            ]);
        }

        $file = $request->file('archivo');
        $originalName = $file->getClientOriginalName();
        $mime = $file->getClientMimeType();
        $size = $file->getSize();

        // Guardar en almacenamiento seguro privado 'local' (storage/app/solicitudes/{id}/)
        $path = $file->store("solicitudes/{$solicitud->id}", 'local');

        // Calcular la versión del documento para preservar el historial sin eliminar registros anteriores
        $versionActual = DocumentoSolicitud::where('solicitud_id', $solicitud->id)
            ->where('tipo_documento_id', $request->tipo_documento_id)
            ->max('version_documento') ?? 0;
        $nuevaVersion = $versionActual + 1;

        DocumentoSolicitud::create([
            'solicitud_id' => $solicitud->id,
            'tipo_documento_id' => $request->tipo_documento_id,
            'nombre_original' => $originalName,
            'ruta_privada' => $path,
            'tipo_mime' => $mime,
            'tamano_bytes' => $size,
            'version_documento' => $nuevaVersion,
            'estado' => 'pendiente',
            'observacion_validacion' => null,
            'created_at' => now(),
        ]);

        $mensaje = $nuevaVersion > 1 
            ? "Documento actualizado correctamente (Versión {$nuevaVersion} registrada para revisión de Coordinación)." 
            : 'Documento adjuntado correctamente al expediente.';

        return back()->with('success', $mensaje);
    }

    /**
     * Descarga de documento adjunto con verificación de acceso y existencia física.
     */
    public function download(DocumentoSolicitud $documento)
    {
        $user = Auth::user();

        // Seguridad: Si es estudiante, debe ser el propietario de la solicitud
        if ($user->isEstudiante() && $user->estudiante?->id !== $documento->solicitud->estudiante_id) {
            abort(403, 'No tienes autorización para descargar este archivo.');
        }

        // Seguridad: Si es tutor, debe tener asignada la práctica correspondiente
        if ($user->isTutor()) {
            $tutor = $user->tutor;
            $esAsignado = $documento->solicitud->practica && $documento->solicitud->practica->tutor_id === $tutor?->id;
            if (!$esAsignado) {
                abort(403, 'No tienes autorización para descargar documentos de estudiantes que no supervisas.');
            }
        }

        // RF-08: NO fabricar archivos ficticios. Si el físico no existe, reportar error sin generar contenido inventado.
        if (!Storage::disk('local')->exists($documento->ruta_privada)) {
            return back()->withErrors([
                'archivo' => "El archivo '{$documento->nombre_original}' no se encuentra en el servidor. Es posible que haya sido eliminado o que nunca se haya cargado correctamente. Vuelva a subir el documento.",
            ]);
        }

        return Storage::disk('local')->download($documento->ruta_privada, $documento->nombre_original);
    }

    /**
     * Validación y dictamen resolutivo sobre un documento del expediente.
     * Restringido exclusivamente al Coordinador Académico.
     */
    public function validar(Request $request, DocumentoSolicitud $documento)
    {
        $user = Auth::user();

        // RF-08 / RF-09: Exclusivo para Coordinación Académica (bloquea estudiante, tutor y administrador)
        if ($user->isEstudiante() || $user->isTutor() || !$user->isCoordinador()) {
            abort(403, 'Acción no autorizada. La validación y rechazo de documentos en el expediente corresponde exclusivamente a Coordinación Académica.');
        }

        $request->validate([
            'estado' => 'required|in:validado,observado,rechazado',
            'observacion' => in_array($request->estado, ['observado', 'rechazado']) ? 'required|string|min:5|max:500' : 'nullable|string|max:500',
        ], [
            'estado.required' => 'El estado de validación es obligatorio.',
            'estado.in' => 'El dictamen resolutivo seleccionado no es válido.',
            'observacion.required' => 'Debes ingresar obligatoriamente el motivo u observación en español al observar o rechazar un documento.',
            'observacion.min' => 'El motivo debe tener al menos :min caracteres para brindar una justificación clara al estudiante.',
            'observacion.max' => 'El motivo no puede exceder los :max caracteres.',
        ]);

        $observacion = $request->observacion;
        if ($request->estado === 'validado' && empty($observacion)) {
            $observacion = 'Documento verificado y validado por Coordinación Académica.';
        }

        $documento->update([
            'estado' => $request->estado,
            'observacion_validacion' => $observacion,
            'validado_por' => Auth::id(),
            'fecha_validacion' => now(),
        ]);

        $estadoNombre = ucfirst($request->estado);
        return back()->with('success', "Documento evaluado exitosamente: marcado como {$estadoNombre}.");
    }
}
