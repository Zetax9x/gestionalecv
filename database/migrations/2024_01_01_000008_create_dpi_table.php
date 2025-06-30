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
        Schema::create('dpi', function (Blueprint $table) {
            $table->id();
            $table->string('nome');
            $table->text('descrizione')->nullable();
            $table->string('codice_dpi')->unique()->nullable();
            
            // Caratteristiche
            $table->enum('categoria', [
                'protezione_testa',
                'protezione_occhi',
                'protezione_respiratoria',
                'protezione_mani',
                'protezione_piedi',
                'protezione_corpo',
                'protezione_cadute',
                'divise',
                'altro'
            ]);
            $table->string('taglia')->nullable();
            $table->string('colore')->nullable();
            $table->string('materiale')->nullable();
            $table->string('marca')->nullable();
            $table->string('modello')->nullable();
            
            // Certificazioni e Normative
            $table->string('certificazione_ce')->nullable();
            $table->json('normative_riferimento')->nullable(); // Array normative
            $table->string('classe_protezione')->nullable();
            $table->date('data_certificazione')->nullable();
            $table->date('scadenza_certificazione')->nullable();
            
            // Scadenze e Manutenzione
            $table->date('data_acquisto')->nullable();
            $table->date('scadenza')->nullable();
            $table->integer('durata_mesi')->nullable(); // Durata teorica in mesi
            $table->integer('max_utilizzi')->nullable(); // Per DPI monouso
            $table->integer('utilizzi_effettuati')->default(0);
            
            // Stato e Disponibilità
            $table->enum('stato', [
                'nuovo',
                'buono',
                'usato',
                'da_controllare',
                'da_sostituire',
                'dismesso'
            ])->default('nuovo');
            $table->boolean('disponibile')->default(true);
            $table->boolean('in_manutenzione')->default(false);
            $table->date('data_ultima_verifica')->nullable();
            $table->date('prossima_verifica')->nullable();
            
            // Costi e Fornitori
            $table->decimal('costo_acquisto', 8, 2)->nullable();
            $table->string('fornitore')->nullable();
            $table->string('numero_fattura')->nullable();
            
            // Posizione
            $table->string('ubicazione')->nullable(); // Dove è riposto
            $table->string('armadio_scaffale')->nullable();
            
            // Istruzioni
            $table->text('istruzioni_uso')->nullable();
            $table->text('istruzioni_manutenzione')->nullable();
            $table->text('istruzioni_pulizia')->nullable();
            
            $table->text('note')->nullable();
            $table->string('foto')->nullable();
            $table->timestamps();
            
            // Indici
            $table->index(['categoria', 'disponibile']);
            $table->index('stato');
            $table->index('scadenza');
            $table->index('prossima_verifica');
            $table->index('taglia');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dpi');
    }
};