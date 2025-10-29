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

class ArchiveExpiredBlockedAccounts implements ShouldQueue
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
        Log::info('Début de l\'archivage des comptes bloqués expirés');

        $now = now();

        // Trouver tous les comptes bloqués dont la date de fin de blocage est dépassée
        $expiredBlockedAccounts = Compte::where('statut', 'bloque')
            ->where('date_fin_blocage', '<=', $now)
            ->where('est_archive', false)
            ->get();

        $archivedCount = 0;

        foreach ($expiredBlockedAccounts as $compte) {
            DB::transaction(function () use ($compte, &$archivedCount) {
                // Archiver le compte
                $compte->update([
                    'est_archive' => true,
                    'date_archivage' => now(),
                    'statut' => 'ferme', // Fermer définitivement le compte
                ]);

                // Archiver toutes les transactions associées (soft delete)
                $compte->transactions()->delete();

                $archivedCount++;

                Log::info('Compte archivé', [
                    'compte_id' => $compte->id,
                    'numero_compte' => $compte->numero,
                    'client_id' => $compte->client_id,
                    'date_archivage' => now(),
                ]);
            });
        }

        Log::info('Archivage terminé', [
            'comptes_archives' => $archivedCount,
            'timestamp' => $now,
        ]);
    }
}
