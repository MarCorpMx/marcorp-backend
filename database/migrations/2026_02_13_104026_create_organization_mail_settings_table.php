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
        Schema::create('organization_mail_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();

            $table->string('provider'); // 🔥 clave
            $table->string('mailer')->default('smtp');
            $table->string('host')->nullable();
            $table->integer('port')->nullable();
            $table->longText('username')->nullable();
            $table->longText('password')->nullable();
            $table->string('encryption')->nullable();
            $table->string('from_address')->nullable();
            $table->string('from_name')->nullable();

            $table->boolean('is_active')->default(false);
            $table->integer('priority')->default(1); // opcional pero pro

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('organization_mail_settings');
    }
};
