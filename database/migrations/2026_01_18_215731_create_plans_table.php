<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        /* 2026_02_20_104211_add_advanced_fields_to_plans_table -> Agrega nuevos campos: 
            is_visible => Para ocultar Founder del pricing público
            is_limited => Para marcar que tiene cupo
            max_sales => Para limitar cantidad vendida
            sales_count => Para llevar control sin hacer queries pesadas
            is_featured => Para destacar plan Pro en frontend
        */
        Schema::create('plans', function (Blueprint $table) {
            $table->id();

            /*
            |--------------------------------------------------------------------------
            | Relación con Subsystem
            |--------------------------------------------------------------------------
            | NULL = plan global (root / pruebas / promos internas)
            | NOT NULL = plan específico de un subsystem
            */
            $table->foreignId('subsystem_id')
                ->nullable()
                ->constrained()
                ->cascadeOnDelete();

            /*
            |--------------------------------------------------------------------------
            | Identidad del plan
            |--------------------------------------------------------------------------
            */
            $table->string('key');
            $table->string('name');
            $table->text('description')->nullable();

            /*
            |--------------------------------------------------------------------------
            | Facturación
            |--------------------------------------------------------------------------
            */
            $table->boolean('is_active')->default(true);
            $table->string('billing_period')->default('monthly'); // monthly | yearly | lifetime
            $table->decimal('price', 10, 2)->default(0);

            /*
            |--------------------------------------------------------------------------
            | Metadatos
            |--------------------------------------------------------------------------
            */
            $table->timestamps();

            /*
            |--------------------------------------------------------------------------
            | Restricciones
            |--------------------------------------------------------------------------
            | Un plan no puede repetirse dentro del mismo subsystem
            */
            $table->unique(['subsystem_id', 'key']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('plans');
    }
};
