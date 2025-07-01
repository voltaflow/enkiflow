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
        Schema::create('clients', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('name');
            $table->string('email')->nullable();
            $table->string('phone', 20)->nullable();
            $table->text('address')->nullable();
            $table->text('notes')->nullable();
            $table->string('timezone', 40)->default('America/Mexico_City');
            $table->boolean('is_active')->default(true);
            $table->boolean('is_demo')->default(false);
            $table->timestamps();
            $table->softDeletes();

            // Índices
            $table->index('slug');
            $table->index('is_active');
            $table->index(['name', 'email']); // Para búsqueda
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('clients');
    }
};