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
        Schema::create('comments', function (Blueprint $table) {
            $table->id();
            $table->text('content');
            $table->foreignId('task_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('user_id'); // Usuario de la base de datos central
            $table->timestamp('edited_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            // Índice para mejorar rendimiento en búsquedas por usuario
            $table->index('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('comments');
    }
};
