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
        Schema::connection('central')->table('invitation_logs', function (Blueprint $table) {
            if (!Schema::connection('central')->hasColumn('invitation_logs', 'metadata')) {
                $table->json('metadata')->nullable()->after('ip_address');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('central')->table('invitation_logs', function (Blueprint $table) {
            $table->dropColumn('metadata');
        });
    }
};