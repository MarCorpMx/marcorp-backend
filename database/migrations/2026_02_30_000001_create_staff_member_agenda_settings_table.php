<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('staff_member_agenda_settings', function (Blueprint $table) {
            $table->id();

            $table->foreignId('staff_member_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('branch_id')
                ->constrained()
                ->cascadeOnDelete();

            // Configuración
            $table->integer('appointment_duration')->default(60);
            $table->integer('break_between_appointments')->default(0);
            $table->boolean('allow_online_booking')->default(true);
            $table->integer('minimum_notice_hours')->default(2);
            $table->boolean('allow_cancellation')->default(true);
            $table->integer('cancellation_limit_hours')->default(12);
            $table->string('timezone')->default('America/Mexico_City');

            $table->timestamps();

            // ÍNDICES
            $table->unique(['staff_member_id', 'branch_id']);
            $table->index(['branch_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('staff_member_agenda_settings');
    }
};
