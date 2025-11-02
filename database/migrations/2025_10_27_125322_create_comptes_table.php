<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('comptes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('numero')->unique();
            $table->uuid('client_id');
            $table->decimal('solde', 12, 2)->default(0);
            $table->string('type_compte', 50);
            $table->timestamps();

            // Index
            $table->index('client_id');
            $table->index('type_compte');

            // Clé étrangère
            $table->foreign('client_id')->references('id')->on('clients')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('comptes');
    }
};
