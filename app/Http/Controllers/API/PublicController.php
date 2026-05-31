<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\JsonResponse;

class PublicController extends Controller
{
    /** Quick reachability check — no auth, no DB needed */
    public function ping(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'API is reachable',
            'time'    => now()->toIso8601String(),
            'env'     => app()->environment(),
        ]);
    }

    /** Public platform settings (name, logo, colors) — used by frontend before login */
    public function settings(): JsonResponse
    {
        $keys = [
            'platform_name_en',
            'platform_name_ar',
            'primary_color',
            'secondary_color',
            'accent_color',
            'logo_path',
            'default_language',
        ];

        $settings = [];
        foreach ($keys as $key) {
            $settings[$key] = Setting::getValue($key);
        }

        return response()->json([
            'success' => true,
            'data'    => $settings,
        ]);
    }
}
