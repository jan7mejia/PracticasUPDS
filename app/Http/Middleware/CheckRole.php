<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        if (!auth()->check()) {
            return redirect()->route('login');
        }

        $user = auth()->user();

        // Seguridad: Si la cuenta ha sido desactivada, invalidar sesión inmediatamente
        if (!$user->activo) {
            auth()->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            return redirect()->route('login')->withErrors(['email' => 'Tu cuenta se encuentra desactivada. Contacta al administrador del sistema.']);
        }

        if (empty($roles)) {
            return $next($request);
        }

        if ($user->rol && in_array($user->rol->codigo, $roles)) {
            return $next($request);
        }

        abort(403, 'Acceso no autorizado para el rol actual.');
    }
}

