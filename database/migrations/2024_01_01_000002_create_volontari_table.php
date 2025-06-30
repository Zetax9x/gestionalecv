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
        Schema::create('volontari', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('tessera_numero')->unique()->nullable();
            $table->date('data_iscrizione');
            $table->date('data_visita_medica')->nullable();
            $table->date('scadenza_visita_medica')->nullable();
            $table->string('medico_competente')->nullable();
            $table->enum('stato_formazione', [
                'base', 
                'avanzato', 
                'istruttore', 
                'in_corso'
            ])->default('base');
            $table->date('ultimo_corso')->nullable();
            $table->json('corsi_completati')->nullable(); // Array di corsi
            $table->json('competenze')->nullable(); // Array di competenze speciali
            $table->enum('disponibilita', [
                'sempre', 
                'weekdays', 
                'weekend', 
                'sera', 
                'limitata'
            ])->default('sempre');
            $table->text('note_disponibilita')->nullable();
            $table->text('allergie_patologie')->nullable();
            $table->string('contatto_emergenza_nome')->nullable();
            $table->string('contatto_emergenza_telefono')->nullable();
            $table->string('gruppo_sanguigno', 3)->nullable();
            $table->decimal('ore_servizio_anno', 8, 2)->default(0);
            $table->text('note')->nullable();
            $table->boolean('attivo')->default(true);
            $table->timestamp('data_sospensione')->nullable();
            $table->text('motivo_sospensione')->nullable();
            $table->timestamps();
            
            // Indici
            $table->index(['attivo', 'data_iscrizione']);
            $table->index('tessera_numero');
            $table->index('scadenza_visita_medica');
            $table->index('stato_formazione');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('volontari');
    }
};