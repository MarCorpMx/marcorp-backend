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
        Schema::create('professional_schedules', function (Blueprint $table) {
            $table->id();

            $table->foreignId('professional_id')
                ->constrained()
                ->cascadeOnDelete();

            // 0 = Domingo, 6 = Sábado
            $table->integer('day_of_week');

            $table->time('start_time');
            $table->time('end_time');

            // Si trabaja en bloques (mañana / tarde)
            $table->boolean('is_active')->default(true);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('professional_schedules');
    }
};
