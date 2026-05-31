<?php

namespace App\Services;

use LdapRecord\Connection;
use LdapRecord\Models\ActiveDirectory\User as LdapUser;
use Illuminate\Support\Facades\Log;

class LdapService
{
    protected ?Connection $connection = null;

    public function __construct()
    {
        if ($this->isConfigured()) {
            $this->connection = new Connection([
                'hosts'            => [config('ldap.connections.default.hosts.0')],
                'port'             => config('ldap.connections.default.port'),
                'base_dn'          => config('ldap.connections.default.base_dn'),
                'username'         => config('ldap.connections.default.username'),
                'password'         => config('ldap.connections.default.password'),
                'timeout'          => config('ldap.connections.default.timeout'),
                'use_ssl'          => config('ldap.connections.default.use_ssl'),
                'use_tls'          => config('ldap.connections.default.use_tls'),
                'version'          => 3,
            ]);
        }
    }

    public function isConfigured(): bool
    {
        return !empty(config('ldap.connections.default.hosts.0'))
            && config('ldap.connections.default.hosts.0') !== 'ldap.company.com';
    }

    public function authenticate(string $username, string $password): bool
    {
        if (!$this->connection) {
            return false;
        }

        try {
            $userDn = $this->findUserDn($username);
            if (!$userDn) {
                return false;
            }

            $this->connection->auth()->attempt($userDn, $password);
            return true;
        } catch (\LdapRecord\Auth\BindException $e) {
            Log::warning("LDAP auth failed for user {$username}: " . $e->getMessage());
            return false;
        } catch (\Exception $e) {
            Log::error("LDAP error: " . $e->getMessage());
            return false;
        }
    }

    public function findUser(string $username): ?array
    {
        if (!$this->connection) {
            return null;
        }

        try {
            $this->connection->connect();

            $user = $this->connection->query()
                ->in(env('LDAP_USER_SEARCH_BASE', config('ldap.connections.default.base_dn')))
                ->where('sAMAccountName', '=', $username)
                ->orWhere('userPrincipalName', '=', $username)
                ->first();

            if (!$user) {
                return null;
            }

            return $this->mapLdapUser($user);
        } catch (\Exception $e) {
            Log::error("LDAP findUser error: " . $e->getMessage());
            return null;
        }
    }

    public function searchUsers(string $term, int $limit = 10): array
    {
        if (!$this->connection) {
            return [];
        }

        try {
            $this->connection->connect();

            $results = $this->connection->query()
                ->in(env('LDAP_USER_SEARCH_BASE', config('ldap.connections.default.base_dn')))
                ->where('objectClass', '=', 'user')
                ->orWhere('cn', 'contains', $term)
                ->orWhere('sAMAccountName', 'contains', $term)
                ->orWhere('mail', 'contains', $term)
                ->limit($limit)
                ->get();

            return array_map([$this, 'mapLdapUser'], $results);
        } catch (\Exception $e) {
            Log::error("LDAP searchUsers error: " . $e->getMessage());
            return [];
        }
    }

    public function getUserPhoto(string $username): ?string
    {
        if (!$this->connection) {
            return null;
        }

        try {
            $this->connection->connect();

            $user = $this->connection->query()
                ->in(env('LDAP_USER_SEARCH_BASE', config('ldap.connections.default.base_dn')))
                ->where('sAMAccountName', '=', $username)
                ->first();

            if (!$user || empty($user['thumbnailphoto'][0])) {
                return null;
            }

            return base64_encode($user['thumbnailphoto'][0]);
        } catch (\Exception $e) {
            Log::error("LDAP getUserPhoto error: " . $e->getMessage());
            return null;
        }
    }

    public function isUserInAdminGroup(string $username): bool
    {
        $adminGroup = env('LDAP_ADMIN_GROUP', '');
        if (empty($adminGroup)) {
            return false;
        }

        try {
            $this->connection->connect();

            $user = $this->connection->query()
                ->in(env('LDAP_USER_SEARCH_BASE', config('ldap.connections.default.base_dn')))
                ->where('sAMAccountName', '=', $username)
                ->first();

            if (!$user || empty($user['memberof'])) {
                return false;
            }

            $groups = is_array($user['memberof']) ? $user['memberof'] : [$user['memberof']];
            return in_array($adminGroup, $groups, true);
        } catch (\Exception $e) {
            Log::error("LDAP isUserInAdminGroup error: " . $e->getMessage());
            return false;
        }
    }

    protected function findUserDn(string $username): ?string
    {
        try {
            $this->connection->connect();

            $user = $this->connection->query()
                ->in(env('LDAP_USER_SEARCH_BASE', config('ldap.connections.default.base_dn')))
                ->where('sAMAccountName', '=', $username)
                ->first();

            return $user['dn'] ?? null;
        } catch (\Exception $e) {
            return null;
        }
    }

    protected function mapLdapUser(array $entry): array
    {
        $getAttribute = function (array $entry, string $key): ?string {
            $val = $entry[$key] ?? null;
            if (is_array($val)) {
                return $val[0] ?? null;
            }
            return $val;
        };

        $guid = $getAttribute($entry, 'objectguid');
        if ($guid && !mb_detect_encoding($guid, 'UTF-8', true)) {
            $guid = bin2hex($guid);
        }

        return [
            'ldap_guid'   => $guid,
            'ldap_domain' => parse_url(config('ldap.connections.default.hosts.0'), PHP_URL_HOST) ?? 'local',
            'username'    => $getAttribute($entry, 'samaccountname') ?? $getAttribute($entry, 'uid'),
            'email'       => $getAttribute($entry, 'mail') ?? $getAttribute($entry, 'userprincipalname'),
            'full_name'   => $getAttribute($entry, 'displayname') ?? $getAttribute($entry, 'cn'),
            'department'  => $getAttribute($entry, 'department'),
            'job_title'   => $getAttribute($entry, 'title'),
        ];
    }
}
