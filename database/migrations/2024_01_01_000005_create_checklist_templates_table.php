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
        Schema::create('checklist_templates', function (Blueprint $table) {
            $table->id();
            $table->string('nome');
            $table->text('descrizione')->nullable();
            $table->enum('tipo_mezzo', [
                'ambulanza_a', 
                'ambulanza_b', 
                'auto_medica', 
                'auto_servizio', 
                'furgone', 
                'tutti'
            ])->default('tutti');
            $table->json('voci'); // Array di oggetti con le voci da controllare
            /* Struttura voci JSON:
            [
                {
                    "categoria": "Esterno",
                    "items": [
                        {"nome": "Controllo luci", "obbligatorio": true, "tipo": "boolean"},
                        {"nome": "Pressione gomme", "obbligatorio": true, "tipo": "select", "opzioni": ["ok", "bassa", "critica"]},
                        {"nome": "Livello carburante %", "obbligatorio": false, "tipo": "number", "min": 0, "max": 100}
                    ]
                }
            ]
            */
            $table->boolean('attivo')->default(true);
            $table->integer('ordine')->default(0);
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->timestamps();
            
            // Indici
            $table->index(['tipo_mezzo', 'attivo']);
            $table->index('ordine');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('checklist_templates');
    }
};