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
        Schema::create('branch_service_variant_staff', function (Blueprint $table) {
            $table->id();

            // Tenant (útil para filtros rápidos)
            $table->foreignId('organization_id')
                ->constrained()
                ->cascadeOnDelete();

            // Sucursal
            $table->foreignId('branch_id')
                ->constrained()
                ->cascadeOnDelete();

            // Variante REAL por sucursal
            $table->foreignId('branch_service_variant_id')
                ->constrained('branch_service_variant')
                ->cascadeOnDelete();

            // Staff asignado
            $table->foreignId('staff_member_id')
                ->constrained('staff_members')
                ->cascadeOnDelete();

            // Estado opcional
            $table->boolean('active')->default(true);

            // Orden opcional
            $table->unsignedInteger('sort_order')->default(0);

            $table->timestamps();

            // Evita duplicados
            $table->unique(
                ['branch_service_variant_id', 'staff_member_id'],
                'bsv_staff_unique'
            );

            // Índices útiles
            $table->index(['organization_id', 'branch_id']);
            $table->index(['branch_id', 'active']);
            $table->index(['staff_member_id', 'active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('branch_service_variant_staff');
    }
};
