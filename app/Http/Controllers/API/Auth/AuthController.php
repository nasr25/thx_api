<?php

namespace App\Http\Controllers\API\Auth;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Services\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    public function __construct(protected AuthService $authService) {}

    // ─── Windows Authentication (Employee auto-login via IIS) ────────────────
    //
    // Identity comes EXCLUSIVELY from the IIS Windows Authentication server
    // variables (Kerberos/NTLM). We deliberately never read $request->user() /
    // auth()->user() here: this endpoint must trust IIS, not any Laravel
    // session or stray Bearer token. In production IIS enforces the challenge
    // because Anonymous Auth is disabled on this path; the PHP-side 401 below
    // is a safety net / local-dev path.

    public function windowsAuth(Request $request): JsonResponse
    {
        $identity = $this->resolveWindowsIdentity($request);
        $username = $identity !== null ? $this->normalizeWindowsUsername($identity) : null;

        $debug = config('windows_auth.debug')
            ? $this->windowsAuthDebug($request, $identity, $username)
            : null;

        // 1. No Windows identity at all → IIS did not authenticate the caller.
        if ($username === null) {
            Log::warning('Windows authentication not detected on /api/auth/windows', [
                'ip'          => $request->ip(),
                'user_agent'  => $request->userAgent(),
                'LOGON_USER'  => $_SERVER['LOGON_USER']  ?? null,
                'AUTH_USER'   => $_SERVER['AUTH_USER']   ?? null,
                'REMOTE_USER' => $_SERVER['REMOTE_USER'] ?? null,
            ]);

            return response()->json($this->withoutNulls([
                'success' => false,
                'message' => 'Windows authentication not detected.',
                'code'    => 'WINDOWS_AUTH_REQUIRED',
                'hint'    => app()->isLocal()
                    ? 'Local dev: send the "X-Windows-User: DOMAIN\\user" header, or set USERNAME / USERDOMAIN env vars.'
                    : 'Enable IIS Windows Authentication (and disable Anonymous) for the api/auth/windows path.',
                'debug'   => $debug,
            ]), 401, ['WWW-Authenticate' => 'Negotiate']);
        }

        // 2. Identity present → resolve/create the user and issue a Bearer token.
        try {
            $result = $this->authService->windowsAuthToken($username, $identity);
        } catch (\Throwable $e) {
            $status  = ($e->getCode() >= 400 && $e->getCode() < 600) ? (int) $e->getCode() : 500;
            $message = $status < 500
                ? $e->getMessage()
                : (app()->isProduction() ? __('messages.server_error') : $e->getMessage());

            Log::error("Windows auth failed for [{$username}]: {$e->getMessage()}");

            return response()->json($this->withoutNulls([
                'success' => false,
                'message' => $message,
                'code'    => 'WINDOWS_AUTH_FAILED',
                'debug'   => $debug,
            ]), $status);
        }

        return response()->json($this->withoutNulls([
            'success'    => true,
            'message'    => __('messages.login_success'),
            'data'       => [
                'user'       => new UserResource($result['user']),
                'token'      => $result['token'],
                'expires_at' => $result['expires_at'],
            ],
            'debug'      => $debug,
        ]));
    }

    // ─── Windows identity helpers (IIS server variables only) ────────────────

    /**
     * Resolve the raw Windows identity (e.g. "DOMAIN\\john.doe") set by IIS
     * after the Kerberos/NTLM handshake. Precedence: the IIS-native LOGON_USER
     * first, then AUTH_USER and REMOTE_USER which various IIS/CGI configs set.
     * Falls back to an explicit header or the OS session only in local dev.
     */
    private function resolveWindowsIdentity(Request $request): ?string
    {
        $candidates = [
            $_SERVER['LOGON_USER']  ?? null,
            $_SERVER['AUTH_USER']   ?? null,
            $_SERVER['REMOTE_USER'] ?? null,
            // Same variables via Laravel's server bag (some FastCGI setups route here):
            $request->server('LOGON_USER'),
            $request->server('AUTH_USER'),
            $request->server('REMOTE_USER'),
            // Reverse-proxy / manual testing override:
            $request->header('X-Windows-User'),
        ];

        foreach ($candidates as $value) {
            if (is_string($value) && trim($value) !== '') {
                return trim($value);
            }
        }

        // Local development only: read the PHP process's Windows session.
        if (app()->isLocal()) {
            return $this->resolveLocalWindowsIdentity();
        }

        return null;
    }

    /**
     * Normalize "DOMAIN\\username" or "username@domain.local" to "username".
     */
    private function normalizeWindowsUsername(string $identity): ?string
    {
        $identity = trim($identity);

        if (str_contains($identity, '\\')) {                 // DOMAIN\username
            $identity = substr($identity, strrpos($identity, '\\') + 1);
        } elseif (str_contains($identity, '@')) {            // username@domain.local
            $identity = strstr($identity, '@', true);
        }

        $identity = strtolower(trim($identity));

        return $identity !== '' ? $identity : null;
    }

    /**
     * Local-dev fallback: derive the identity from the OS session when running
     * `php artisan serve` on a domain-joined machine. Never used in production.
     */
    private function resolveLocalWindowsIdentity(): ?string
    {
        $username = getenv('USERNAME') ?: null;
        $domain   = getenv('USERDOMAIN') ?: getenv('COMPUTERNAME') ?: null;

        if ($username && !in_array(strtoupper((string) $domain), ['BUILTIN', 'NT AUTHORITY', ''], true)) {
            return $domain ? "{$domain}\\{$username}" : $username;
        }

        return null;
    }

    /**
     * Temporary diagnostics surfaced when WINDOWS_AUTH_DEBUG=true.
     * Exposes account names — keep disabled outside active troubleshooting.
     */
    private function windowsAuthDebug(Request $request, ?string $identity, ?string $username): array
    {
        return [
            'server_variables' => [
                'LOGON_USER'  => $_SERVER['LOGON_USER']  ?? null,
                'AUTH_USER'   => $_SERVER['AUTH_USER']   ?? null,
                'REMOTE_USER' => $_SERVER['REMOTE_USER'] ?? null,
                'AUTH_TYPE'   => $_SERVER['AUTH_TYPE']   ?? null,
            ],
            'x_windows_user_header' => $request->header('X-Windows-User'),
            'resolved_identity'     => $identity,
            'normalized_username'   => $username,
            'app_environment'       => app()->environment(),
        ];
    }

    /** Drop null entries so optional keys (e.g. debug) don't appear when unset. */
    private function withoutNulls(array $payload): array
    {
        return array_filter($payload, static fn ($value) => $value !== null);
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
