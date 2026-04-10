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
        Schema::create('staff_recurring_blocks', function (Blueprint $table) {
            $table->id();

            $table->foreignId('staff_member_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->tinyInteger('day_of_week'); // 0-6
            $table->time('start_time');
            $table->time('end_time');

            $table->string('label')->nullable(); // comida, break, etc

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('staff_recurring_blocks');
    }
};
