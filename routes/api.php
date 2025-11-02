<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\ClientController;
use App\Http\Controllers\CompteController;

// Test minimal pour diagnostiquer l'erreur  500
Route::get('/test-minimal', function () {
    return response()->json(['test' => 'ok', 'timestamp' => now()]);
});

// Test super simple
Route::get('/super-simple', function () {
    return 'OK';
});

// Routes de base sans authentification temporairement
Route::apiResource('clients', ClientController::class);

// Groupement par version v1 - SANS authentification pour le moment
Route::prefix('v1')->group(function () {
    // Lister tous les comptes
    Route::get('comptes', [CompteController::class, 'index'])->name('comptes.index');

    // Créer un compte
    Route::post('comptes', [CompteController::class, 'store'])->name('comptes.store');

    // Afficher un compte spécifique
    Route::get('comptes/{compte}', [CompteController::class, 'show'])->name('comptes.show');

    // Modifier un compte
    Route::patch('comptes/{compte}', [CompteController::class, 'update'])->name('comptes.update');

    // Supprimer un compte
    Route::delete('comptes/{compte}', [CompteController::class, 'destroy'])->name('comptes.destroy');

    // Bloquer un compte
    Route::post('comptes/{compte}/bloquer', [CompteController::class, 'bloquer'])->name('comptes.bloquer');
});
