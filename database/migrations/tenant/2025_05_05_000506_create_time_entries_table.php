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
        Schema::create('time_entries', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id'); // User from central database
            $table->foreignId('task_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('project_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('category_id')->nullable()->constrained('time_categories')->nullOnDelete();
            $table->dateTime('started_at');
            $table->dateTime('ended_at')->nullable();
            $table->integer('duration')->default(0); // en segundos
            $table->text('description')->nullable();
            $table->boolean('is_billable')->default(true);
            $table->boolean('is_manual')->default(false);
            $table->json('tags')->nullable();
            $table->json('metadata')->nullable(); // para datos de actividad, inactividad, etc.
            $table->timestamps();
            $table->softDeletes();

            // Índices para optimizar consultas frecuentes
            $table->index(['user_id', 'started_at']);
            $table->index(['project_id', 'started_at']);
            $table->index(['task_id', 'started_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('time_entries');
    }
};
