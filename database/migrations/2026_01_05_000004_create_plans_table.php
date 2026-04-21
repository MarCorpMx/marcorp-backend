<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plans', function (Blueprint $table) {
            $table->id();

            /*
            |--------------------------------------------------------------------------
            | Relación con Subsystem
            |--------------------------------------------------------------------------
            */
            $table->foreignId('subsystem_id')
                ->nullable()
                ->constrained()
                ->cascadeOnDelete();

            /*
            |--------------------------------------------------------------------------
            | Orden / Identidad
            |--------------------------------------------------------------------------
            */
            $table->unsignedSmallInteger('sort_order')->default(0);

            $table->string('key'); // free, basic, pro, etc
            $table->string('name');
            $table->text('description')->nullable();

            /*
            |--------------------------------------------------------------------------
            | Estado / Visibilidad
            |--------------------------------------------------------------------------
            */
            $table->boolean('is_active')->default(true);
            $table->boolean('is_visible')->default(true); // ocultar founder
            $table->boolean('is_featured')->default(false); // destacar plan

            /*
            |--------------------------------------------------------------------------
            | Facturación
            |--------------------------------------------------------------------------
            */
            $table->enum('billing_period', ['monthly', 'yearly', 'lifetime'])
                ->default('monthly');

            $table->decimal('price', 10, 2)->default(0);

            /*
            |--------------------------------------------------------------------------
            | Control de ventas (Founder / promos)
            |--------------------------------------------------------------------------
            */
            $table->boolean('is_limited')->default(false);

            $table->integer('max_sales')->nullable();
            $table->integer('sales_count')->default(0);

            /*
            |--------------------------------------------------------------------------
            | Metadata flexible (🔥 CLAVE)
            |--------------------------------------------------------------------------
            | Aquí puedes meter:
            | - badge (founder)
            | - early_access
            | - priority_support
            | - cualquier cosa futura
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
            | Restricciones
            |--------------------------------------------------------------------------
            */
            $table->unique(['subsystem_id', 'key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plans');
    }
};
