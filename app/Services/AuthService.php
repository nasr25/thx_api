<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\Department;
use App\Models\User;
use App\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class AuthService
{
    public function __construct(
        protected UserRepositoryInterface $userRepository,
        protected LdapService $ldapService,
    ) {}

    // ─── Admin Form Login ─────────────────────────────────────────────────────

    public function adminLogin(string $username, string $password): array
    {
        $user = $this->userRepository->findByUsername($username)
            ?? $this->userRepository->findByEmail($username);

        if (!$user || !Hash::check($password, $user->password)) {
            throw new \Exception(__('messages.invalid_credentials'), 401);
        }

        if (!$user->is_active) {
            throw new \Exception(__('messages.account_inactive'), 403);
        }

        if (!$user->hasAnyRole(['admin', 'super-admin'])) {
            throw new \Exception(__('messages.unauthorized'), 403);
        }

        return $this->issueToken($user, 'admin_form_login');
    }

    // ─── Windows Authentication (IIS) ────────────────────────────────────────

    /**
     * Issue a Bearer token for a Windows-authenticated user.
     *
     * Receives the normalized username (e.g. "john.doe") taken from the IIS
     * server variables — NOT a Laravel User. Resolves the local user, syncing
     * from Active Directory or auto-creating on first sight, then issues a token.
     *
     * @param  string       $username     Normalized Windows username.
     * @param  string|null  $rawIdentity  Original IIS value (e.g. "DOMAIN\\john.doe"), for AD sync/logging.
     */
    public function windowsAuthToken(string $username, ?string $rawIdentity = null): array
    {
        $user = $this->resolveWindowsUser($username, $rawIdentity ?? $username);

        if (!$user->is_active) {
            throw new \Exception(__('messages.account_inactive'), 403);
        }

        return $this->issueToken($user, 'windows_auth');
    }

    /**
     * Find the local user for a Windows username, or provision one:
     *   1. Existing local record (fast path).
     *   2. Sync from Active Directory when LDAP is configured.
     *   3. Auto-create a minimal record (no AD required).
     */
    private function resolveWindowsUser(string $username, string $rawIdentity): User
    {
        $user = $this->userRepository->findByUsername($username);

        if ($user) {
            $user->update(['last_login_at' => now()]);
            return $user;
        }

        if ($this->ldapService->isConfigured()) {
            $ldapData = $this->ldapService->findUser($username);
            if ($ldapData) {
                return $this->syncFromLdap($ldapData, $rawIdentity);
            }
        }

        return $this->createFromWindowsIdentity($username, $rawIdentity);
    }

    public function syncFromLdap(array $ldapData, string $windowsIdentity = ''): User
    {
        $departmentId = $this->resolveDepartment($ldapData['department'] ?? null);

        $userData = [
            'username'      => $ldapData['username'],
            'email'         => $ldapData['email'] ?? ($ldapData['username'] . '@company.com'),
            'full_name'     => $ldapData['full_name'] ?? $ldapData['username'],
            'full_name_ar'  => $ldapData['full_name_ar'] ?? null,
            'password'      => Hash::make(bin2hex(random_bytes(16))), // Random — can't log in with password
            'department_id' => $departmentId,
            'job_title'     => $ldapData['job_title'] ?? null,
            'is_active'     => true,
            'ldap_guid'     => $ldapData['ldap_guid'] ?? null,
            'ldap_domain'   => $ldapData['ldap_domain'] ?? null,
            'last_login_at' => now(),
        ];

        $user = User::updateOrCreate(
            ['username' => $ldapData['username']],
            $userData
        );

        if (!$user->hasAnyRole(['admin', 'super-admin', 'employee'])) {
            $user->assignRole('employee');
        }

        return $user;
    }

    public function createFromWindowsIdentity(string $username, string $windowsIdentity): User
    {
        // Fallback: create a minimal record when AD is not reachable
        $user = User::firstOrCreate(
            ['username' => $username],
            [
                'email'         => $username . '@company.local',
                'full_name'     => ucwords(str_replace(['.', '_', '-'], ' ', $username)),
                'password'      => Hash::make(bin2hex(random_bytes(16))),
                'is_active'     => true,
                'last_login_at' => now(),
            ]
        );

        if (!$user->hasAnyRole(['admin', 'super-admin', 'employee'])) {
            $user->assignRole('employee');
        }

        return $user;
    }

    // ─── Logout ───────────────────────────────────────────────────────────────

    public function logout(User $user): void
    {
        $user->currentAccessToken()?->delete();
        ActivityLog::log('logout', 'User logged out');
    }

    public function updateLanguage(User $user, string $lang): void
    {
        $user->update(['preferred_language' => in_array($lang, ['en', 'ar']) ? $lang : 'en']);
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────

    private function issueToken(User $user, string $source): array
    {
        // Revoke previous tokens of same type
        $user->tokens()->where('name', 'api-token')->delete();

        // Windows auth tokens last 12 hours; admin tokens 8 hours
        $hours = $source === 'windows_auth' ? 12 : 8;
        $token = $user->createToken('api-token', ['*'], now()->addHours($hours));

        $user->update(['last_login_at' => now()]);

        ActivityLog::log('login', "Authenticated via {$source}");

        app()->setLocale($user->preferred_language ?? 'en');

        return [
            'user'       => $user->load(['department', 'roles']),
            'token'      => $token->plainTextToken,
            'expires_at' => now()->addHours($hours)->toIso8601String(),
        ];
    }

    private function resolveDepartment(?string $departmentName): ?int
    {
        if (!$departmentName) {
            return null;
        }

        $dept = Department::where('name', 'like', "%{$departmentName}%")
                          ->orWhere('name_ar', 'like', "%{$departmentName}%")
                          ->first();

        if ($dept) {
            return $dept->id;
        }

        return Department::create(['name' => $departmentName, 'is_active' => true])->id;
    }
}
