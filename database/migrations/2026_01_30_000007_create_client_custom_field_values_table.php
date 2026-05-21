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
        Schema::create('client_custom_field_values', function (Blueprint $table) {
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

            $table->foreignId('client_custom_field_id')
                ->constrained()
                ->cascadeOnDelete();

            /*
            |--------------------------------------------------------------------------
            | Compatibilidad futura
            |--------------------------------------------------------------------------
            |
            | Permite reutilizar campos personalizados
            | para mascotas, expedientes, vehículos, etc.
            |
            */

            $table->string('entity_type')->default('client');
            $table->unsignedBigInteger('entity_id')->nullable();

            /*
            |--------------------------------------------------------------------------
            | Valor
            |--------------------------------------------------------------------------
            |
            | Guardamos todo como texto para máxima flexibilidad.
            | El frontend interpreta según field_type.
            |
            */

            $table->longText('value')->nullable();

            /*
            |--------------------------------------------------------------------------
            | Metadata opcional
            |--------------------------------------------------------------------------
            */

            $table->json('meta')->nullable();

            $table->timestamps();

            /*
            |--------------------------------------------------------------------------
            | Índices
            |--------------------------------------------------------------------------
            */

            $table->index([
                'organization_id',
                'client_id'
            ], 'ccfv_org_client_idx');

            $table->index([
                'organization_id',
                'client_custom_field_id'
            ], 'ccfv_org_field_idx');

            $table->index([
                'entity_type',
                'entity_id'
            ], 'ccfv_entity_idx');

            /*
            |--------------------------------------------------------------------------
            | Evita duplicados
            |--------------------------------------------------------------------------
            |
            | Un cliente solo puede tener un valor
            | por campo personalizado.
            |
            */

            $table->unique([
                'client_id',
                'client_custom_field_id',
                'entity_type',
                'entity_id'
            ], 'ccfv_unique_value');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('client_custom_field_values');
    }
};
