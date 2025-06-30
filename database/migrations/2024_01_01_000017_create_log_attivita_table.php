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
        Schema::create('log_attivita', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
            
            // Azione
            $table->string('azione'); // create, update, delete, login, logout, view
            $table->string('modulo'); // volontari, mezzi, dpi, etc.
            $table->string('risorsa')->nullable(); // Nome specifico risorsa
            $table->bigInteger('risorsa_id')->nullable(); // ID della risorsa
            
            // Descrizione
            $table->text('descrizione');
            $table->text('descrizione_dettagliata')->nullable();
            
            // Dati Tecnici
            $table->string('ip_address');
            $table->text('user_agent');
            $table->string('metodo_http')->nullable(); // GET, POST, PUT, DELETE
            $table->string('url')->nullable();
            $table->json('parametri')->nullable(); // Parametri della request
            
            // Cambiamenti (per update)
            $table->json('valori_precedenti')->nullable();
            $table->json('valori_nuovi')->nullable();
            
            // Contesto
            $table->enum('livello', [
                'info',
                'warning', 
                'error',
                'critical',
                'security'
            ])->default('info');
            
            $table->enum('categoria', [
                'autenticazione',
                'autorizzazione',
                'crud', // Create, Read, Update, Delete
                'sistema',
                'sicurezza',
                'export',
                'import',
                'configurazione',
                'notifiche',
                'altro'
            ])->default('crud');
            
            // Geolocalizzazione (se disponibile)
            $table->string('paese')->nullable();
            $table->string('citta')->nullable();
            $table->string('provider_internet')->nullable();
            
            // Flag
            $table->boolean('successo')->default(true);
            $table->text('messaggio_errore')->nullable();
            $table->string('session_id')->nullable();
            
            // Tempi
            $table->timestamp('data_ora');
            $table->integer('durata_ms')->nullable(); // Durata operazione in millisecondi
            
            $table->timestamps();
            
            // Indici per performance
            $table->index(['user_id', 'data_ora']);
            $table->index(['modulo', 'azione']);
            $table->index(['data_ora', 'livello']);
            $table->index(['categoria', 'data_ora']);
            $table->index(['risorsa_id', 'modulo']);
            $table->index('ip_address');
            $table->index('successo');
            
            // Partitioning per data (da implementare se necessario)
            // $table->index('data_ora')->partition('BY RANGE (YEAR(data_ora))');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('log_attivita');
    }
};