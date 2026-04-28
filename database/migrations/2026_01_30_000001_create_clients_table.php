<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('clients', function (Blueprint $table) {
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

            /*
    |--------------------------------------------------------------------------
    | Identidad principal
    |--------------------------------------------------------------------------
    */
            $table->string('first_name', 100);
            $table->string('last_name', 100)->nullable(); // permitir solo nombre
            $table->string('preferred_name', 100)->nullable();

            /*
    |--------------------------------------------------------------------------
    | Contacto
    |--------------------------------------------------------------------------
    */
            $table->string('email')->nullable();

            /*
    | email_verified_at
    | null = no verificado
    | futuro:
    | - booking público con confirmación
    | - campañas email confiables
    | - recuperación de acceso portal cliente
    */
            $table->timestamp('email_verified_at')->nullable();

            /*
    phone JSON esperado:
    {
        "number": "+5217771234567",
        "internationalNumber": "+52 1 777 123 4567",
        "nationalNumber": "7771234567",
        "e164Number": "+5217771234567",
        "countryCode": "MX",
        "dialCode": "+52"
    }
    */
            $table->json('phone')->nullable();

            /*
    |--------------------------------------------------------------------------
    | Datos personales opcionales
    |--------------------------------------------------------------------------
    */
            $table->date('birth_date')->nullable();
            $table->string('gender', 30)->nullable();
            $table->string('preferred_language', 10)->nullable(); // es, en, fr
            $table->string('timezone', 80)->nullable();

            /*
    |--------------------------------------------------------------------------
    | Negocio / CRM
    |--------------------------------------------------------------------------
    */
            $table->string('source', 50)->nullable(); // web, whatsapp, instagram, referido
            $table->json('tags')->nullable(); // ["vip","frecuente","empresa"]
            $table->text('notes')->nullable();

            /*
    |--------------------------------------------------------------------------
    | Métricas rápidas
    |--------------------------------------------------------------------------
    */
            $table->timestamp('last_visit_at')->nullable();
            $table->timestamp('last_booking_at')->nullable();

            /*
    |--------------------------------------------------------------------------
    | Estado
    |--------------------------------------------------------------------------
    */
            $table->boolean('is_active')->default(true);
            $table->boolean('is_blocked')->default(false);
            $table->text('blocked_reason')->nullable();

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

            // búsquedas por email dentro de organización (sin unique)
            $table->index(['organization_id', 'email']);

            // futuro campañas / filtros
            $table->index(['organization_id', 'email_verified_at']);

            // CRM / listado
            $table->index(['organization_id', 'created_at']);
            $table->index(['organization_id', 'last_name']);
            $table->index(['organization_id', 'is_active']);
            $table->index(['organization_id', 'last_visit_at']);
            $table->index(['organization_id', 'last_booking_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clients');
    }
};
