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
        Schema::create('promotions', function (Blueprint $table) {

            $table->id();

            $table->foreignId('organization_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('branch_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            // Opcional: promoción aplicada a un servicio
            $table->foreignId('branch_service_id')
                ->nullable()
                ->constrained('branch_services')
                ->nullOnDelete();

            // Opcional: promoción aplicada a una variante específica
            $table->foreignId('branch_service_variant_id')
                ->nullable()
                ->constrained('branch_service_variant')
                ->nullOnDelete();

            /*
            |--------------------------------------------------------------------------
            | Información
            |--------------------------------------------------------------------------
            */
            $table->string('name');

            $table->text('description')
                ->nullable();

            /*
            |--------------------------------------------------------------------------
            | Descuento
            |--------------------------------------------------------------------------
            */
            $table->enum('discount_type', [
                'fixed',
                'percentage'
            ]);

            $table->decimal('discount_value', 10, 2);

            /*
            |--------------------------------------------------------------------------
            | Configuración
            |--------------------------------------------------------------------------
            */

            // Permite definir qué promoción gana si existen varias activas
            $table->unsignedInteger('priority')
                ->default(0);

            // Permite combinar promociones futuras
            $table->boolean('stackable')
                ->default(false);

            // Límite total de usos (null = ilimitado)
            $table->unsignedInteger('max_uses')
                ->nullable();

            // Contador de usos realizados
            $table->unsignedInteger('used_count')
                ->default(0);

            /*
            |--------------------------------------------------------------------------
            | Vigencia
            |--------------------------------------------------------------------------
            */
            $table->dateTime('starts_at')
                ->nullable();

            $table->dateTime('ends_at')
                ->nullable();

            /*
            |--------------------------------------------------------------------------
            | Estado
            |--------------------------------------------------------------------------
            */
            $table->boolean('is_active')
                ->default(true);

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

            $table->timestamps();

            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('promotions');
    }
};
