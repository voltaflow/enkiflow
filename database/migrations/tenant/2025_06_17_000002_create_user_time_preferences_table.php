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
        Schema::create('user_time_preferences', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            
            // Time tracking preferences
            $table->decimal('daily_hours_goal', 4, 2)->default(8.00);
            $table->time('reminder_time')->default('17:00:00');
            $table->boolean('enable_idle_detection')->default(true);
            $table->boolean('enable_reminders')->default(true);
            $table->integer('idle_threshold_minutes')->default(10);
            
            // Additional preferences
            $table->boolean('allow_multiple_timers')->default(false);
            $table->string('default_billable')->default('project'); // 'project', 'yes', 'no'
            $table->string('week_starts_on')->default('monday'); // monday, sunday
            $table->boolean('show_weekend_days')->default(true);
            $table->string('time_format')->default('24h'); // '24h' or '12h'
            $table->string('date_format')->default('DD/MM/YYYY');
            
            // Notification preferences
            $table->boolean('email_daily_summary')->default(false);
            $table->boolean('email_weekly_summary')->default(true);
            $table->boolean('push_notifications')->default(true);
            
            $table->timestamps();
            
            // Indexes
            $table->unique('user_id');
            $table->index('enable_reminders');
            
            // Foreign key
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_time_preferences');
    }
};