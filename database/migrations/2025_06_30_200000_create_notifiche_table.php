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
        Schema::create('notifiche', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('cascade');
            $table->json('destinatari')->nullable();
            $table->string('titolo');
            $table->text('messaggio');
            $table->string('tipo');
            $table->json('letta_da')->nullable();
            $table->enum('priorita', ['bassa', 'normale', 'alta', 'urgente'])->default('normale');
            $table->string('url_azione', 500)->nullable();
            $table->string('testo_azione', 100)->nullable();
            $table->timestamp('scade_il')->nullable();
            $table->json('metadati')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            $table->index('user_id');
            $table->index(['user_id', 'tipo']);
            $table->index('tipo');
            $table->index('priorita');
            $table->index('scade_il');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notifiche');
    }
};
