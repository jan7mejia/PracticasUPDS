<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $table = 'users';

    protected $fillable = [
        'rol_id',
        'name',
        'email',
        'password',
        'activo',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'activo' => 'boolean',
    ];

    public function rol()
    {
        return $this->belongsTo(Role::class, 'rol_id');
    }

    public function estudiante()
    {
        return $this->hasOne(Estudiante::class, 'usuario_id');
    }

    public function coordinador()
    {
        return $this->hasOne(Coordinador::class, 'usuario_id');
    }

    public function tutor()
    {
        return $this->hasOne(DocenteTutor::class, 'usuario_id');
    }

    public function responsableOrganizacion()
    {
        return $this->hasOne(ResponsableOrganizacion::class, 'usuario_id');
    }

    public function hasRole(string $codigo): bool
    {
        return $this->rol && $this->rol->codigo === $codigo;
    }

    public function isAdmin(): bool
    {
        return $this->hasRole('administrador');
    }

    public function isCoordinador(): bool
    {
        return $this->hasRole('coordinador');
    }

    public function isTutor(): bool
    {
        return $this->hasRole('tutor');
    }

    public function isEstudiante(): bool
    {
        return $this->hasRole('estudiante');
    }
}
