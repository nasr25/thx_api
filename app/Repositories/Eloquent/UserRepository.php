<?php

namespace App\Repositories\Eloquent;

use App\Models\Appreciation;
use App\Models\User;
use App\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Hash;

class UserRepository implements UserRepositoryInterface
{
    public function findById(int $id): ?User
    {
        return User::with('department')->find($id);
    }

    public function findByUsername(string $username): ?User
    {
        return User::where('username', $username)->first();
    }

    public function findByEmail(string $email): ?User
    {
        return User::where('email', $email)->first();
    }

    public function findByLdapGuid(string $guid): ?User
    {
        return User::where('ldap_guid', $guid)->first();
    }

    public function search(string $term, int $perPage = 15): LengthAwarePaginator
    {
        return User::with('department')
            ->active()
            ->search($term)
            ->withCount('receivedAppreciations')
            ->paginate($perPage);
    }

    public function getAll(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = User::with('department')->withCount('receivedAppreciations');

        if (!empty($filters['search'])) {
            $query->search($filters['search']);
        }

        if (!empty($filters['department_id'])) {
            $query->where('department_id', $filters['department_id']);
        }

        if (isset($filters['is_active'])) {
            $query->where('is_active', $filters['is_active']);
        }

        if (!empty($filters['role'])) {
            $query->role($filters['role']);
        }

        return $query->orderBy('full_name')->paginate($perPage);
    }

    public function getTopAppreciated(int $limit = 10, ?string $period = null): Collection
    {
        $query = User::withCount(['receivedAppreciations as appreciation_count' => function ($q) use ($period) {
            if ($period === 'month') {
                $q->whereMonth('appreciations.created_at', now()->month)
                  ->whereYear('appreciations.created_at', now()->year);
            } elseif ($period === 'week') {
                $q->where('appreciations.created_at', '>=', now()->startOfWeek());
            }
        }])
        ->active()
        ->orderByDesc('appreciation_count')
        ->limit($limit);

        return $query->get();
    }

    public function getLeaderboard(int $limit = 10): Collection
    {
        return $this->getTopAppreciated($limit, 'month');
    }

    public function create(array $data): User
    {
        if (isset($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        }
        return User::create($data);
    }

    public function update(User $user, array $data): User
    {
        if (isset($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        }
        $user->update($data);
        return $user->fresh('department');
    }

    public function updateOrCreateFromLdap(array $ldapData): User
    {
        $user = User::updateOrCreate(
            ['ldap_guid' => $ldapData['ldap_guid']],
            array_merge($ldapData, ['is_active' => true])
        );

        if (!$user->hasAnyRole(['admin', 'super-admin', 'employee'])) {
            $user->assignRole('employee');
        }

        return $user;
    }
}
