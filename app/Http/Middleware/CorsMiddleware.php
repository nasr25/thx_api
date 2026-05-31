<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CorsMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        // Handle preflight OPTIONS instantly — never reaches the router
        if ($request->isMethod('OPTIONS')) {
            $response = response('', 204);
            $this->stamp($response, $request->header('Origin'));
            return $response;
        }

        $response = $next($request);
        $this->stamp($response, $request->header('Origin'));
        return $response;
    }

    private function stamp(Response $response, ?string $origin): void
    {
        // In local development allow every origin — no security risk on localhost
        if (app()->environment('local', 'development')) {
            $response->headers->set('Access-Control-Allow-Origin',      $origin ?: '*');
            $response->headers->set('Access-Control-Allow-Credentials', $origin ? 'true' : 'false');
        } elseif ($origin && $this->isAllowed($origin)) {
            $response->headers->set('Access-Control-Allow-Origin',      $origin);
            $response->headers->set('Access-Control-Allow-Credentials', 'true');
        }

        $response->headers->set('Access-Control-Allow-Methods',
            'GET, POST, PUT, PATCH, DELETE, OPTIONS');
        $response->headers->set('Access-Control-Allow-Headers',
            'Content-Type, Accept, Authorization, X-Requested-With, X-Windows-User, Accept-Language, Origin, Cache-Control');
        $response->headers->set('Access-Control-Max-Age', '3600');
        $response->headers->set('Vary', 'Origin');
    }

    private function isAllowed(string $origin): bool
    {
        $allowed = array_filter([
            env('FRONTEND_URL'),
            'http://localhost:5173',
            'http://localhost:5174',
            'http://127.0.0.1:5173',
        ]);
        return in_array($origin, $allowed, true);
    }
}
