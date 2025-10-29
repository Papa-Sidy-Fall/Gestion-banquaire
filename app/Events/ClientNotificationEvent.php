<?php

namespace App\Events;

use App\Models\Client;
use App\Models\Compte;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ClientNotificationEvent
{
    use Dispatchable, SerializesModels;

    public Client $client;
    public Compte $compte;
    public string $password;
    public string $code;

    /**
     * Create a new event instance.
     */
    public function __construct(Client $client, Compte $compte, string $password, string $code)
    {
        $this->client = $client;
        $this->compte = $compte;
        $this->password = $password;
        $this->code = $code;
    }
}
