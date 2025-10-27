<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Client extends Model
{
    use HasFactory;

    protected $table = 'clients';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'nom',
        'prenom',
        'email',
        'telephone',
        'adresse',
    ];

    protected static function boot()
    {
        parent::boot();

        // Générer un UUID automatiquement
        static::creating(function ($client) {
            if (empty($client->{$client->getKeyName()})) {
                $client->{$client->getKeyName()} = (string) Str::uuid();
            }
        });
    }

    // Relation avec les comptes
    public function comptes()
    {
        return $this->hasMany(Compte::class);
    }
}
