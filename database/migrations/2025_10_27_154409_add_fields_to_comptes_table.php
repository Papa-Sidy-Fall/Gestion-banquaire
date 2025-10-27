<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('comptes', function (Blueprint $table) {
            $table->string('devise', 10)->default('FCFA');
            $table->enum('statut', ['actif', 'bloque', 'ferme'])->default('actif');
            $table->text('motifBlocage')->nullable();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('comptes', function (Blueprint $table) {
            $table->dropSoftDeletes();
            $table->dropColumn(['devise', 'statut', 'motifBlocage']);
        });
    }
};
