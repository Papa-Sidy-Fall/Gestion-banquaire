<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;


Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});

use App\Http\Controllers\ClientController;
use App\Http\Controllers\CompteController;
use App\Http\Middleware\RateLimitMiddleware;

Route::apiResource('clients', ClientController::class);

// Route de debug temporaire
Route::get('/debug-env', function () {
    return response()->json([
        'env_variables' => [
            'DB_CONNECTION' => env('DB_CONNECTION'),
            'DB_HOST' => env('DB_HOST'),
            'DB_PORT' => env('DB_PORT'),
            'DB_DATABASE' => env('DB_DATABASE'),
            'DB_USERNAME' => env('DB_USERNAME'),
            'DB_PASSWORD' => env('DB_PASSWORD') ? '***SET***' : 'NOT_SET',
        ],
        'config_database' => config('database.connections.pgsql'),
        'timestamp' => now()
    ]);
});

// Groupement par version v1
Route::prefix('v1')->group(function () {
    Route::middleware([RateLimitMiddleware::class])->group(function () {
        // Lister tous les comptes (Admin: tous, Client: ses comptes)
        // GET /api/v1/comptes?page=1&limit=10&type=epargne&statut=actif&search=Amadou&sort=created_at&order=desc
        Route::get('comptes', [CompteController::class, 'index'])->name('comptes.index');

        // Créer un compte
        Route::post('comptes', [CompteController::class, 'store'])
            ->middleware('logging:CREATION_COMPTE')
            ->name('comptes.store');

        // Afficher un compte spécifique
        Route::get('comptes/{compte}', [CompteController::class, 'show'])->name('comptes.show');

        // Modifier un compte
        Route::patch('comptes/{compte}', [CompteController::class, 'update'])
            ->middleware('logging:MODIFICATION_COMPTE')
            ->name('comptes.update');

        // Supprimer un compte (soft delete)
        Route::delete('comptes/{compte}', [CompteController::class, 'destroy'])->name('comptes.destroy');

        // Bloquer un compte
        Route::post('comptes/{compte}/bloquer', [CompteController::class, 'bloquer'])
            ->middleware('logging:BLOCAGE_COMPTE')
            ->name('comptes.bloquer');
    });
});
