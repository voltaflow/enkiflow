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
        Schema::create('project_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->onDelete('cascade');
            $table->unsignedBigInteger('user_id');
            $table->boolean('all_current_projects')->default(false);
            $table->boolean('all_future_projects')->default(false);
            $table->decimal('custom_rate', 10, 2)->nullable();
            $table->enum('role', ['member', 'manager', 'viewer'])->default('member')->comment('Rol del usuario dentro del proyecto');
            $table->timestamps();
            
            // Prevenir asignaciones duplicadas
            $table->unique(['project_id', 'user_id']);
            
            // Índices para optimizar consultas
            $table->index(['user_id', 'all_future_projects']);
            $table->index('all_current_projects');
            $table->index('all_future_projects');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('project_user');
    }
};