<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Stancl\Tenancy\Database\TenantMigration;

#[TenantMigration(tags: ['time-tracking', 'core'])]
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('time_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('color', 7)->default('#6366F1');
            $table->text('description')->nullable();
            $table->boolean('is_billable_default')->default(true);
            $table->timestamps();

            // Añadir índice para búsquedas por nombre
            $table->index('name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('time_categories');
    }
};
