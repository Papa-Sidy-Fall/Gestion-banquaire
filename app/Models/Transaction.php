<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Transaction extends Model
{
    use HasFactory;

    protected $table = 'transactions';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'compte_id',
        'type',
        'montant',
        'reference',
    ];

    protected static function boot()
    {
        parent::boot();

        // UUID automatique
        static::creating(function ($model) {
            if (empty($model->{$model->getKeyName()})) {
                $model->{$model->getKeyName()} = (string) Str::uuid();
            }

            // Génération automatique d’une référence
            if (empty($model->reference)) {
                $model->reference = 'TX-' . strtoupper(Str::random(10));
            }
        });
    }

    // Relation avec le compte
    public function compte()
    {
        return $this->belongsTo(Compte::class);
    }
}
