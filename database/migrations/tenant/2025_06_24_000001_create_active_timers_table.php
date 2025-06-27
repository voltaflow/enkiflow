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
        Schema::create('active_timers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('project_id')->nullable();
            $table->unsignedBigInteger('task_id')->nullable();
            $table->string('description')->nullable();
            $table->timestamp('started_at');
            $table->timestamp('paused_at')->nullable();
            $table->boolean('is_running')->default(true);
            $table->boolean('is_paused')->default(false);
            $table->integer('duration')->default(0); // Seconds tracked so far
            $table->integer('paused_duration')->default(0); // Total seconds paused
            $table->json('metadata')->nullable();
            $table->string('sync_token')->unique();
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamps();

            // Indexes
            $table->index('user_id');
            $table->index('project_id');
            $table->index('task_id');
            $table->index('is_running');
            $table->index('last_synced_at');

            // Foreign keys
            // Note: user_id is not a foreign key because users table is in the central database
            $table->foreign('project_id')->references('id')->on('projects')->onDelete('set null');
            $table->foreign('task_id')->references('id')->on('tasks')->onDelete('set null');

            // Ensure only one active timer per user
            $table->unique(['user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('active_timers');
    }
};