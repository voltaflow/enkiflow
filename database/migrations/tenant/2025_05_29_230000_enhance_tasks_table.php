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
        Schema::table('tasks', function (Blueprint $table) {
            // Add parent task support for subtasks
            $table->unsignedBigInteger('parent_task_id')->nullable()->after('project_id');
            $table->foreign('parent_task_id')->references('id')->on('tasks')->onDelete('cascade');

            // Add position for ordering (useful for Kanban)
            $table->integer('position')->default(0)->after('priority');

            // Add estimated hours
            $table->decimal('estimated_hours', 8, 2)->nullable()->after('due_date');

            // Add creator tracking
            $table->unsignedBigInteger('created_by')->nullable()->after('user_id');

            // Add more detailed status tracking
            $table->string('board_column')->default('todo')->after('status');

            // Add indexes for performance
            $table->index(['project_id', 'status']);
            $table->index(['project_id', 'board_column']);
            $table->index('parent_task_id');
            $table->index('position');
        });

        // Create task_assignees table for multiple assignees
        Schema::create('task_assignees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('user_id');
            $table->timestamps();

            $table->unique(['task_id', 'user_id']);
            $table->index('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('task_assignees');

        Schema::table('tasks', function (Blueprint $table) {
            $table->dropForeign(['parent_task_id']);
            $table->dropIndex(['project_id', 'status']);
            $table->dropIndex(['project_id', 'board_column']);
            $table->dropIndex('parent_task_id');
            $table->dropIndex('position');

            $table->dropColumn([
                'parent_task_id',
                'position',
                'estimated_hours',
                'created_by',
                'board_column',
            ]);
        });
    }
};
