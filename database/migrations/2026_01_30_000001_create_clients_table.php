<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('clients', function (Blueprint $table) {
            $table->id();

            // Multi-tenant
            $table->foreignId('organization_id')
                ->constrained()
                ->cascadeOnDelete();

            // Auditoría opcional
            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            // Datos principales
            $table->string('first_name', 100);
            $table->string('last_name', 100);

            $table->string('email')->nullable();

            /*
            |--------------------------------------------------------------------------
            | Phone JSON
            |--------------------------------------------------------------------------
            | Compatible con ngx-intl-tel-input
            | Ejemplo esperado desde Angular:
            | {
            |   "number": "+5217771234567",
            |   "internationalNumber": "+52 1 777 123 4567",
            |   "nationalNumber": "7771234567",
            |   "e164Number": "+5217771234567",
            |   "countryCode": "MX",
            |   "dialCode": "+52"
            | }
            */
            $table->json('phone')->nullable();

            $table->date('birth_date')->nullable();

            // Flag futuro (no usado aún)
            $table->boolean('is_active')->default(true);

            $table->timestamps();

            // Unique por organización (multi-tenant real)
            $table->unique(['organization_id', 'email']);

            // Índice optimizado para index()
            $table->index(['organization_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clients');
    }
};
