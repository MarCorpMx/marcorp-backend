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
        Schema::create('client_pets', function (Blueprint $table) {
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
            | Relación principal
            |--------------------------------------------------------------------------
            */
            $table->foreignId('client_id')
                ->constrained()
                ->cascadeOnDelete();

            /*
            |--------------------------------------------------------------------------
            | Identidad mascota
            |--------------------------------------------------------------------------
            */

            $table->string('name');

            // perro, gato, conejo, etc
            $table->string('species', 50)->nullable();

            // husky, pug, persa, etc
            $table->string('breed', 100)->nullable();

            $table->enum('gender', [
                'male',
                'female',
                'unknown'
            ])->nullable();

            /*
            |--------------------------------------------------------------------------
            | Información física
            |--------------------------------------------------------------------------
            */

            $table->decimal('weight', 8, 2)->nullable();

            // kg, lb
            $table->string('weight_unit', 10)
                ->default('kg');

            $table->string('color')->nullable();

            /*
            |--------------------------------------------------------------------------
            | Fechas
            |--------------------------------------------------------------------------
            */

            $table->date('birth_date')->nullable();

            /*
            |--------------------------------------------------------------------------
            | Información médica básica
            |--------------------------------------------------------------------------
            */

            $table->text('allergies')->nullable();

            $table->text('medical_notes')->nullable();

            /*
            |--------------------------------------------------------------------------
            | Extras
            |--------------------------------------------------------------------------
            */

            // foto principal
            $table->string('photo_url')->nullable();

            // activo/inactivo/fallecido
            $table->string('status', 30)
                ->default('active');

            /*
            |--------------------------------------------------------------------------
            | Metadata flexible
            |--------------------------------------------------------------------------
            */

            $table->json('metadata')->nullable();

            /*
            |--------------------------------------------------------------------------
            | Timestamps
            |--------------------------------------------------------------------------
            */
            $table->timestamps();

            $table->softDeletes();

            /*
            |--------------------------------------------------------------------------
            | Índices
            |--------------------------------------------------------------------------
            */

            $table->index([
                'organization_id',
                'client_id'
            ]);

            $table->index([
                'organization_id',
                'species'
            ]);

            $table->index([
                'organization_id',
                'status'
            ]);

            $table->index([
                'client_id',
                'name'
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('client_pets');
    }
};
