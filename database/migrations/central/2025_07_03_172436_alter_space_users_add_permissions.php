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
            // Añadir columna para permisos personalizados (JSON)
            $table->json('custom_permissions')->nullable()->after('role');
            
            // Añadir columna para permisos adicionales (JSON)
            // Esto permitirá otorgar permisos adicionales a un usuario sin cambiar su rol
            $table->json('additional_permissions')->nullable()->after('custom_permissions');
            
            // Añadir columna para permisos revocados (JSON)
            // Esto permitirá revocar permisos específicos de un rol sin cambiar el rol
            $table->json('revoked_permissions')->nullable()->after('additional_permissions');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('space_users', function (Blueprint $table) {
            $table->dropColumn('custom_permissions');
            $table->dropColumn('additional_permissions');
            $table->dropColumn('revoked_permissions');
        });
    }
};
