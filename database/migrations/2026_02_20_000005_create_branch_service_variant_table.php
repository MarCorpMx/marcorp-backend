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

            $table->foreignId('branch_service_id')
                ->constrained('branch_services')
                ->cascadeOnDelete();

            /*
            |--------------------------------------------------------------------------
            | VARIANTE REAL
            |--------------------------------------------------------------------------
            */
            $table->string('name');

            $table->text('description')->nullable();

            $table->unsignedInteger('duration_minutes');

            $table->decimal('price', 10, 2)->nullable();

            $table->unsignedInteger('max_capacity')->default(1);

            $table->string('mode', 50)->default('presential');
            /*presential
            online
            hybrid
            home_service
            provider_home
            phone_call
            onsite_business
            custom*/

            $table->boolean('includes_material')->default(false);

            $table->boolean('requires_meeting_link')->default(false);
            $table->string('meeting_provider')->nullable();

            /*
            |--------------------------------------------------------------------------
            | OPERACIÓN
            |--------------------------------------------------------------------------
            */
            $table->boolean('active')->default(true);

            $table->unsignedInteger('sort_order')->default(0);

            $table->timestamps();

            /*
            |--------------------------------------------------------------------------
            | SOFT DELETE
            |--------------------------------------------------------------------------
            */
            $table->softDeletes();

            /*
            |--------------------------------------------------------------------------
            | REGLAS
            |--------------------------------------------------------------------------
            */
            /*$table->unique(
                ['branch_service_id', 'name', 'deleted_at'],
                'branch_service_variant_unique'
            );*/

            /*
            |--------------------------------------------------------------------------
            | ÍNDICES
            |--------------------------------------------------------------------------
            */
            $table->index(['organization_id', 'branch_id']);
            $table->index(['branch_id', 'active']);
            $table->index(['branch_service_id', 'active']);
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
