<?php

namespace App\Http\Controllers\API\Auth;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Services\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    public function __construct(protected AuthService $authService) {}

    // ─── Windows Authentication (Employee auto-login via IIS) ────────────────
    // Route is protected by WindowsAuthMiddleware which reads LOGON_USER from IIS.
    // The middleware authenticates the user and sets Auth::user() before this runs.

    public function windowsAuth(Request $request): JsonResponse
    {
        $user = $request->user() ?? auth()->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Windows authentication not detected.',
                'code'    => 'WINDOWS_AUTH_REQUIRED',
            ], 401);
        }

        $result = $this->authService->windowsAuthToken($user);

        return response()->json([
            'success'    => true,
            'message'    => __('messages.login_success'),
            'data'       => [
                'user'       => new UserResource($result['user']),
                'token'      => $result['token'],
                'expires_at' => $result['expires_at'],
            ],
        ]);
    }

    // ─── Admin Form Login ─────────────────────────────────────────────────────

    public function adminLogin(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'username' => 'required|string|max:100',
            'password' => 'required|string|min:6',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => __('messages.validation_error'),
                'errors'  => $validator->errors(),
            ], 422);
        }

        try {
            $result = $this->authService->adminLogin(
                $request->input('username'),
                $request->input('password')
            );

            return response()->json([
                'success' => true,
                'message' => __('messages.login_success'),
                'data'    => [
                    'user'       => new UserResource($result['user']),
                    'token'      => $result['token'],
                    'expires_at' => $result['expires_at'],
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], $e->getCode() ?: 401);
        }
    }

    // ─── Shared Endpoints ─────────────────────────────────────────────────────

    public function logout(Request $request): JsonResponse
    {
        $this->authService->logout($request->user());

        return response()->json([
            'success' => true,
            'message' => __('messages.logout_success'),
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        $user = $request->user()->load(['department', 'roles']);

        return response()->json([
            'success' => true,
            'data'    => new UserResource($user),
        ]);
    }

    public function refresh(Request $request): JsonResponse
    {
        $user = $request->user();
        $user->currentAccessToken()->delete();

        $token = $user->createToken('api-token', ['*'], now()->addHours(12));

        return response()->json([
            'success' => true,
            'data'    => [
                'token'      => $token->plainTextToken,
                'expires_at' => now()->addHours(12)->toIso8601String(),
            ],
        ]);
    }

    public function updateLanguage(Request $request): JsonResponse
    {
        $request->validate(['language' => 'required|in:en,ar']);
        $this->authService->updateLanguage($request->user(), $request->input('language'));

        return response()->json([
            'success' => true,
            'message' => __('messages.language_updated'),
        ]);
    }
}
