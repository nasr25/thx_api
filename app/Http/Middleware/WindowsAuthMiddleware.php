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
        $windowsIdentity = $this->resolveWindowsIdentity($request);

        if (!$windowsIdentity) {
            return response()->json([
                'success' => false,
                'message' => 'Windows identity not found.',
                'code'    => 'WINDOWS_AUTH_REQUIRED',
                'hint'    => app()->isLocal()
                    ? 'Running locally: set USERNAME / USERDOMAIN environment variables or use the X-Windows-User header.'
                    : 'Ensure IIS Windows Authentication is enabled for the /api/auth/windows path.',
            ], 401, ['WWW-Authenticate' => 'Negotiate']);
        }

        $username = $this->extractUsername($windowsIdentity);

        if (!$username) {
            return response()->json([
                'success' => false,
                'message' => 'Could not parse Windows identity: ' . $windowsIdentity,
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

            Auth::login($user);

            return $next($request);
        } catch (\Exception $e) {
            Log::error("WindowsAuth error for {$username}: " . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Authentication error: ' . $e->getMessage(),
            ], 500);
        }
    }

    // ─── Identity resolution (priority order) ────────────────────────────────

    private function resolveWindowsIdentity(Request $request): ?string
    {
        // 1. IIS Windows Auth — set by IIS after NTLM/Kerberos handshake (production)
        $identity = $_SERVER['LOGON_USER']
            ?? $_SERVER['AUTH_USER']
            ?? $request->server('LOGON_USER')
            ?? $request->server('AUTH_USER')
            ?? null;

        if ($identity) {
            return $identity;
        }

        // 2. Explicit header — for reverse proxy setups or manual testing
        //    e.g.  curl -H "X-Windows-User: DOMAIN\john.doe" ...
        $headerUser = $request->header('X-Windows-User');
        if ($headerUser) {
            return $headerUser;
        }

        // 3. Local development fallback — reads the current Windows login session.
        //    On a domain-joined PC running "php artisan serve", the PHP process
        //    inherits the logged-in user's environment variables automatically.
        return $this->resolveLocalWindowsIdentity();
    }

    private function resolveLocalWindowsIdentity(): ?string
    {
        // USERNAME  = e.g. "john.doe"
        // USERDOMAIN = e.g. "COMPANY"  (domain name on domain-joined machines)
        // COMPUTERNAME = fallback when not domain-joined (local account)
        $username = getenv('USERNAME') ?: null;
        $domain   = getenv('USERDOMAIN') ?: getenv('COMPUTERNAME') ?: null;

        if ($username) {
            // Skip "BUILTIN", "NT AUTHORITY", "SYSTEM" accounts
            if (in_array(strtoupper((string) $domain), ['BUILTIN', 'NT AUTHORITY', ''], true)) {
                return null;
            }
            return $domain ? "{$domain}\\{$username}" : $username;
        }

        // Last resort: run whoami (works on Windows & Linux)
        try {
            $whoami = trim((string) shell_exec('whoami 2>&1'));
            if (
                !empty($whoami)
                && !str_contains(strtolower($whoami), 'not recognized')
                && !str_contains(strtolower($whoami), 'error')
            ) {
                return $whoami;
            }
        } catch (\Throwable) {
        }

        return null;
    }

    // ─── Parse the raw identity into a plain username ─────────────────────────

    private function extractUsername(string $identity): ?string
    {
        $identity = trim($identity);

        // DOMAIN\username
        if (str_contains($identity, '\\')) {
            [, $user] = explode('\\', $identity, 2);
            return strtolower(trim($user)) ?: null;
        }

        // username@domain.com
        if (str_contains($identity, '@')) {
            [$user] = explode('@', $identity, 2);
            return strtolower(trim($user)) ?: null;
        }

        return strtolower($identity) ?: null;
    }

    // ─── Resolve DB user (find → sync from AD → auto-create) ─────────────────

    private function resolveUser(string $username, string $windowsIdentity): ?User
    {
        // 1. Existing user in local DB
        $user = User::where('username', $username)->first();

        if ($user) {
            $user->update(['last_login_at' => now()]);
            return $user;
        }

        // 2. Sync from Active Directory (if LDAP is configured in .env)
        if ($this->ldapService->isConfigured()) {
            $ldapData = $this->ldapService->findUser($username);
            if ($ldapData) {
                return $this->authService->syncFromLdap($ldapData, $windowsIdentity);
            }
        }

        // 3. Auto-create from Windows identity alone (no AD needed)
        return $this->authService->createFromWindowsIdentity($username, $windowsIdentity);
    }
}
