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
        // This migration runs in tenant databases
        Schema::create('project_permissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('projects')->onDelete('cascade');
            $table->unsignedBigInteger('user_id'); // References central.users
            $table->string('role', 50); // admin, manager, editor, member, viewer
            
            // Permisos explícitos - NULL = heredar del rol
            $table->boolean('can_manage_project')->nullable();
            $table->boolean('can_manage_members')->nullable();
            $table->boolean('can_edit_content')->nullable();
            $table->boolean('can_view_reports')->nullable();
            $table->boolean('can_track_time')->nullable();
            $table->boolean('can_view_budget')->nullable();
            $table->boolean('can_export_data')->nullable();
            $table->boolean('can_delete_content')->nullable();
            $table->boolean('can_manage_integrations')->nullable();
            $table->boolean('can_view_all_time_entries')->nullable();
            
            // Metadatos
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamp('expires_at')->nullable(); // Para permisos temporales
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            
            $table->timestamps();
            
            // Índices
            $table->unique(['project_id', 'user_id']);
            $table->index('user_id');
            $table->index('role');
            $table->index('is_active');
            $table->index('expires_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('project_permissions');
    }
};
