<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckCookieConsent
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Si la cookie existe, guardamos en sesión para fácil acceso en vistas/otros middlewares
        if ($request->hasCookie('cookie_consent')) {
            session(['cookie_consent' => $request->cookie('cookie_consent')]);
        }

        return $next($request);
    }
}
