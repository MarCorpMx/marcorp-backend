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
        Schema::create('staff_member_agenda_settings', function (Blueprint $table) {
            $table->id();

            $table->foreignId('staff_member_id')
                ->unique()
                ->constrained()
                ->onDelete('cascade');

            $table->unsignedInteger('slot_duration_minutes')->default(30);
            $table->unsignedInteger('buffer_time_minutes')->default(0);
            $table->unsignedInteger('max_daily_appointments')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('staff_member_agenda_settings');
    }
};
