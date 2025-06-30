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
        Schema::create('permissions', function (Blueprint $table) {
            $table->id();
            $table->string('modulo'); // volontari, mezzi, magazzino, dpi, documenti, eventi, avvisi, tickets, archivio, logs
            $table->string('ruolo'); // admin, direttivo, segreteria, mezzi, dipendente, volontario
            $table->boolean('visualizza')->default(false);
            $table->boolean('crea')->default(false);
            $table->boolean('modifica')->default(false);
            $table->boolean('elimina')->default(false);
            $table->boolean('configura')->default(false); // Solo per admin
            $table->text('note')->nullable();
            $table->timestamps();
            
            // Chiave unica per modulo-ruolo
            $table->unique(['modulo', 'ruolo'], 'unique_modulo_ruolo');
            
            // Indici
            $table->index('modulo');
            $table->index('ruolo');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('permissions');
    }
};
