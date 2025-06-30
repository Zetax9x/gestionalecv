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
        Schema::create('manutenzioni', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mezzo_id')->constrained('mezzi')->onDelete('cascade');
            $table->foreignId('user_id')->constrained("users")->onDelete('cascade'); // Chi ha registrato
            $table->date('data_manutenzione');
            $table->enum('tipo', [
                'tagliando',
                'riparazione',
                'revisione',
                'sostituzione_gomme',
                'controllo_freni',
                'cambio_olio',
                'filtri',
                'batteria',
                'climatizzatore',
                'sanitizzazione',
                'altro'
            ]);
            $table->text('descrizione');
            $table->decimal('costo', 8, 2)->nullable();
            $table->integer('km_effettuati');
            $table->string('officina')->nullable();
            $table->string('meccanico')->nullable();
            $table->string('numero_fattura')->nullable();
            $table->date('data_fattura')->nullable();
            $table->enum('stato', ['programmata', 'in_corso', 'completata', 'annullata'])->default('completata');
            $table->date('prossima_scadenza')->nullable(); // Per manutenzioni ricorrenti
            $table->integer('km_prossima_scadenza')->nullable();
            $table->json('ricambi_utilizzati')->nullable(); // Array con dettagli ricambi
            $table->text('note')->nullable();
            $table->string('allegato_fattura')->nullable(); // Path file
            $table->timestamps();
            
            // Indici
            $table->index(['mezzo_id', 'data_manutenzione']);
            $table->index('tipo');
            $table->index('stato');
            $table->index('prossima_scadenza');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('manutenzioni');
    }
};
