<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class PublicController extends Controller
{
    /** No DB, no cache — just proves the API is reachable */
    public function ping(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'API is reachable',
            'env'     => app()->environment(),
        ]);
    }

    /** Public platform branding — safe to call before login */
    public function settings(): JsonResponse
    {
        try {
            $keys = [
                'platform_name_en', 'platform_name_ar',
                'primary_color', 'secondary_color',
                'accent_color', 'logo_path', 'default_language',
            ];

            $data = [];
            foreach ($keys as $key) {
                $data[$key] = \App\Models\Setting::getValue($key);
            }

            return response()->json(['success' => true, 'data' => $data]);
        } catch (\Throwable) {
            // DB not ready — return safe defaults so the frontend can still load
            return response()->json([
                'success' => true,
                'data' => [
                    'platform_name_en' => config('app.name', 'Appreciation Platform'),
                    'platform_name_ar' => 'منصة التقدير',
                    'primary_color'    => '#6366f1',
                    'secondary_color'  => '#f59e0b',
                    'accent_color'     => '#10b981',
                    'logo_path'        => null,
                    'default_language' => 'en',
                ],
            ]);
        }
    }
}
