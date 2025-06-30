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
        Schema::create('allegati_tickets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ticket_id')->constrained('tickets')->onDelete('cascade');
            $table->foreignId('user_id')->constrained("users")->onDelete('cascade'); // Chi ha caricato
            
            // File Info
            $table->string('nome_file');
            $table->string('file_path');
            $table->string('file_originale');
            $table->string('mime_type');
            $table->integer('file_size');
            
            // Tipo e Categoria
            $table->enum('tipo', [
                'foto_problema',
                'foto_risoluzione',
                'documento',
                'fattura',
                'preventivo',
                'manuale',
                'schema',
                'altro'
            ]);
            $table->text('descrizione')->nullable();
            
            // Metadata
            $table->json('metadata')->nullable(); // EXIF per foto, etc.
            $table->boolean('pubblico')->default(true);
            
            $table->timestamps();
            
            // Indici
            $table->index(['ticket_id', 'tipo']);
            $table->index('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('allegati_tickets');
    }
};
