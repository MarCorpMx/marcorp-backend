<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('user_plans', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('plan_id')
                ->constrained('plans')
                ->cascadeOnDelete();

            $table->timestamp('started_at');
            $table->timestamp('expires_at')->nullable();
            $table->boolean('is_active')->default(true);

            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('user_plans');
    }
};
