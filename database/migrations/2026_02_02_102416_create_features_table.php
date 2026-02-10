<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('features', function (Blueprint $table) {
            $table->id();

            // Relación estructural con subsystem
            $table->foreignId('subsystem_id')
                ->constrained('subsystems')
                ->cascadeOnDelete();

            // Identidad
            $table->string('key');                  // agenda, clients, reports
            $table->string('name');                 // Agenda
            $table->text('description')->nullable();

            // Menú / UI
            $table->string('menu_label')->nullable();
            $table->string('menu_route')->nullable();
            $table->string('menu_icon')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);

            // Negocio
            $table->boolean('is_billable')->default(true);
            $table->boolean('is_core')->default(false);

            $table->timestamps();

            // Un feature no se puede repetir dentro del mismo subsystem
            $table->unique(['subsystem_id', 'key']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('features');
    }
};
