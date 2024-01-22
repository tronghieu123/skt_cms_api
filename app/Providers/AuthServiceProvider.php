<?php

namespace App\Providers;
use App\Models\Sanctum\PersonalAccessToken;
use Laravel\Sanctum\Sanctum;
use Modules\Passport\Models\Client;
// use Illuminate\Support\Facades\Gate;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Auth;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        //
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function register(): void
    {

    }

    public function boot(): void
    {
        class_alias(PersonalAccessToken::class, \Laravel\Sanctum\PersonalAccessToken::class);
    }
}
