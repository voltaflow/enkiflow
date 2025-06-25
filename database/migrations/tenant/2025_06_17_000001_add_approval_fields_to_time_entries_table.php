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
        // This migration may have been partially applied, so we skip if already done
        if (Schema::hasColumn('time_entries', 'locked_at')) {
            return;
        }
        
        Schema::table('time_entries', function (Blueprint $table) {
            // Approval fields
            $table->timestamp('submitted_at')->nullable()->after('stopped_at');
            $table->timestamp('approved_at')->nullable()->after('submitted_at');
            $table->unsignedBigInteger('approved_by_id')->nullable()->after('approved_at');
            $table->timestamp('locked_at')->nullable()->after('approved_by_id');
            
            // Idle time tracking
            $table->integer('idle_minutes')->default(0)->after('duration');
            
            // Indexes for better query performance
            $table->index(['submitted_at', 'approved_at', 'user_id'], 'idx_time_entries_approval');
            $table->index('approved_by_id');
            $table->index('locked_at');
            
            // Foreign key
            $table->foreign('approved_by_id')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('time_entries', function (Blueprint $table) {
            // Drop foreign key first
            $table->dropForeign(['approved_by_id']);
            
            // Drop indexes
            $table->dropIndex('idx_time_entries_approval');
            $table->dropIndex(['approved_by_id']);
            $table->dropIndex(['locked_at']);
            
            // Drop columns
            $table->dropColumn([
                'submitted_at',
                'approved_at',
                'approved_by_id',
                'locked_at',
                'idle_minutes'
            ]);
        });
    }
};