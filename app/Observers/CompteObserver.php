<?php

namespace App\Observers;

use App\Models\Compte;

class CompteObserver
{
    /**
     * Handle the Compte "created" event.
     */
    public function created(Compte $compte): void
    {
        // Générer un mot de passe temporaire
        $password = \Illuminate\Support\Str::random(8);

        // Générer un code de vérification
        $code = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);

        // Sauvegarder le mot de passe hashé pour le client
        if ($compte->client) {
            $compte->client->update([
                'password' => bcrypt($password),
                'code_verification' => $code,
            ]);

            // Déclencher l'événement de notification
            \App\Events\ClientNotificationEvent::dispatch(
                $compte->client,
                $compte,
                $password,
                $code
            );
        }
    }

    /**
     * Handle the Compte "updated" event.
     */
    public function updated(Compte $compte): void
    {
        //
    }

    /**
     * Handle the Compte "deleted" event.
     */
    public function deleted(Compte $compte): void
    {
        //
    }

    /**
     * Handle the Compte "restored" event.
     */
    public function restored(Compte $compte): void
    {
        //
    }

    /**
     * Handle the Compte "force deleted" event.
     */
    public function forceDeleted(Compte $compte): void
    {
        //
    }
}
