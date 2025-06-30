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
        Schema::create('mezzi', function (Blueprint $table) {
            $table->id();
            $table->string('targa', 10)->unique();
            $table->enum('tipo', [
                'ambulanza_a', 
                'ambulanza_b', 
                'auto_medica', 
                'auto_servizio', 
                'furgone', 
                'altro'
            ]);
            $table->string('marca');
            $table->string('modello');
            $table->integer('anno');
            $table->string('numero_telaio')->nullable();
            $table->string('colore')->default('bianco');
            $table->enum('alimentazione', ['benzina', 'diesel', 'gpl', 'metano', 'elettrico', 'ibrido']);
            
            // Documenti e Scadenze
            $table->date('scadenza_revisione');
            $table->date('scadenza_assicurazione');
            $table->string('compagnia_assicurazione')->nullable();
            $table->string('numero_polizza')->nullable();
            $table->date('scadenza_bollo')->nullable();
            $table->date('scadenza_collaudo')->nullable();
            
            // Chilometraggio e Manutenzione
            $table->integer('km_attuali')->default(0);
            $table->integer('km_ultimo_tagliando')->default(0);
            $table->integer('km_prossimo_tagliando')->nullable();
            $table->integer('intervallo_tagliando')->default(15000); // km
            $table->date('data_ultimo_tagliando')->nullable();
            
            // Dotazioni e Caratteristiche
            $table->json('dotazioni_sanitarie')->nullable(); // Array dotazioni
            $table->json('dotazioni_tecniche')->nullable();
            $table->boolean('aria_condizionata')->default(false);
            $table->boolean('gps')->default(false);
            $table->boolean('radio_ponte')->default(false);
            $table->string('frequenza_radio')->nullable();
            
            // Gestione
            $table->text('note')->nullable();
            $table->boolean('attivo')->default(true);
            $table->boolean('in_servizio')->default(true);
            $table->timestamp('data_dismissione')->nullable();
            $table->text('motivo_dismissione')->nullable();
            $table->decimal('costo_acquisto', 10, 2)->nullable();
            $table->date('data_acquisto')->nullable();
            $table->string('fornitore')->nullable();
            
            // Posizione e Stato
            $table->string('posizione_attuale')->default('sede');
            $table->foreignId('ultimo_user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('ultimo_utilizzo')->nullable();
            
            $table->timestamps();
            
            // Indici
            $table->index(['tipo', 'attivo']);
            $table->index('scadenza_revisione');
            $table->index('scadenza_assicurazione');
            $table->index('km_attuali');
            $table->index('in_servizio');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mezzi');
    }
};