<?php

declare(strict_types=1);

namespace AndyDefer\LaravelVote;

use AndyDefer\LaravelVote\Repositories\VoteRepository;
use AndyDefer\LaravelVote\Services\VoteService;
use Illuminate\Support\ServiceProvider;

final class VoteServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(VoteRepository::class);
        $this->app->singleton(VoteService::class);
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->loadMigrationsFrom(__DIR__.'/migrations');
        }

        $this->publishes([
            __DIR__.'/migrations' => database_path('migrations'),
        ], 'vote-migrations');
    }
}
