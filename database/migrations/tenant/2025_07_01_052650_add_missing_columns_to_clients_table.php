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
        Schema::table('clients', function (Blueprint $table) {
            $table->string('city', 100)->nullable()->after('address');
            $table->string('state', 100)->nullable()->after('city');
            $table->string('country', 100)->nullable()->after('state');
            $table->string('postal_code', 20)->nullable()->after('country');
            $table->string('website')->nullable()->after('postal_code');
            $table->string('contact_name')->nullable()->after('website');
            $table->string('contact_email')->nullable()->after('contact_name');
            $table->string('contact_phone', 50)->nullable()->after('contact_email');
            $table->string('currency', 3)->default('USD')->after('timezone');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->dropColumn([
                'city',
                'state',
                'country',
                'postal_code',
                'website',
                'contact_name',
                'contact_email',
                'contact_phone',
                'currency',
            ]);
        });
    }
};