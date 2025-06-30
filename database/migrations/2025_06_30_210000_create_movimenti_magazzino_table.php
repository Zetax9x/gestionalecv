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
        Schema::create('movimenti_magazzino', function (Blueprint $table) {
            $table->id();
            $table->foreignId('magazzino_id')->constrained('magazzino')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->enum('tipo_movimento', [
                'carico',
                'scarico',
                'inventario',
                'rettifica',
                'trasferimento',
                'perdita',
                'donazione'
            ]);
            $table->decimal('quantita', 8, 2);
            $table->decimal('quantita_precedente', 8, 2)->default(0);
            $table->decimal('quantita_attuale', 8, 2)->default(0);
            $table->decimal('prezzo_unitario', 8, 2)->nullable();
            $table->decimal('valore_totale', 10, 2)->nullable();
            $table->timestamp('data_movimento');
            $table->string('causale')->nullable();
            $table->string('numero_documento')->nullable();
            $table->string('fornitore')->nullable();
            $table->text('note')->nullable();
            $table->boolean('approvato')->default(true);
            $table->foreignId('approvato_da')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('data_approvazione')->nullable();
            $table->timestamps();

            $table->index(['magazzino_id', 'tipo_movimento']);
            $table->index('data_movimento');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('movimenti_magazzino');
    }
};
