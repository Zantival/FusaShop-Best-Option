<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class SecurityHeaders
{
    /**
     * OWASP Security Headers Implementation
     */
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        // Prevent page from being displayed in an iframe (Anti-Clickjacking)
        $response->headers->set('X-Frame-Options', 'DENY');
        
        // Prevent browser from sniffing content type
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        
        // Enable XSS Protection in older browsers
        $response->headers->set('X-XSS-Protection', '1; mode=block');
        
        // Strict Transport Security (Force HTTPS)
        if ($request->secure()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        // Basic Content Security Policy (Allow trusted sources only)
        $response->headers->set('Content-Security-Policy', "default-src 'self'; script-src 'self' 'unsafe-inline' https://cdn.tailwindcss.com https://cdn.jsdelivr.net https://fonts.googleapis.com https://unpkg.com; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://unpkg.com; font-src 'self' https://fonts.gstatic.com; img-src 'self' data: http: https:; connect-src 'self' https://cdn.jsdelivr.net https://nominatim.openstreetmap.org https://router.project-osrm.org;");

        return $response;
    }
}
