<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AuditoriaAdministrativa extends Model
{
    protected $table = 'auditoria_administrativa';
    public $timestamps = false; // Only created_at exists and defaults to CURRENT_TIMESTAMP

    protected $fillable = [
        'admin_id',
        'accion',
        'usuario_afectado_id',
        'detalles',
        'motivo',
        'ip_address',
        'created_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function admin()
    {
        return $this->belongsTo(User::class, 'admin_id');
    }

    public function usuarioAfectado()
    {
        return $this->belongsTo(User::class, 'usuario_afectado_id');
    }

    /**
     * Helper to log an administrative action
     */
    public static function registrar($accion, $usuarioAfectadoId = null, $motivo = null, $detalles = null)
    {
        try {
            return self::create([
                'admin_id' => auth()->id() ?? 1,
                'accion' => $accion,
                'usuario_afectado_id' => $usuarioAfectadoId,
                'detalles' => is_array($detalles) ? json_encode($detalles, JSON_UNESCAPED_UNICODE) : $detalles,
                'motivo' => $motivo,
                'ip_address' => request()->ip(),
                'created_at' => now(),
            ]);
        } catch (\Throwable $e) {
            // Silently fail or log if audit fails so critical operations aren't disrupted
            \Illuminate\Support\Facades\Log::warning("No se pudo registrar auditoría: " . $e->getMessage());
            return null;
        }
    }
}

