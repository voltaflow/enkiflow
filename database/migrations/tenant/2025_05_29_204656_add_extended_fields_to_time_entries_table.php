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
            $table->foreignId('weekly_timesheet_id')->nullable()->after('user_id')
                ->constrained('weekly_timesheets')->nullOnDelete();
            $table->enum('created_from', ['timer', 'manual', 'import', 'template'])
                ->default('manual')->after('created_via');
            $table->foreignId('parent_entry_id')->nullable()->after('id')
                ->constrained('time_entries')->nullOnDelete();
            $table->boolean('locked')->default(false)->after('is_running');
            $table->timestamp('locked_at')->nullable()->after('locked');
            $table->unsignedBigInteger('locked_by')->nullable()->after('locked_at');

            // Indexes for performance
            $table->index('weekly_timesheet_id');
            $table->index('locked');
            $table->index(['started_at', 'ended_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('time_entries', function (Blueprint $table) {
            $table->dropForeign(['weekly_timesheet_id']);
            $table->dropForeign(['parent_entry_id']);

            $table->dropColumn([
                'weekly_timesheet_id',
                'created_from',
                'parent_entry_id',
                'locked',
                'locked_at',
                'locked_by',
            ]);
        });
    }
};
