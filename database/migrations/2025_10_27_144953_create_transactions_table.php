<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('compte_id');
            $table->string('type', 20); // dépôt, retrait, transfert
            $table->decimal('montant', 12, 2);
            $table->string('reference')->unique();
            $table->timestamps();

            // Index
            $table->index('compte_id');
            $table->index('type');

            // Clé étrangère
            $table->foreign('compte_id')->references('id')->on('comptes')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
