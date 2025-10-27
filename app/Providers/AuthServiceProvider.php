<?php

namespace App\Providers;

// use Illuminate\Support\Facades\Gate;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
// use Laravel\Passport\Passport;


use Laravel\Passport\Http\Controllers\AccessTokenController;
use Laravel\Passport\Http\Controllers\AuthorizationController;
use Laravel\Passport\Http\Controllers\TransientTokenController;
use Illuminate\Support\Facades\Route;


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
    public function boot(): void
    {
        
          $this->registerPolicies();

    // Définir manuellement les routes de Passport
        Route::prefix('oauth')->group(function () {
        Route::post('/token', [AccessTokenController::class, 'issueToken'])->name('passport.token');
        Route::get('/authorize', [AuthorizationController::class, 'authorize'])->name('passport.authorizations.authorize');
        Route::post('/token/refresh', [TransientTokenController::class, 'refresh'])->name('passport.token.refresh');
    });
        
    }
}
