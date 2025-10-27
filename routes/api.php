<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});

use App\Http\Controllers\ClientController;
use App\Http\Controllers\CompteController;
use App\Http\Middleware\RateLimitMiddleware;

Route::apiResource('clients', ClientController::class);

// Groupement par version v1
Route::prefix('v1')->group(function () {
    Route::middleware([RateLimitMiddleware::class])->group(function () {
        // Lister tous les comptes (Admin: tous, Client: ses comptes)
        // GET /api/v1/comptes?page=1&limit=10&type=epargne&statut=actif&search=Amadou&sort=created_at&order=desc
        Route::get('comptes', [CompteController::class, 'index'])->name('comptes.index');

        // Créer un compte
        Route::post('comptes', [CompteController::class, 'store'])->name('comptes.store');

        // Afficher un compte spécifique
        Route::get('comptes/{compte}', [CompteController::class, 'show'])->name('comptes.show');

        // Supprimer un compte (soft delete)
        Route::delete('comptes/{compte}', [CompteController::class, 'destroy'])->name('comptes.destroy');
    });
});
