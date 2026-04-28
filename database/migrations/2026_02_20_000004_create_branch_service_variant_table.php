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
        Schema::create('branch_service_variant', function (Blueprint $table) {
            $table->id();

            /*
            |--------------------------------------------------------------------------
            | RELACIONES
            |--------------------------------------------------------------------------
            */
            $table->foreignId('organization_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('branch_id')
                ->constrained('branches')
                ->cascadeOnDelete();

            $table->foreignId('service_variant_id')
                ->constrained('service_variants')
                ->cascadeOnDelete();

            /*
            |--------------------------------------------------------------------------
            | DISPONIBILIDAD EN SUCURSAL
            |--------------------------------------------------------------------------
            */

            // visible / habilitado en esta sucursal
            $table->boolean('active')->default(true);

            // orden visual catálogo / booking
            $table->unsignedInteger('sort_order')->default(0);

            /*
            |--------------------------------------------------------------------------
            | OVERRIDES (MISMO NOMBRE Y MISMO TIPO QUE service_variants)
            |--------------------------------------------------------------------------
            | null = usar valor global de service_variants
            */

            $table->string('name')->nullable();
            $table->text('description')->nullable();

            $table->unsignedInteger('duration_minutes')->nullable();

            $table->decimal('price', 10, 2)->nullable();

            $table->unsignedInteger('max_capacity')->nullable();

            $table->enum('mode', [
                'online',
                'presential',
                'hybrid'
            ])->nullable();

            $table->boolean('includes_material')->nullable();

            $table->timestamps();

            /*
            |--------------------------------------------------------------------------
            | ÚNICO
            |--------------------------------------------------------------------------
            */
            $table->unique(
                ['branch_id', 'service_variant_id'],
                'branch_service_variant_unique'
            );

            /*
            |--------------------------------------------------------------------------
            | ÍNDICES
            |--------------------------------------------------------------------------
            */
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
        Schema::dropIfExists('branch_service_variant');
    }
};
