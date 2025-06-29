<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('volunteer_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('vehicle_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('category');
            $table->string('name');
            $table->string('path');
            $table->date('expiry_date')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('documents');
    }
};
