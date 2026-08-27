<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;

class RoleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string ...$roles
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        if (!Auth::check()) {
            return redirect()->route('login')->with('error', 'Silakan login terlebih dahulu untuk mengakses sistem.');
        }

        $user = Auth::user();
        $userRole = strtolower($user->role ?? 'ar');

        $allowedRoles = array_map('strtolower', $roles);

        // Jika user adalah admin dan admin termasuk di allowed roles, atau role match
        if (in_array($userRole, $allowedRoles) || ($userRole === 'admin' && empty($roles))) {
            return $next($request);
        }

        // Jika role tidak diizinkan, tolak akses
        if ($request->expectsJson()) {
            return response()->json(['message' => 'Akses ditolak. Anda tidak memiliki izin untuk tindakan ini.'], 403);
        }

        abort(403, 'Akses Ditolak: Anda tidak memiliki wewenang untuk mengakses modul ini.');
    }
}
