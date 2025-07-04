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
        Schema::table('space_users', function (Blueprint $table) {
            // Capacidad de horas semanales (por defecto 40h)
            $table->integer('capacity_hours')->default(40)->after('revoked_permissions');
            
            // Tasa de costo interno (costo por hora)
            $table->decimal('cost_rate', 10, 2)->nullable()->after('capacity_hours');
            
            // Tasa facturable (precio por hora al cliente)
            $table->decimal('billable_rate', 10, 2)->nullable()->after('cost_rate');
            
            // Estado del usuario en el espacio
            $table->enum('status', ['active', 'invited', 'archived'])->default('active')->after('billable_rate');
            
            // Índice para búsquedas por estado
            $table->index(['tenant_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('space_users', function (Blueprint $table) {
            $table->dropIndex(['tenant_id', 'status']);
            $table->dropColumn(['capacity_hours', 'cost_rate', 'billable_rate', 'status']);
        });
    }
};