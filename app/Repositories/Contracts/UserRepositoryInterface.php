<?php

namespace App\Repositories\Contracts;

use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface UserRepositoryInterface
{
    public function findById(int $id): ?User;
    public function findByUsername(string $username): ?User;
    public function findByEmail(string $email): ?User;
    public function findByLdapGuid(string $guid): ?User;
    public function search(string $term, int $perPage = 15): LengthAwarePaginator;
    public function getAll(array $filters = [], int $perPage = 15): LengthAwarePaginator;
    public function getTopAppreciated(int $limit = 10, ?string $period = null): Collection;
    public function create(array $data): User;
    public function update(User $user, array $data): User;
    public function updateOrCreateFromLdap(array $ldapData): User;
    public function getLeaderboard(int $limit = 10): Collection;
}
