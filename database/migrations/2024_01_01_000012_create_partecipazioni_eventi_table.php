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
        Schema::create('partecipazioni_eventi', function (Blueprint $table) {
            $table->id();
            $table->foreignId('evento_id')->constrained("users")->onDelete('cascade');
            $table->foreignId('user_id')->constrained("users")->onDelete('cascade');
            
            // Stato Partecipazione
            $table->enum('stato', [
                'in_attesa',
                'confermato',
                'lista_attesa',
                'rifiutato',
                'annullato',
                'presente',
                'assente',
                'completato'
            ])->default('in_attesa');
            
            // Date
            $table->timestamp('data_iscrizione');
            $table->timestamp('data_conferma')->nullable();
            $table->timestamp('data_annullamento')->nullable();
            
            // Presenza e Valutazione
            $table->boolean('presente')->nullable();
            $table->time('ora_arrivo')->nullable();
            $table->time('ora_uscita')->nullable();
            $table->text('note_presenza')->nullable();
            
            // Certificazione
            $table->boolean('superato')->nullable();
            $table->decimal('voto', 5, 2)->nullable();
            $table->string('numero_attestato')->nullable();
            $table->date('data_rilascio_attestato')->nullable();
            $table->string('file_attestato')->nullable(); // Path file PDF
            
            // Feedback e Valutazioni
            $table->integer('valutazione_evento')->nullable(); // 1-5 stelle
            $table->text('feedback_evento')->nullable();
            $table->integer('valutazione_docenti')->nullable();
            $table->text('feedback_docenti')->nullable();
            $table->text('suggerimenti')->nullable();
            $table->boolean('consiglia_evento')->nullable();
            
            // Motivi
            $table->text('motivo_rifiuto')->nullable();
            $table->text('motivo_annullamento')->nullable();
            
            $table->text('note')->nullable();
            $table->timestamps();
            
            // Vincolo unico
            $table->unique(['evento_id', 'user_id']);
            
            // Indici
            $table->index(['user_id', 'stato']);
            $table->index(['evento_id', 'stato']);
            $table->index('data_iscrizione');
            $table->index('presente');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('partecipazioni_eventi');
    }
};