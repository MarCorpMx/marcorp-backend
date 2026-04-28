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
        Schema::create('branch_user_access', function (Blueprint $table) {
            $table->id();

            // Contexto organizacional
            $table->foreignId('organization_id')
                ->constrained()
                ->cascadeOnDelete();

            // Usuario del sistema (Lucy, Michelle, Margarita, etc)
            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete();

            // Sucursal
            $table->foreignId('branch_id')
                ->constrained()
                ->cascadeOnDelete();

            // Subsystem (appointments, web, billing, etc)
            $table->foreignId('subsystem_id')
                ->constrained()
                ->cascadeOnDelete();

            // Rol dentro de ese subsystem en esa sucursal
            $table->foreignId('role_id')
                ->constrained()
                ->cascadeOnDelete();

            // Si este user también es staff (opcional)
            $table->foreignId('staff_member_id')
                ->nullable()
                ->constrained('staff_members')
                ->nullOnDelete();

            // Flags útiles
            $table->boolean('is_active')->default(true);

            // Para cosas futuras (permisos custom, overrides, etc)
            $table->json('metadata')->nullable();

            $table->timestamps();

            // Restricción clave: evita duplicados
            $table->unique([
                'organization_id',
                'user_id',
                'branch_id',
                'subsystem_id'
            ], 'branch_user_access_unique');

            // Índices para performance (muy importantes en SaaS)
            $table->index(['organization_id', 'user_id']);
            $table->index(['branch_id', 'subsystem_id']);
            $table->index(['staff_member_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('branch_user_access');
    }
};
