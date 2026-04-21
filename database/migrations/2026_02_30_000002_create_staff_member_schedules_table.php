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
        Schema::create('staff_member_schedules', function (Blueprint $table) {
            $table->id();

            $table->foreignId('staff_member_id')
                ->constrained()
                ->onDelete('cascade');

            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();

            // 0 = Domingo, 6 = Sábado
            $table->unsignedTinyInteger('day_of_week');

            $table->time('start_time');
            $table->time('end_time');

            // Si trabaja en bloques (mañana / tarde)
            $table->boolean('is_active')->default(true);

            $table->timestamps();

            // índices
            $table->index(
                ['staff_member_id', 'branch_id', 'day_of_week'],
                'srb_staff_branch_day_idx'
            );
            $table->index(['branch_id', 'day_of_week']);
            // $table->check('end_time > start_time');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('staff_member_schedules');
    }
};
