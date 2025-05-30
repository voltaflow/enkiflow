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
        Schema::table('tenants', function (Blueprint $table) {
            $table->string('name')->after('id');
            $table->string('slug')->unique()->after('name');
            $table->string('status')->default('active')->after('data');
            $table->boolean('auto_tracking_enabled')->default(false)->after('status');
            $table->timestamp('trial_ends_at')->nullable()->after('auto_tracking_enabled');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn([
                'name',
                'slug',
                'status',
                'auto_tracking_enabled',
                'trial_ends_at',
            ]);
        });
    }
};
