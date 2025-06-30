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
        Schema::create('documenti', function (Blueprint $table) {
            $table->id();
            $table->foreignId('volontario_id')->constrained()->onDelete('cascade');
            $table->foreignId('caricato_da')->constrained('users')->onDelete('cascade');
            
            // Informazioni Documento
            $table->string('nome_documento');
            $table->enum('tipo', [
                'carta_identita',
                'patente_guida',
                'codice_fiscale',
                'attestato_corso',
                'certificato_medico',
                'visita_medica',
                'vaccino',
                'autorizzazione',
                'privacy_gdpr',
                'foto_tessera',
                'curriculum',
                'referenze',
                'altro'
            ]);
            $table->string('sottotipo')->nullable(); // Es: patente_b, corso_blsd, ecc.
            
            // File e Storage
            $table->string('file_path');
            $table->string('file_originale');
            $table->string('mime_type');
            $table->integer('file_size'); // in bytes
            $table->string('hash_file'); // Per controllo integrità
            
            // Date e Scadenze
            $table->date('data_rilascio')->nullable();
            $table->date('data_scadenza')->nullable();
            $table->string('ente_rilascio')->nullable();
            $table->string('numero_documento')->nullable();
            
            // Validazione e Controlli
            $table->enum('stato_validazione', [
                'in_attesa',
                'validato',
                'rifiutato',
                'scaduto',
                'da_rinnovare'
            ])->default('in_attesa');
            $table->foreignId('validato_da')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('data_validazione')->nullable();
            $table->text('note_validazione')->nullable();
            
            // Notifiche Scadenza
            $table->boolean('notifica_scadenza')->default(true);
            $table->integer('giorni_preavviso')->default(30);
            $table->timestamp('ultima_notifica')->nullable();
            
            // Categorizzazione e Ricerca
            $table->json('tags')->nullable();
            $table->text('note')->nullable();
            $table->boolean('obbligatorio')->default(false);
            $table->boolean('pubblico')->default(false); // Visibile a tutti o solo admin
            
            // Versioning
            $table->integer('versione')->default(1);
            $table->foreignId('documento_precedente')->nullable()->constrained('documenti')->onDelete('set null');
            
            $table->timestamps();
            
            // Indici
            $table->index(['volontario_id', 'tipo']);
            $table->index('data_scadenza');
            $table->index('stato_validazione');
            $table->index(['tipo', 'data_scadenza']);
            $table->index('hash_file');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('documenti');
    }
};