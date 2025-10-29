<?php

namespace App\Jobs;

use App\Models\Compte;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class UnblockExpiredAccounts implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info('Début du déblocage des comptes dont le blocage est expiré');

        $now = now();

        // Trouver tous les comptes bloqués dont la date de fin de blocage est dépassée
        // mais qui ne sont pas encore archivés
        $expiredBlockedAccounts = Compte::where('statut', 'bloque')
            ->where('date_fin_blocage', '<=', $now)
            ->where('est_archive', false)
            ->get();

        $unblockedCount = 0;

        foreach ($expiredBlockedAccounts as $compte) {
            DB::transaction(function () use ($compte, &$unblockedCount) {
                // Débloquer le compte
                $compte->update([
                    'statut' => 'actif',
                    'motifBlocage' => null,
                    'date_debut_blocage' => null,
                    'date_fin_blocage' => null,
                ]);

                $unblockedCount++;

                Log::info('Compte débloqué automatiquement', [
                    'compte_id' => $compte->id,
                    'numero_compte' => $compte->numero,
                    'client_id' => $compte->client_id,
                    'date_deblocage' => now(),
                ]);
            });
        }

        Log::info('Déblocage terminé', [
            'comptes_debloques' => $unblockedCount,
            'timestamp' => $now,
        ]);
    }
}
