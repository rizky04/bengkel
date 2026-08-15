<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user || (! in_array($user->role, $roles) && $user->role !== 'admin')) {
            abort(403, 'Anda tidak punya akses ke halaman ini.');
        }

        return $next($request);
    }
}
