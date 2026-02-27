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
        Schema::create('non_working_days', function (Blueprint $table) {
            $table->id();

            $table->foreignId('professional_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->date('date');
            $table->string('reason')->nullable(); // Vacaciones, Congreso, etc
            $table->boolean('is_recurring')->default(false); // Cada 25 de diciembre, Todos los lunes festivos, etc

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('non_working_days');
    }
};
