<?php

namespace App\Http\Middleware;

use App\Models\User;
use App\Services\AuthService;
use App\Services\LdapService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class WindowsAuthMiddleware
{
    public function __construct(
        protected LdapService $ldapService,
        protected AuthService $authService,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        // IIS sets LOGON_USER or AUTH_USER when Windows Authentication is active
        $windowsIdentity = $this->resolveWindowsIdentity($request);

        if (!$windowsIdentity) {
            return response()->json([
                'success' => false,
                'message' => 'Windows authentication identity not found. Ensure IIS Windows Authentication is enabled for this endpoint.',
                'code'    => 'WINDOWS_AUTH_REQUIRED',
            ], 401, ['WWW-Authenticate' => 'Negotiate']);
        }

        $username = $this->extractUsername($windowsIdentity);

        if (!$username) {
            return response()->json([
                'success' => false,
                'message' => 'Could not parse Windows identity.',
            ], 401);
        }

        try {
            $user = $this->resolveUser($username, $windowsIdentity);

            if (!$user || !$user->is_active) {
                return response()->json([
                    'success' => false,
                    'message' => __('messages.account_inactive'),
                ], 403);
            }

            // Bind to auth for this request
            Auth::login($user);

            return $next($request);
        } catch (\Exception $e) {
            Log::error("WindowsAuth error for {$username}: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Authentication failed.',
            ], 500);
        }
    }

    private function resolveWindowsIdentity(Request $request): ?string
    {
        return $_SERVER['LOGON_USER']
            ?? $_SERVER['AUTH_USER']
            ?? $request->server('LOGON_USER')
            ?? $request->server('AUTH_USER')
            ?? $request->header('X-Windows-User')    // For testing / reverse proxy
            ?? null;
    }

    private function extractUsername(string $identity): ?string
    {
        // Format: DOMAIN\username
        if (str_contains($identity, '\\')) {
            $parts = explode('\\', $identity, 2);
            return strtolower(trim($parts[1]));
        }

        // Format: username@domain.com
        if (str_contains($identity, '@')) {
            $parts = explode('@', $identity, 2);
            return strtolower(trim($parts[0]));
        }

        return strtolower(trim($identity));
    }

    private function resolveUser(string $username, string $windowsIdentity): ?User
    {
        // 1. Try to find existing user in local DB
        $user = User::where('username', $username)
            ->orWhere('username', strtolower($username))
            ->first();

        if ($user) {
            // Update last login
            $user->update(['last_login_at' => now()]);
            return $user;
        }

        // 2. Pull from Active Directory and create/sync
        if ($this->ldapService->isConfigured()) {
            $ldapData = $this->ldapService->findUser($username);
            if ($ldapData) {
                return $this->authService->syncFromLdap($ldapData, $windowsIdentity);
            }
        }

        // 3. Create minimal user from Windows identity if AD not available
        return $this->authService->createFromWindowsIdentity($username, $windowsIdentity);
    }
}
