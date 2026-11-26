<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class PerfilController extends Controller
{
    public function edit()
    {
        $user = Auth::user();
        return view('perfil.edit', compact('user'));
    }

    public function update(Request $request)
    {
        $user = Auth::user();

        if ($user->isEstudiante()) {
            // Regla académica: El estudiante NO puede modificar su nombre ni su correo institucional.
            // Solo puede actualizar datos de contacto autorizados (teléfono/celular).
            $request->validate([
                'telefono' => ['nullable', 'string', 'max:25', 'regex:/^(\+?591[\s\-]?)?(\(?4\)?[\s\-]?)?[467]\d{6,7}$/'],
            ], [
                'telefono.regex' => 'El teléfono debe ser un número válido de Bolivia / Cochabamba (celular de 8 dígitos empezando en 6 o 7, o fijo empezando en 4).',
            ]);

            if ($user->estudiante) {
                $user->estudiante->update([
                    'telefono' => $request->telefono,
                ]);
            }

            return redirect()->route('perfil.edit')->with('success', 'Datos de contacto actualizados correctamente.');
        }

        if ($user->isTutor()) {
            // Regla académica: El docente tutor NO puede modificar su nombre, correo institucional, rol ni estado.
            // Solo puede actualizar su información personal secundaria autorizada (teléfono de contacto).
            $request->validate([
                'telefono' => ['nullable', 'string', 'max:25', 'regex:/^(\+?591[\s\-]?)?(\(?4\)?[\s\-]?)?[467]\d{6,7}$/'],
            ], [
                'telefono.regex' => 'El teléfono debe ser un número válido de Bolivia / Cochabamba (celular de 8 dígitos empezando en 6 o 7, o fijo empezando en 4).',
            ]);

            if ($user->tutor) {
                $user->tutor->update([
                    'telefono' => $request->telefono,
                ]);
            }

            return redirect()->route('perfil.edit')->with('success', 'Teléfono de contacto actualizado correctamente.');
        }

        if ($user->hasRole('coordinador')) {
            // Regla académica: El coordinador NO puede modificar su nombre institucional, correo, rol ni estado.
            // Solo puede actualizar sus datos de contacto secundarios autorizados (teléfono).
            $request->validate([
                'telefono' => ['nullable', 'string', 'max:25', 'regex:/^(\+?591[\s\-]?)?(\(?4\)?[\s\-]?)?[467]\d{6,7}$/'],
            ], [
                'telefono.regex' => 'El teléfono debe ser un número válido de Bolivia / Cochabamba (celular de 8 dígitos empezando en 6 o 7, o fijo empezando en 4).',
            ]);

            if ($user->coordinador) {
                $user->coordinador->update([
                    'telefono' => $request->telefono,
                ]);
            }

            return redirect()->route('perfil.edit')->with('success', 'Teléfono de contacto del coordinador actualizado correctamente.');
        }

        if ($user->isAdmin()) {
            return redirect()->route('perfil.edit')->with('info', 'Los datos institucionales del Administrador están protegidos. Para correcciones de identidad, utilice el módulo de Gestión de Usuarios.');
        }

        $request->validate([
            'name'  => ['required', 'string', 'max:160', 'regex:/^[a-zA-ZáéíóúÁÉÍÓÚñÑüÜ\s\.\'\-]+$/u'],
            'email' => 'required|email|max:191|unique:users,email,' . $user->id,
        ], [
            'name.regex' => 'El nombre completo solo debe contener letras y espacios, no se permiten números.',
        ]);

        $user->update([
            'name'  => $request->name,
            'email' => $request->email,
        ]);

        return redirect()->route('perfil.edit')->with('success', 'Perfil actualizado correctamente.');
    }

    public function updatePassword(Request $request)
    {
        $request->validate([
            'current_password'          => 'required',
            'password'                  => ['required', 'confirmed', Password::min(8)],
        ]);

        $user = Auth::user();

        if (!Hash::check($request->current_password, $user->password)) {
            return back()->withErrors(['current_password' => 'La contraseña actual no es correcta.']);
        }

        $user->update(['password' => Hash::make($request->password)]);

        return redirect()->route('perfil.edit')->with('success', '¡Contraseña cambiada exitosamente!');
    }
}

