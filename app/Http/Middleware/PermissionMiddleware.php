<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PermissionMiddleware
{
    /** Izinkan bila user punya salah satu izin yang diminta (admin selalu lolos). */
    public function handle(Request $request, Closure $next, string ...$permissions): Response
    {
        $user = $request->user();

        $boleh = $user && collect($permissions)->contains(fn ($p) => $user->canAccess($p));

        abort_unless($boleh, 403, 'Anda tidak punya akses ke halaman ini.');

        return $next($request);
    }
}
