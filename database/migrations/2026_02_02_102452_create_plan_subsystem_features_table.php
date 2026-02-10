<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('plan_subsystem_features', function (Blueprint $table) {
            $table->id();

            $table->foreignId('plan_id')->constrained()->cascadeOnDelete();
            $table->foreignId('subsystem_id')->constrained()->cascadeOnDelete();
            $table->foreignId('feature_id')->constrained()->cascadeOnDelete();

            $table->boolean('is_enabled')->default(false);
            $table->integer('limit_value')->nullable();

            $table->timestamps();

            $table->unique(['plan_id', 'subsystem_id', 'feature_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('plan_subsystem_features');
    }
};
