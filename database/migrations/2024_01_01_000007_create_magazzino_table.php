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
        Schema::create('magazzino', function (Blueprint $table) {
            $table->id();
            $table->string('nome_articolo');
            $table->text('descrizione')->nullable();
            $table->string('codice_articolo')->unique()->nullable();
            $table->string('codice_interno')->unique()->nullable(); // Codice personalizzato CroceVerde
            $table->string('codice_fornitore')->nullable();
            
            // Quantità e Gestione
            $table->integer('quantita_attuale')->default(0);
            $table->integer('quantita_minima')->default(0);
            $table->integer('quantita_massima')->nullable();
            $table->integer('punto_riordino')->nullable();
            $table->string('unita_misura')->default('pezzi');
            
            // Categorizzazione
            $table->string('categoria'); // farmaci, dispositivi_medici, consumabili, dpi, altro
            $table->string('sottocategoria')->nullable();
            $table->json('tags')->nullable(); // Per ricerca avanzata
            
            // Scadenze e Lotti
            $table->date('scadenza')->nullable();
            $table->string('lotto')->nullable();
            $table->boolean('gestione_lotti')->default(false);
            $table->boolean('gestione_scadenze')->default(false);
            
            // Costi e Fornitori
            $table->decimal('prezzo_unitario', 8, 2)->nullable();
            $table->decimal('costo_ultimo_acquisto', 8, 2)->nullable();
            $table->string('fornitore_principale')->nullable();
            $table->json('fornitori_alternativi')->nullable();
            
            // Posizione e Storage
            $table->string('ubicazione')->nullable(); // Scaffale, armadio, etc.
            $table->string('zona_magazzino')->default('principale');
            $table->decimal('temperatura_conservazione_min', 5, 2)->nullable();
            $table->decimal('temperatura_conservazione_max', 5, 2)->nullable();
            $table->text('condizioni_conservazione')->nullable();
            
            // Classificazioni Speciali
            $table->boolean('farmaco')->default(false);
            $table->boolean('stupefacente')->default(false);
            $table->boolean('dispositivo_medico')->default(false);
            $table->string('classe_dispositivo')->nullable(); // I, IIa, IIb, III
            $table->boolean('monouso')->default(false);
            
            // Gestione
            $table->boolean('attivo')->default(true);
            $table->text('note')->nullable();
            $table->string('foto')->nullable(); // Path immagine articolo
            $table->foreignId('responsabile_id')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            
            // Indici
            $table->index(['categoria', 'attivo']);
            $table->index('quantita_attuale');
            $table->index('scadenza');
            $table->index('punto_riordino');
            $table->index(['farmaco', 'stupefacente']);
            $table->fullText(['nome_articolo', 'descrizione']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('magazzino');
    }
};