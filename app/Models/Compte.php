<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Compte extends Model
{
    use HasFactory;

    protected $table = 'comptes';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'numero',
        'client_id',
        'solde',
        'type_compte',
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


}
