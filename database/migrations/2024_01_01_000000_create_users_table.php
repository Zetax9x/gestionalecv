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
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('nome');
            $table->string('cognome');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->string('telefono')->nullable();
            $table->date('data_nascita')->nullable();
            $table->string('codice_fiscale', 16)->nullable();
            $table->text('indirizzo')->nullable();
            $table->string('citta')->nullable();
            $table->string('cap', 5)->nullable();
            $table->string('provincia', 2)->nullable();
            $table->enum('ruolo', [
                'admin', 
                'direttivo', 
                'segreteria', 
                'mezzi', 
                'dipendente', 
                'volontario'
            ])->default('volontario');
            $table->boolean('attivo')->default(true);
            $table->timestamp('ultimo_accesso')->nullable();
            $table->string('avatar')->nullable();
            $table->text('note')->nullable();
            $table->json('dispositivi_autorizzati')->nullable(); // Per login automatico futuro
            $table->rememberToken();
            $table->timestamps();
            
            // Indici per performance
            $table->index(['ruolo', 'attivo']);
            $table->index('email');
            $table->index('ultimo_accesso');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};