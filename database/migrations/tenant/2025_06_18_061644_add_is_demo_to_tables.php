<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tablas a las que se añadirá el campo is_demo.
     */
    protected $tables = [
        'projects',
        'tasks',
        'task_states',
        'tags',
        'comments',
        'time_entries',
    ];

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        foreach ($this->tables as $table) {
            if (Schema::hasTable($table) && !Schema::hasColumn($table, 'is_demo')) {
                Schema::table($table, function (Blueprint $table) {
                    $table->boolean('is_demo')->default(false)->index();
                });
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        foreach ($this->tables as $table) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, 'is_demo')) {
                Schema::table($table, function (Blueprint $table) {
                    $table->dropColumn('is_demo');
                });
            }
        }
    }
};