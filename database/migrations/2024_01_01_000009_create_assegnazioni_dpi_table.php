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
        Schema::create('assegnazioni_dpi', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dpi_id')->constrained('dpi')->onDelete('cascade');
            $table->foreignId('volontario_id')->constrained('volontari')->onDelete('cascade');
            $table->foreignId('assegnato_da')->constrained('users')->onDelete('cascade');
            
            // Date assegnazione/restituzione
            $table->date('data_assegnazione');
            $table->date('data_restituzione')->nullable();
            $table->boolean('restituito')->default(false);
            
            // Stato al momento dell'assegnazione/restituzione
            $table->enum('stato_consegna', [
                'nuovo',
                'buono',
                'usato',
                'da_controllare'
            ])->default('buono');
            $table->enum('stato_restituzione', [
                'buono',
                'usato',
                'danneggiato',
                'perso',
                'non_restituito'
            ])->nullable();
            
            // Motivazioni
            $table->text('motivo_assegnazione')->nullable();
            $table->text('motivo_restituzione')->nullable();
            
            // Responsabilità e Documenti
            $table->boolean('ricevuta_firmata')->default(false);
            $table->string('documento_ricevuta')->nullable(); // Path file ricevuta
            $table->boolean('formazione_effettuata')->default(false);
            $table->date('data_formazione')->nullable();
            $table->foreignId('formatore_id')->nullable()->constrained('users')->onDelete('set null');
            
            // Controlli e Verifiche
            $table->date('ultima_verifica')->nullable();
            $table->date('prossima_verifica')->nullable();
            $table->text('note_verifica')->nullable();
            
            // Utilizzo
            $table->integer('ore_utilizzo')->default(0);
            $table->integer('giorni_utilizzo')->default(0);
            
            $table->text('note')->nullable();
            $table->timestamps();
            
            // Indici
            $table->index(['volontario_id', 'restituito']);
            $table->index(['dpi_id', 'data_assegnazione']);
            $table->index('data_restituzione');
            $table->index('prossima_verifica');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('assegnazioni_dpi');
    }
};