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
        Schema::create('checklist_compilate', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mezzo_id')->constrained('mezzi')->onDelete('cascade');
            $table->foreignId('user_id')->constrained("users")->onDelete('cascade');
            $table->foreignId('template_id')->constrained('checklist_templates')->onDelete('cascade');
            $table->json('risultati'); // Array con i risultati dei controlli
            /* Struttura risultati JSON:
            {
                "controllo_luci": {"valore": true, "conforme": true, "note": ""},
                "pressione_gomme": {"valore": "ok", "conforme": true, "note": ""},
                "livello_carburante": {"valore": 75, "conforme": true, "note": ""}
            }
            */
            $table->boolean('conforme')->default(true); // Calcolato automaticamente
            $table->text('note_generali')->nullable();
            $table->timestamp('data_compilazione');
            $table->integer('km_mezzo'); // KM al momento della compilazione
            $table->enum('turno', ['mattina', 'pomeriggio', 'sera', 'notte'])->nullable();
            $table->string('destinazione_servizio')->nullable();
            $table->foreignId('supervisore_id')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('data_approvazione')->nullable();
            $table->text('note_supervisore')->nullable();
            $table->json('foto_anomalie')->nullable(); // Array path foto eventuali problemi
            $table->timestamps();
            
            // Indici
            $table->index(['mezzo_id', 'data_compilazione']);
            $table->index(['conforme', 'data_compilazione']);
            $table->index('user_id');
            $table->index('data_approvazione');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('checklist_compilate');
    }
};
