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
        Schema::create('archivio_digitale', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); // Chi ha caricato
            
            // Informazioni Base
            $table->string('nome_documento');
            $table->text('descrizione')->nullable();
            $table->string('codice_documento')->unique()->nullable();
            
            // Classificazione
            $table->enum('sezione', [
                'mezzi',
                'dpi', 
                'volontari',
                'formazione',
                'amministrativo',
                'normative',
                'procedure',
                'contratti',
                'assicurazioni',
                'certificazioni',
                'altro'
            ]);
            $table->string('categoria')->nullable();
            $table->string('sottocategoria')->nullable();
            
            // Tags e Ricerca
            $table->json('tags')->nullable(); // Array di tag per ricerca
            $table->text('parole_chiave')->nullable();
            $table->text('contenuto_indicizzato')->nullable(); // Testo estratto per ricerca
            
            // File
            $table->string('file_path');
            $table->string('file_originale');
            $table->string('mime_type');
            $table->integer('file_size');
            $table->string('hash_file'); // Per controllo integrità e duplicati
            
            // Date e Versioning
            $table->date('data_documento')->nullable(); // Data originale del documento
            $table->date('data_scadenza')->nullable();
            $table->integer('versione')->default(1);
            $table->foreignId('documento_precedente')->nullable()->constrained('archivio_digitale')->onDelete('set null');
            $table->boolean('versione_corrente')->default(true);
            
            // Accesso e Sicurezza
            $table->enum('livello_accesso', [
                'pubblico',
                'interno',
                'riservato',
                'confidenziale'
            ])->default('interno');
            $table->json('ruoli_autorizzati')->nullable(); // Array ruoli che possono accedere
            $table->json('utenti_autorizzati')->nullable(); // Array user_id autorizzati
            
            // Metadati
            $table->string('autore_originale')->nullable();
            $table->string('ente_emittente')->nullable();
            $table->string('numero_protocollo')->nullable();
            $table->date('data_protocollo')->nullable();
            
            // Tracking
            $table->integer('downloads')->default(0);
            $table->timestamp('ultimo_accesso')->nullable();
            $table->json('log_accessi')->nullable(); // Array con storico accessi
            
            // Gestione
            $table->boolean('attivo')->default(true);
            $table->boolean('archiviato')->default(false);
            $table->timestamp('data_archiviazione')->nullable();
            $table->text('note')->nullable();
            
            $table->timestamps();
            
            // Indici
            $table->index(['sezione', 'attivo']);
            $table->index('data_scadenza');
            $table->index('livello_accesso');
            $table->index('hash_file');
            $table->index('versione_corrente');
            $table->fullText(['nome_documento', 'descrizione', 'parole_chiave']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('archivio_digitale');
    }
};