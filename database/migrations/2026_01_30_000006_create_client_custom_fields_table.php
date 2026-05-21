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
        Schema::create('client_custom_fields', function (Blueprint $table) {
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
            | Identidad campo
            |--------------------------------------------------------------------------
            */

            // identificador interno
            // ej:
            // blood_type
            // skin_type
            // emergency_contact
            $table->string('key');

            // nombre visible
            $table->string('label');

            /*
            |--------------------------------------------------------------------------
            | Tipo campo
            |--------------------------------------------------------------------------
            */

            /*
            Tipos recomendados:

            text
            textarea
            number
            decimal
            date
            datetime
            boolean
            select
            multiselect
            radio
            checkbox
            email
            phone
            url
            json
            */

            $table->string('field_type', 50);

            /*
            |--------------------------------------------------------------------------
            | Configuración UI
            |--------------------------------------------------------------------------
            */

            $table->string('placeholder')->nullable();

            $table->text('help_text')->nullable();

            $table->boolean('is_required')
                ->default(false);

            $table->boolean('is_active')
                ->default(true);

            $table->boolean('is_visible')
                ->default(true);

            /*
            |--------------------------------------------------------------------------
            | Opciones
            |--------------------------------------------------------------------------
            */

            /*
            Ejemplo:

            [
                "A+",
                "A-",
                "O+"
            ]
            */

            $table->json('options')->nullable();

            /*
            |--------------------------------------------------------------------------
            | Reglas
            |--------------------------------------------------------------------------
            */

            // validaciones futuras
            $table->json('validation_rules')->nullable();

            /*
            |--------------------------------------------------------------------------
            | Organización UI
            |--------------------------------------------------------------------------
            */

            // "Información médica"
            // "Datos fiscales"
            // etc
            $table->string('group_name')
                ->nullable();

            $table->unsignedInteger('sort_order')
                ->default(0);

            /*
            |--------------------------------------------------------------------------
            | Metadata
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

            // evita campos duplicados
            $table->unique([
                'organization_id',
                'key'
            ]);

            $table->index([
                'organization_id',
                'is_active'
            ]);

            $table->index([
                'organization_id',
                'group_name'
            ]);

            $table->index([
                'organization_id',
                'sort_order'
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('client_custom_fields');
    }
};
