<?php

namespace App\Listeners;

use App\Events\ClientNotificationEvent;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendClientNotification implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(ClientNotificationEvent $event): void
    {
        // Log de l'opération
        Log::info('Envoi des notifications pour le nouveau compte', [
            'client_id' => $event->client->id,
            'compte_id' => $event->compte->id,
            'numero_compte' => $event->compte->numero,
        ]);

        // Envoyer l'email d'authentification
        $this->sendEmail($event);

        // Envoyer le SMS avec le code
        $this->sendSMS($event);
    }

    /**
     * Envoyer l'email d'authentification
     */
    private function sendEmail(ClientNotificationEvent $event): void
    {
        try {
            // Simulation d'envoi d'email (remplacer par vraie implémentation)
            Log::info('Email envoyé à ' . $event->client->email, [
                'sujet' => 'Création de votre compte bancaire',
                'contenu' => [
                    'nom' => $event->client->prenom . ' ' . $event->client->nom,
                    'numero_compte' => $event->compte->numero,
                    'mot_de_passe' => $event->password,
                    'code_verification' => $event->code,
                ]
            ]);

            // Ici vous pouvez utiliser Mail::to() pour envoyer un vrai email
            // Mail::to($event->client->email)->send(new WelcomeEmail($event));

        } catch (\Exception $e) {
            Log::error('Erreur lors de l\'envoi de l\'email', [
                'client_id' => $event->client->id,
                'email' => $event->client->email,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Envoyer le SMS avec le code
     */
    private function sendSMS(ClientNotificationEvent $event): void
    {
        try {
            // Simulation d'envoi de SMS (remplacer par vraie implémentation)
            Log::info('SMS envoyé à ' . $event->client->telephone, [
                'message' => 'Votre code de vérification est : ' . $event->code,
                'numero_compte' => $event->compte->numero,
            ]);

            // Ici vous pouvez utiliser un service SMS comme Twilio, Africa's Talking, etc.
            // $smsService->send($event->client->telephone, 'Votre code : ' . $event->code);

        } catch (\Exception $e) {
            Log::error('Erreur lors de l\'envoi du SMS', [
                'client_id' => $event->client->id,
                'telephone' => $event->client->telephone,
                'error' => $e->getMessage()
            ]);
        }
    }
}
