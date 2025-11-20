<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class OwnerRoleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Verifica si el usuario está autenticado y si su rol es 'propietario'
        if ($request->user() && $request->user()->role === 'propietario') {
            return $next($request);
        }

        // Si no es propietario, devuelve error 403 (Prohibido)
        return response()->json([
            'message' => 'Acceso denegado. Se requiere rol de propietario.'
        ], 403);
    }
}
