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
        if (!Schema::hasTable('task_states')) {
            Schema::create('task_states', function (Blueprint $table) {
                $table->id();
                $table->string('tenant_id');
                $table->string('name');
                $table->string('color')->default('#3498db');
                $table->integer('position');
                $table->boolean('is_default')->default(false);
                $table->boolean('is_completed')->default(false);
                $table->boolean('is_demo')->default(false);
                $table->timestamps();
                
                // No foreign key in tenant database as tenants table is in central database
                $table->index('tenant_id');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('task_states');
    }
};