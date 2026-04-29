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
        Schema::create('branch_services', function (Blueprint $table) {
            $table->id();

            // Tenant / contexto
            $table->foreignId('organization_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('branch_id')
                ->constrained()
                ->cascadeOnDelete();

            // Servicio maestro
            $table->foreignId('service_id')
                ->constrained()
                ->cascadeOnDelete();

            // Overrides por sucursal
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('color')->nullable();

            // Estado real por sucursal
            $table->boolean('active')->default(true);

            // Orden visual opcional
            $table->unsignedInteger('sort_order')->default(0);

            $table->timestamps();

            // Un servicio solo una vez por sucursal
            $table->unique(['branch_id', 'service_id'], 'branch_service_unique');

            // Índices útiles
            $table->index(['organization_id', 'branch_id']);
            $table->index(['branch_id', 'active']);
            $table->index(['branch_id', 'sort_order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('branch_services');
    }
};
