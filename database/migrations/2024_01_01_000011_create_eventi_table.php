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
        Schema::create('eventi', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organizzatore_id')->constrained('users')->onDelete('cascade');
            
            // Informazioni Base
            $table->string('titolo');
            $table->text('descrizione');
            $table->enum('tipo', [
                'corso_formazione',
                'aggiornamento',
                'esercitazione',
                'riunione',
                'assemblea',
                'evento_pubblico',
                'gara',
                'manifestazione',
                'altro'
            ]);
            $table->string('categoria')->nullable(); // BLSD, PBLSD, Trauma, ecc.
            
            // Date e Orari
            $table->datetime('data_inizio');
            $table->datetime('data_fine');
            $table->boolean('evento_multiplo')->default(false); // Corso su più giorni
            $table->json('date_aggiuntive')->nullable(); // Array date per corsi multipli
            $table->integer('durata_ore')->nullable();
            
            // Luogo
            $table->string('luogo')->nullable();
            $table->text('indirizzo_completo')->nullable();
            $table->string('aula_sala')->nullable();
            $table->decimal('latitudine', 10, 8)->nullable();
            $table->decimal('longitudine', 11, 8)->nullable();
            
            // Partecipazione
            $table->integer('max_partecipanti')->nullable();
            $table->integer('min_partecipanti')->default(1);
            $table->boolean('richiede_conferma')->default(true);
            $table->boolean('lista_attesa')->default(true);
            $table->datetime('scadenza_iscrizioni')->nullable();
            
            // Costi e Certificazioni
            $table->decimal('costo_partecipazione', 8, 2)->nullable();
            $table->boolean('rilascia_attestato')->default(false);
            $table->string('tipo_attestato')->nullable();
            $table->integer('crediti_ecm')->nullable();
            $table->string('provider_ecm')->nullable();
            
            // Docenti e Staff
            $table->json('docenti')->nullable(); // Array con ID docenti
            $table->json('staff')->nullable(); // Array con ID staff
            $table->text('materiali_necessari')->nullable();
            $table->text('prerequisiti')->nullable();
            
            // Gestione
            $table->enum('stato', [
                'programmato',
                'aperto',
                'chiuso',
                'in_corso',
                'completato',
                'annullato',
                'rinviato'
            ])->default('programmato');
            $table->text('motivo_annullamento')->nullable();
            $table->datetime('data_annullamento')->nullable();
            
            // Notifiche e Promemoria
            $table->boolean('invia_promemoria')->default(true);
            $table->json('giorni_promemoria')->nullable(); // [7, 1] = 7 giorni prima e 1 giorno prima
            $table->timestamp('ultimo_promemoria')->nullable();
            
            // Valutazioni
            $table->boolean('abilita_feedback')->default(true);
            $table->decimal('valutazione_media', 3, 2)->nullable();
            $table->integer('numero_valutazioni')->default(0);
            
            $table->text('note')->nullable();
            $table->string('locandina')->nullable(); // Path immagine
            $table->json('allegati')->nullable(); // Array path files
            $table->timestamps();
            
            // Indici
            $table->index(['tipo', 'stato']);
            $table->index('data_inizio');
            $table->index(['data_inizio', 'data_fine']);
            $table->index('scadenza_iscrizioni');
            $table->index('organizzatore_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('eventi');
    }
};
