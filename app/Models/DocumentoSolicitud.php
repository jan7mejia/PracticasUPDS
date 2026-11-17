<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DocumentoSolicitud extends Model
{
    protected $table = 'documentos_solicitud';
    public $timestamps = false;

    protected $fillable = [
        'solicitud_id',
        'tipo_documento_id',
        'nombre_original',
        'ruta_privada',
        'tipo_mime',
        'tamano_bytes',
        'version_documento',
        'estado',
        'observacion_validacion',
        'validado_por',
        'fecha_validacion',
        'created_at',
    ];

    protected $casts = [
        'fecha_validacion' => 'datetime',
        'created_at' => 'datetime',
    ];

    public function solicitud()
    {
        return $this->belongsTo(SolicitudPractica::class, 'solicitud_id');
    }

    public function tipoDocumento()
    {
        return $this->belongsTo(TipoDocumento::class, 'tipo_documento_id');
    }

    public function validador()
    {
        return $this->belongsTo(User::class, 'validado_por');
    }
}

