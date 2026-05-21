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
        Schema::create('client_branches', function (Blueprint $table) {
            $table->id();

            /*
            |--------------------------------------------------------------------------
            | Multi-tenant
            |--------------------------------------------------------------------------
            */
            $table->foreignId('organization_id')
                ->constrained()
                ->cascadeOnDelete();

            /*
            |--------------------------------------------------------------------------
            | Relaciones
            |--------------------------------------------------------------------------
            */
            $table->foreignId('client_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('branch_id')
                ->constrained()
                ->cascadeOnDelete();

            /*
            |--------------------------------------------------------------------------
            | Métricas operacionales
            |--------------------------------------------------------------------------
            */

            // primera vez que interactuó con la sucursal
            $table->timestamp('first_visit_at')->nullable();

            // última actividad/cita en la sucursal
            $table->timestamp('last_visit_at')->nullable();

            // cache rápido para dashboards/listados
            $table->unsignedInteger('appointments_count')->default(0);

            /*
            |--------------------------------------------------------------------------
            | Estado
            |--------------------------------------------------------------------------
            */

            // por ejemplo:
            // cliente frecuente de esta sucursal
            $table->boolean('is_primary')->default(false);

            /*
            |--------------------------------------------------------------------------
            | Extras futuros
            |--------------------------------------------------------------------------
            */

            $table->json('metadata')->nullable();

            /*
            |--------------------------------------------------------------------------
            | Timestamps
            |--------------------------------------------------------------------------
            */
            $table->timestamps();

            /*
            |--------------------------------------------------------------------------
            | Índices
            |--------------------------------------------------------------------------
            */

            // evita duplicados
            $table->unique([
                'client_id',
                'branch_id'
            ], 'client_branch_unique');

            // filtros rápidos
            $table->index([
                'organization_id',
                'branch_id'
            ]);

            $table->index([
                'organization_id',
                'client_id'
            ]);

            $table->index([
                'branch_id',
                'last_visit_at'
            ]);

            $table->index([
                'branch_id',
                'appointments_count'
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('client_branches');
    }
};
