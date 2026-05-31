<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ForceJsonResponse
{
    /**
     * Force every API request to be treated as wanting JSON.
     *
     * Without this, an unauthenticated request that lacks an
     * "Accept: application/json" header makes Laravel try to redirect to a
     * non-existent "login" route, producing "Route [login] not defined" (500)
     * instead of a clean 401 JSON response.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $request->headers->set('Accept', 'application/json');

        return $next($request);
    }
}
