<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CorsMiddleware
{
    private function allowedOrigins(): array
    {
        return array_filter(array_unique([
            'http://localhost:5173',
            'http://localhost:5174',
            'http://localhost:3000',
            'http://127.0.0.1:5173',
            'http://127.0.0.1:5174',
            env('FRONTEND_URL'),
        ]));
    }

    public function handle(Request $request, Closure $next): Response
    {
        $origin = $request->header('Origin');

        // ── Preflight (OPTIONS) — respond immediately, never touch the router ──
        if ($request->isMethod('OPTIONS')) {
            return $this->buildPreflightResponse($origin);
        }

        $response = $next($request);

        $this->addCorsHeaders($response, $origin);

        return $response;
    }

    private function buildPreflightResponse(?string $origin): Response
    {
        $response = response('', 204);
        $this->addCorsHeaders($response, $origin);
        return $response;
    }

    private function addCorsHeaders(Response $response, ?string $origin): void
    {
        if ($origin && in_array($origin, $this->allowedOrigins(), true)) {
            // Reflect the exact origin back (required when credentials: true)
            $response->headers->set('Access-Control-Allow-Origin',      $origin);
            $response->headers->set('Access-Control-Allow-Credentials', 'true');
        }

        $response->headers->set('Access-Control-Allow-Methods',
            'GET, POST, PUT, PATCH, DELETE, OPTIONS');

        $response->headers->set('Access-Control-Allow-Headers',
            'Content-Type, Accept, Authorization, X-Requested-With, X-Windows-User, Accept-Language, Origin, Cache-Control');

        $response->headers->set('Access-Control-Max-Age', '3600');

        // Vary header tells browsers/CDNs the response differs per Origin
        $response->headers->set('Vary', 'Origin');
    }
}
