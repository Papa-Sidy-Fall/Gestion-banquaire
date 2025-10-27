<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Compte extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'comptes';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'numero',
        'client_id',
        'solde',
        'type_compte',
        'devise',
        'statut',
        'motifBlocage',
    ];

    protected static function boot()
    {
        parent::boot();

        // UUID automatique
        static::creating(function ($model) {
            if (empty($model->{$model->getKeyName()})) {
                $model->{$model->getKeyName()} = (string) Str::uuid();
            }

            // Génération automatique du numéro
            if (empty($model->numero)) {
                $model->numero = 'CPT-' . strtoupper(Str::random(8));
            }
        });
    }

    // Relation avec client
    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    // Scope pour les comptes non supprimés (soft delete)
    public function scopeActive($query)
    {
        return $query->where('statut', '!=', 'ferme');
    }

    // Scope pour filtrer par type
    public function scopeByType($query, $type)
    {
        return $query->where('type_compte', $type);
    }

    // Scope pour filtrer par statut
    public function scopeByStatut($query, $statut)
    {
        return $query->where('statut', $statut);
    }

    // Scope pour rechercher par titulaire ou numéro
    public function scopeSearch($query, $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('numero', 'like', '%' . $search . '%')
              ->orWhereHas('client', function ($clientQuery) use ($search) {
                  $clientQuery->where('nom', 'like', '%' . $search . '%')
                              ->orWhere('prenom', 'like', '%' . $search . '%')
                              ->orWhere('telephone', 'like', '%' . $search . '%');
              });
        });
    }

    // Scope pour récupérer un compte par son numéro
    public function scopeNumero($query, $numero)
    {
        return $query->where('numero', $numero);
    }

    // Scope pour récupérer les comptes d'un client par téléphone
    public function scopeClient($query, $telephone)
    {
        return $query->whereHas('client', function ($clientQuery) use ($telephone) {
            $clientQuery->where('telephone', $telephone);
        });
    }

}
