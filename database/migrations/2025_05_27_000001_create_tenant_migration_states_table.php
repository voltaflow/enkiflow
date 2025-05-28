<?php

declare(strict_types=1);

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
        // Crear en la base de datos principal (landlord)
        Schema::create('tenant_migration_states', function (Blueprint $table) {
            $table->id();
            $table->string('tenant_id'); // Space usa string como ID
            $table->string('migration');
            $table->enum('status', ['pending', 'migrated', 'failed']);
            $table->text('error_message')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->unsignedInteger('batch')->nullable();
            $table->timestamps();
            
            // No podemos hacer foreign key a 'tenants' si está en otra conexión
            // Solo mantenemos el índice y unique constraint
            $table->unique(['tenant_id', 'migration']);
            
            // Índice para consultas rápidas por estado
            $table->index(['status', 'tenant_id']);
            $table->index('tenant_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tenant_migration_states');
    }
};