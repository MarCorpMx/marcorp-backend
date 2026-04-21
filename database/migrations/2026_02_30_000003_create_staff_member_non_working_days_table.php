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
        Schema::create('staff_member_non_working_days', function (Blueprint $table) {
            $table->id();

            $table->foreignId('staff_member_id')
                ->constrained()
                ->onDelete('cascade');

            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();

            $table->date('date');
            $table->string('reason')->nullable();

            $table->timestamps();


            $table->index(
                ['staff_member_id', 'branch_id', 'date'],
                'smnwd_staff_branch_date_idx'
            );
            $table->index(['branch_id', 'date']);
            
            $table->unique(
                ['staff_member_id', 'branch_id', 'date'],
                'smnwd_staff_branch_date_unique'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('staff_member_non_working_days');
    }
};
