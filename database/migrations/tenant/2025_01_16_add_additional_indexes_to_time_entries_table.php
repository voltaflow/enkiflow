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
        Schema::table('time_entries', function (Blueprint $table) {
            // Índices compuestos para optimizar consultas
            $table->index(['category_id', 'started_at']);
            // El índice ['started_at', 'ended_at'] ya existe en la migración anterior
            $table->index(['is_billable', 'project_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('time_entries', function (Blueprint $table) {
            $table->dropIndex(['category_id', 'started_at']);
            // El índice ['started_at', 'ended_at'] se elimina en otra migración
            $table->dropIndex(['is_billable', 'project_id']);
        });
    }
};