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

            $table->foreignId('branch_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->tinyInteger('day_of_week');

            $table->time('start_time');
            $table->time('end_time');

            $table->string('label')->nullable();

            $table->timestamps();

            // ÍNDICES CLAVE
            $table->index(
                ['staff_member_id', 'branch_id', 'day_of_week'],
                'srb_staff_branch_dayw_idx'
            );


            $table->index(['branch_id', 'day_of_week']);

            // ANTI-DUPLICADOS
            $table->unique([
                'staff_member_id',
                'branch_id',
                'day_of_week',
                'start_time',
                'end_time'
            ], 'srb_staff_branch_day_start_end_unique');

            // OPCIONAL
            // $table->check('end_time > start_time');
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
