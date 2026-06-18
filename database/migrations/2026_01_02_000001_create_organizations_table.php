<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('organizations', function (Blueprint $table) {
            $table->id();

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

            $table->foreignId('deleted_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            // Identidad
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('reference_prefix', 10)->nullable();
            $table->string('type', 30)->default('client'); // root | client
            $table->boolean('is_internal')->default(false);


            $table->string('slogan')->nullable();

            $table->string('business_niche', 50)->nullable();
            $table->string('business_subniche', 50)->nullable();


            // Dueño
            $table->foreignId('owner_user_id')
                ->constrained('users')
                ->cascadeOnDelete();

            // Estado
            $table->string('status', 20)->default('active');

            // Booking-publico
            $table->boolean(
                'online_booking_enabled'
            )->default(true);

            $table->string(
                'online_booking_disabled_message'
            )->nullable();

            // Onboarding
            $table->string('onboarding_step')->nullable()->default('email_pending');;
            $table->timestamp('onboarding_completed_at')->nullable();

            // Contacto
            $table->json('phone')->nullable();
            $table->string('email')->nullable();


            $table->string('website', 255)->nullable();

            // ========================
            // DIRECCIÓN (FISCAL)
            // ========================
            $table->string('country', 2)->nullable(); // mx, co
            $table->string('state', 100)->nullable();
            $table->string('city', 100)->nullable();
            $table->string('zip_code', 20)->nullable();
            $table->string('address', 255)->nullable();

            // ========================
            // 🧾 FACTURACIÓN (SAT)
            // ========================
            $table->string('legal_name', 255)->nullable(); // Razón social
            $table->string('tax_id', 20)->nullable(); // RFC

            $table->string('tax_regime', 10)->nullable();
            // Ej: 601, 626, etc

            $table->string('invoice_zip_code', 10)->nullable();
            // Código postal fiscal (MUY importante para CFDI)

            $table->string('cfdi_email', 150)->nullable();
            // Email para recibir facturas

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

            // Indice
            $table->index('reference_prefix');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('organizations');
    }
};
