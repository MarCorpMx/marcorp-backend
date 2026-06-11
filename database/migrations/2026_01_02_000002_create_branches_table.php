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

            /*
            |--------------------------------------------------------------------------
            | Auditoría
            |--------------------------------------------------------------------------
            */
            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->foreignId('updated_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->foreignId('blocked_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->foreignId('deleted_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            // Identidad    
            $table->string('name');
            $table->string('tagline', 120)->nullable(); // Centro Integral de Psicología para adolescentes y adultos.
            $table->text('description')->nullable(); // En Punto de Calma ayudamos a adolescentes y adultos a mejorar su bienestar emocional mediante terapia individual, terapia de pareja y acompañamiento psicológico profesional.
            $table->string('slug')->nullable();
            $table->string('reference_prefix', 10)->nullable();

            // Estatus
            $table->boolean('is_primary')->default(false);
            $table->boolean('is_active')->default(true);
            $table->boolean('locked_by_plan')->default(false);

            // Indicador de porcentaje (datos completados)
            $table->timestamp('public_profile_completed_at')->nullable();

            // Bloqueo
            $table->boolean('is_blocked')->default(false);
            $table->text('blocked_reason')->nullable();
            $table->timestamp('blocked_at')->nullable();

            // Contacto
            $table->json('phone')->nullable();
            $table->json('whatsapp_phone')->nullable();
            $table->string('email')->nullable();
            $table->string('website', 255)->nullable();
            $table->json('social_links')->nullable();

            // ubicación
            $table->string('country', 2)->nullable(); // Para guardar MX, US, CO, AR...
            $table->string('state', 100)->nullable();
            $table->string('city', 100)->nullable();
            $table->string('zip_code', 20)->nullable();
            $table->string('address', 255)->nullable();

            // Geolocalización
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->timestamp('location_verified_at')->nullable();

            // Mostrar parámetros
            $table->boolean('show_phone')->default(true);
            $table->boolean('show_email')->default(true);
            $table->boolean('show_website')->default(true);
            $table->boolean('show_whatsapp')->default(false);
            $table->boolean('show_social_links')->default(true);
            $table->boolean('show_address')->default(true);

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
            $table->string('timezone')->default('UTC');

            // Extra flexible config
            $table->json('metadata')->nullable();

            // Timestamps
            $table->timestamps();
            $table->softDeletes();

            // Índices
            //$table->index('organization_id');
            //$table->index('is_active');
            $table->unique(['organization_id', 'slug']);
            $table->index(['organization_id', 'is_active']);
            $table->index(['organization_id', 'is_primary']);
            $table->index(['latitude', 'longitude']);
            //$table->index('created_by');
            //$table->index('updated_by');
            //$table->index('blocked_by');
            //$table->index('deleted_by');
            $table->index('country');
            $table->index('city');
            $table->index('deleted_at');
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
