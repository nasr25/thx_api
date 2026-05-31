<?php

namespace App\Http\Controllers\API\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateSettingsRequest;
use App\Services\SettingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    public function __construct(protected SettingService $settingService) {}

    public function index(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data'    => $this->settingService->getAll(),
        ]);
    }

    public function update(UpdateSettingsRequest $request): JsonResponse
    {
        $settings = $this->settingService->update($request->validated());

        return response()->json([
            'success' => true,
            'message' => __('messages.settings_updated'),
            'data'    => $settings,
        ]);
    }

    public function uploadLogo(Request $request): JsonResponse
    {
        $request->validate([
            'logo' => 'required|image|mimes:png,jpg,jpeg,svg|max:2048',
        ]);

        $url = $this->settingService->uploadLogo($request->file('logo'));

        return response()->json([
            'success' => true,
            'message' => __('messages.logo_uploaded'),
            'data'    => ['url' => $url],
        ]);
    }
}
