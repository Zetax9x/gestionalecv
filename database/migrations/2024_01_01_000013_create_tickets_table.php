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
        Schema::create('tickets', function (Blueprint $table) {
            $table->id();
            $table->string('numero_ticket')->unique(); // Generato automaticamente
            $table->foreignId('user_id')->constrained("users")->onDelete('cascade'); // Chi ha creato
            $table->foreignId('assegnato_a')->nullable()->constrained('users')->onDelete('set null');
            
            // Informazioni Base
            $table->string('titolo');
            $table->text('descrizione');
            $table->enum('categoria', [
                'mezzi',
                'dpi',
                'magazzino',
                'strutture',
                'informatica',
                'formazione',
                'amministrativo',
                'sicurezza',
                'altro'
            ]);
            $table->string('sottocategoria')->nullable();
            
            // Priorità e Urgenza
            $table->enum('priorita', ['bassa', 'media', 'alta', 'critica'])->default('media');
            $table->enum('urgenza', ['non_urgente', 'normale', 'urgente', 'critica'])->default('normale');
            $table->boolean('blocca_operativita')->default(false);
            
            // Stato e Workflow
            $table->enum('stato', [
                'aperto',
                'assegnato', 
                'in_corso',
                'in_attesa_parti',
                'in_attesa_approvazione',
                'risolto',
                'chiuso',
                'annullato'
            ])->default('aperto');
            
            // Date e Tempi
            $table->timestamp('data_apertura');
            $table->timestamp('data_assegnazione')->nullable();
            $table->timestamp('data_inizio_lavori')->nullable();
            $table->timestamp('data_risoluzione')->nullable();
            $table->timestamp('data_chiusura')->nullable();
            $table->integer('tempo_risoluzione_ore')->nullable(); // Calcolato automaticamente
            
            // Dettagli Tecnici
            $table->foreignId('mezzo_id')->nullable()->constrained("users")->onDelete('set null');
            $table->foreignId('dpi_id')->nullable()->constrained("users")->onDelete('set null');
            $table->foreignId('articolo_magazzino_id')->nullable()->constrained('magazzino')->onDelete('set null');
            $table->string('ubicazione_problema')->nullable();
            
            // Risoluzione
            $table->text('soluzione_adottata')->nullable();
            $table->text('note_tecniche')->nullable();
            $table->decimal('costo_riparazione', 8, 2)->nullable();
            $table->string('fornitore_servizio')->nullable();
            $table->boolean('richiede_follow_up')->default(false);
            $table->date('data_follow_up')->nullable();
            
            // Valutazione
            $table->integer('valutazione_richiedente')->nullable(); // 1-5
            $table->text('feedback_richiedente')->nullable();
            $table->timestamp('data_feedback')->nullable();
            
            // Workflow e Approvazioni
            $table->boolean('richiede_approvazione')->default(false);
            $table->foreignId('approvato_da')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('data_approvazione')->nullable();
            $table->text('note_approvazione')->nullable();
            
            // Notifiche
            $table->json('notificati')->nullable(); // Array user_id notificati
            $table->timestamp('ultima_notifica')->nullable();
            
            $table->text('note')->nullable();
            $table->timestamps();
            
            // Indici
            $table->index(['categoria', 'stato']);
            $table->index(['priorita', 'stato']);
            $table->index(['assegnato_a', 'stato']);
            $table->index('data_apertura');
            $table->index('numero_ticket');
            $table->index(['mezzo_id', 'stato']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tickets');
    }
};
