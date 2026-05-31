<?php

namespace App\Providers;

use App\Repositories\Contracts\AppreciationRepositoryInterface;
use App\Repositories\Contracts\UserRepositoryInterface;
use App\Repositories\Eloquent\AppreciationRepository;
use App\Repositories\Eloquent\UserRepository;
use Illuminate\Support\ServiceProvider;

class RepositoryServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(UserRepositoryInterface::class, UserRepository::class);
        $this->app->bind(AppreciationRepositoryInterface::class, AppreciationRepository::class);
    }

    public function boot(): void {}
}
