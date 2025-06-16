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
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->foreignId('time_entry_id')->constrained()->cascadeOnDelete();
            $table->string('activity_type'); // 'keyboard', 'mouse', 'application_focus', etc.
            $table->json('metadata')->nullable(); // Datos específicos del tipo de actividad
            $table->timestamp('timestamp'); // Momento exacto de la actividad
            $table->timestamps();
            
            // Índices para optimizar consultas
            $table->index(['time_entry_id', 'timestamp']);
            $table->index(['user_id', 'timestamp']);
            $table->index('activity_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
    }
};