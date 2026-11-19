<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use App\Models\DocumentoSolicitud;
use App\Models\SolicitudPractica;

class DocumentoController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'solicitud_id' => 'required|exists:solicitudes_practica,id',
            'tipo_documento_id' => 'required|exists:tipos_documento,id',
            'archivo' => 'required|file|mimes:pdf,doc,docx|max:10240', // Solo PDF y Word (10MB)
        ], [
            'archivo.required' => 'Selecciona un documento para adjuntar.',
            'archivo.file' => 'El elemento seleccionado no es un archivo válido.',
            'archivo.mimes' => 'Formato no permitido. Para documentos académicos formales, usa únicamente archivos PDF o Word (.pdf, .doc, .docx).',
            'archivo.max' => 'El archivo supera el límite de 10 MB.',
        ]);

        $solicitud = SolicitudPractica::findOrFail($request->solicitud_id);
        $user = Auth::user();

        // Validar permisos: Estudiante solo sube a su propia solicitud
        if ($user->isEstudiante() && $user->estudiante?->id !== $solicitud->estudiante_id) {
            abort(403, 'No tienes permiso para subir documentos a esta solicitud.');
        }

        // Tutores no cargan documentos de admisión a la solicitud
        if ($user->isTutor()) {
            abort(403, 'Los docentes tutores no tienen permisos para cargar documentos en el expediente de solicitud.');
        }

        $file = $request->file('archivo');
        $originalName = $file->getClientOriginalName();
        $mime = $file->getClientMimeType();
        $size = $file->getSize();

        // Guardar en disco privado 'local' (storage/app/solicitudes/{id}/)
        $path = $file->store("solicitudes/{$solicitud->id}", 'local');

        DocumentoSolicitud::create([
            'solicitud_id' => $solicitud->id,
            'tipo_documento_id' => $request->tipo_documento_id,
            'nombre_original' => $originalName,
            'ruta_privada' => $path,
            'tipo_mime' => $mime,
            'tamano_bytes' => $size,
            'version_documento' => 1,
            'estado' => 'pendiente',
            'created_at' => now(),
        ]);

        return back()->with('success', 'Documento adjuntado correctamente.');
    }

    public function download(DocumentoSolicitud $documento)
    {
        $user = Auth::user();

        // Si es estudiante, validar que sea su solicitud
        if ($user->isEstudiante() && $user->estudiante?->id !== $documento->solicitud->estudiante_id) {
            abort(403, 'No tienes autorización para descargar este archivo.');
        }

        // Si es tutor, validar que supervise la práctica
        if ($user->isTutor()) {
            $tutor = $user->tutor;
            $esAsignado = $documento->solicitud->practica && $documento->solicitud->practica->tutor_id === $tutor?->id;
            if (!$esAsignado) {
                abort(403, 'No tienes autorización para descargar documentos de estudiantes que no supervisas.');
            }
        }

        // RF-08: No fabricar archivos ficticios. Si el físico no existe, reportar error.
        if (!Storage::disk('local')->exists($documento->ruta_privada)) {
            return back()->withErrors([
                'archivo' => "El archivo '{$documento->nombre_original}' no se encuentra en el servidor. Es posible que haya sido eliminado o que nunca se haya cargado correctamente. Vuelva a subir el documento.",
            ]);
        }

        return Storage::disk('local')->download($documento->ruta_privada, $documento->nombre_original);
    }

    public function validar(Request $request, DocumentoSolicitud $documento)
    {
        $user = Auth::user();

        // Seguridad: El estudiante y el tutor no validan documentos de admisión (corresponde a coordinación)
        if ($user->isEstudiante() || $user->isTutor()) {
            abort(403, 'Acción no autorizada. La validación de expedientes de solicitud corresponde a Coordinación Académica.');
        }

        $request->validate([
            'estado' => 'required|in:validado,observado,rechazado',
            'observacion' => in_array($request->estado, ['observado', 'rechazado']) ? 'required|string|min:3|max:500' : 'nullable|string|max:500',
        ], [
            'observacion.required' => 'Debe ingresar obligatoriamente el motivo u observación al observar o rechazar un documento.',
        ]);

        $documento->update([
            'estado' => $request->estado,
            'observacion_validacion' => $request->observacion,
            'validado_por' => Auth::id(),
            'fecha_validacion' => now(),
        ]);

        return back()->with('success', "Documento marcado como: {$request->estado}.");
    }
}

