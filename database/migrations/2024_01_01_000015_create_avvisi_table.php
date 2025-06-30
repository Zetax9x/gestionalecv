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
        Schema::create('avvisi', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained("users")->onDelete('cascade'); // Autore
            
            // Contenuto
            $table->string('titolo');
            $table->text('contenuto');
            $table->text('riassunto')->nullable(); // Breve descrizione
            
            // Tipologia
            $table->enum('tipo', [
                'generale',
                'urgente',
                'formazione',
                'servizi',
                'manutenzioni',
                'eventi',
                'normative',
                'covid',
                'altro'
            ])->default('generale');
            
            // Visibilità
            $table->boolean('pubblico')->default(true);
            $table->json('destinatari_ruoli')->nullable(); // Array ruoli se non pubblico
            $table->json('destinatari_specifici')->nullable(); // Array user_id specifici
            
            // Priorità e Stile
            $table->enum('priorita', ['bassa', 'normale', 'alta', 'critica'])->default('normale');
            $table->string('colore_badge')->nullable(); // Per UI
            $table->string('icona')->nullable(); // FontAwesome icon
            
            // Date e Pubblicazione
            $table->datetime('data_pubblicazione');
            $table->datetime('data_scadenza')->nullable();
            $table->boolean('sempre_visibile')->default(false); // Pin in cima
            $table->boolean('notifica_push')->default(false);
            
            // Stato
            $table->enum('stato', [
                'bozza',
                'programmato',
                'pubblicato',
                'scaduto',
                'archiviato'
            ])->default('bozza');
            
            // Approvazione (per alcuni ruoli)
            $table->boolean('richiede_approvazione')->default(false);
            $table->foreignId('approvato_da')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('data_approvazione')->nullable();
            
            // Tracking
            $table->integer('visualizzazioni')->default(0);
            $table->json('letto_da')->nullable(); // Array user_id che hanno letto
            $table->timestamp('ultima_modifica')->nullable();
            
            // Allegati
            $table->json('allegati')->nullable(); // Array path files
            $table->string('link_esterno')->nullable();
            
            $table->text('note')->nullable();
            $table->timestamps();
            
            // Indici
            $table->index(['tipo', 'stato']);
            $table->index(['data_pubblicazione', 'stato']);
            $table->index('priorita');
            $table->index('sempre_visibile');
            $table->index('data_scadenza');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('avvisi');
    }
};
