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
        Schema::create('branches', function (Blueprint $table) {
            $table->id();

            $table->foreignId('organization_id')
                ->constrained()
                ->cascadeOnDelete();

            // Identidad    
            $table->string('name');
            $table->string('slug')->nullable();
            $table->string('reference_prefix', 10)->nullable();

            // Estatus
            $table->boolean('is_active')->default(true);
            $table->boolean('is_primary')->default(false);

            // Contacto
            $table->json('phone')->nullable();
            $table->string('email')->nullable();
            $table->string('website', 255)->nullable();

            // ubicación
            $table->string('country', 2)->nullable(); // Para guardar MX, US, CO, AR...
            $table->string('state', 100)->nullable();
            $table->string('city', 100)->nullable();
            $table->string('zip_code', 20)->nullable();
            $table->string('address', 255)->nullable();

            // Branding
            $table->string('theme_key')->nullable();
            $table->string('primary_color', 20)->nullable();
            $table->string('secondary_color', 20)->nullable();
            $table->string('logo_url')->nullable();
            $table->boolean('white_label')->default(false);

            // Dominio
            $table->string('primary_domain')->nullable();
            $table->json('domains')->nullable();
            $table->boolean('force_https')->default(true);

            // Zona Horaria
            $table->string('timezone')->default('America/Mexico_City');

            // Extra flexible config
            $table->json('metadata')->nullable();

            $table->timestamps();

            // Índices
            $table->index('organization_id');
            $table->index('is_active');
            $table->unique(['organization_id', 'slug']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('branches');
    }
};
