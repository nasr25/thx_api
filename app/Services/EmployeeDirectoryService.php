<?php

namespace App\Services;

use App\Models\Department;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class EmployeeDirectoryService
{
    /** Is an external directory endpoint configured? */
    public function isConfigured(): bool
    {
        return !empty(config('directory.url'));
    }

    /**
     * Search the external corporate directory, then upsert each result into the
     * local users table so every employee has a real ID (required to receive
     * appreciations). Returns a collection of User models.
     */
    public function search(string $term): \Illuminate\Database\Eloquent\Collection
    {
        $raw = $this->callEndpoint($term);

        if (empty($raw)) {
            return User::whereRaw('1 = 0')->get(); // empty Eloquent collection
        }

        // Upsert each directory result and collect the resulting IDs.
        $ids = collect($raw)
            ->map(fn ($entry) => $this->mapEntry($entry))
            ->filter(fn ($mapped) => !empty($mapped['username']))
            ->map(fn ($mapped) => $this->upsertUser($mapped))
            ->filter()
            ->pluck('id')
            ->unique()
            ->all();

        if (empty($ids)) {
            return User::whereRaw('1 = 0')->get();
        }

        // Re-query as a proper Eloquent collection with department + counts.
        return User::with('department')
            ->withCount('receivedAppreciations')
            ->whereIn('id', $ids)
            ->orderBy('full_name')
            ->get();
    }

    // ─── HTTP call ────────────────────────────────────────────────────────────

    private function callEndpoint(string $term): array
    {
        $url     = config('directory.url');
        $method  = config('directory.method', 'POST');
        $field   = config('directory.query_field', 'search');
        $timeout = config('directory.timeout', 10);
        $token   = config('directory.token');

        try {
            $request = Http::timeout($timeout)->acceptJson();

            if (!empty($token)) {
                $request = $request->withToken($token);
            }

            $payload = [$field => $term];

            $response = $method === 'GET'
                ? $request->get($url, $payload)
                : $request->post($url, $payload);

            if (!$response->successful()) {
                Log::warning('Directory search returned non-2xx', [
                    'status' => $response->status(),
                    'url'    => $url,
                ]);
                return [];
            }

            $json = $response->json();

            // Unwrap the results array if a key is configured
            $key = config('directory.results_key');
            if (!empty($key) && isset($json[$key]) && is_array($json[$key])) {
                return $json[$key];
            }

            // Common wrapper keys, then fall back to the body itself
            foreach (['data', 'results', 'employees', 'items'] as $candidate) {
                if (isset($json[$candidate]) && is_array($json[$candidate])) {
                    return $json[$candidate];
                }
            }

            return is_array($json) ? $json : [];
        } catch (\Throwable $e) {
            Log::error('Directory search failed: ' . $e->getMessage(), ['url' => $url]);
            return [];
        }
    }

    // ─── Mapping ──────────────────────────────────────────────────────────────

    private function mapEntry(array $entry): array
    {
        $fields = config('directory.fields');
        $pick = function (array $candidates) use ($entry) {
            foreach ($candidates as $key) {
                if (isset($entry[$key]) && $entry[$key] !== '' && $entry[$key] !== null) {
                    return is_array($entry[$key]) ? ($entry[$key][0] ?? null) : $entry[$key];
                }
            }
            return null;
        };

        $username = $pick($fields['username']);

        return [
            'username'   => $username ? strtolower(trim((string) $username)) : null,
            'full_name'  => $pick($fields['full_name']) ?? $username,
            'email'      => $pick($fields['email']),
            'department' => $pick($fields['department']),
            'job_title'  => $pick($fields['job_title']),
            'photo'      => $pick($fields['photo']),
        ];
    }

    private function upsertUser(array $data): ?User
    {
        try {
            $departmentId = $this->resolveDepartment($data['department'] ?? null);
            $existing     = User::where('username', $data['username'])->first();

            $attributes = [
                'email'         => $data['email'] ?? ($existing->email ?? $data['username'] . '@company.local'),
                'full_name'     => $data['full_name'] ?? ucwords(str_replace(['.', '_'], ' ', $data['username'])),
                'department_id' => $departmentId ?? $existing?->department_id,
                'job_title'     => $data['job_title'] ?? $existing?->job_title,
                'profile_photo' => $data['photo'] ?? $existing?->profile_photo,
                'is_active'     => true,
            ];

            if ($existing) {
                // Never touch an existing user's password or roles (e.g. admin).
                $existing->update($attributes);
                return $existing->load('department');
            }

            // New employee discovered via directory — random password (Windows-auth only).
            $user = User::create($attributes + [
                'username' => $data['username'],
                'password' => Hash::make(bin2hex(random_bytes(16))),
            ]);
            $user->assignRole('employee');

            return $user->load('department');
        } catch (\Throwable $e) {
            Log::error('Directory upsert failed: ' . $e->getMessage(), ['username' => $data['username'] ?? null]);
            return null;
        }
    }

    private function resolveDepartment(?string $name): ?int
    {
        if (empty($name)) {
            return null;
        }

        $dept = Department::where('name', 'like', "%{$name}%")
            ->orWhere('name_ar', 'like', "%{$name}%")
            ->first();

        return $dept?->id ?? Department::create(['name' => $name, 'is_active' => true])->id;
    }
}
