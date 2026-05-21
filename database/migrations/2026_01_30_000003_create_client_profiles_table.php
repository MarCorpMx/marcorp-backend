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
        Schema::create('client_profiles', function (Blueprint $table) {
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
                ->unique()
                ->constrained()
                ->cascadeOnDelete();

            /*
            |--------------------------------------------------------------------------
            | Información extendida
            |--------------------------------------------------------------------------
            */

            // datos flexibles según nicho
            $table->json('profile_data')->nullable();

            /*
            |--------------------------------------------------------------------------
            | Preferencias
            |--------------------------------------------------------------------------
            */

            $table->json('preferences')->nullable();

            /*
            |--------------------------------------------------------------------------
            | Consentimientos
            |--------------------------------------------------------------------------
            */

            $table->json('consents')->nullable();

            /*
            |--------------------------------------------------------------------------
            | Información interna
            |--------------------------------------------------------------------------
            */

            $table->json('internal_flags')->nullable();

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

            $table->index([
                'organization_id',
                'client_id'
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('client_profiles');
    }
};
