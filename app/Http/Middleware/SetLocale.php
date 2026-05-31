<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        // 1. From authenticated user preference
        if ($user = $request->user()) {
            app()->setLocale($user->preferred_language ?? 'en');
            return $next($request);
        }

        // 2. From Accept-Language header
        $acceptLang = $request->header('Accept-Language', 'en');
        $lang = str_starts_with($acceptLang, 'ar') ? 'ar' : 'en';

        // 3. From query param (for unauthenticated requests)
        $queryLang = $request->query('lang');
        if (in_array($queryLang, ['en', 'ar'])) {
            $lang = $queryLang;
        }

        app()->setLocale($lang);

        return $next($request);
    }
}
