<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('username', 255)->nullable()->unique()->after('id');
            $table->string('first_name', 100)->after('name');
            $table->string('last_name', 100)->after('first_name');
            $table->json('phone')->nullable()->after('last_name');
            $table->enum('status', ['active', 'inactive', 'blocked'])
                ->default('active')
                ->after('password');
            $table->string('company')->nullable()->after('status');
            $table->json('address')->nullable()->after('company');
            $table->boolean('email_verified')->default(false)->after('address');
            $table->timestamp('last_login_at')->nullable()->after('email_verified');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'username',
                'first_name',
                'last_name',
                'phone',
                'status',
                'company',
                'address',
                'email_verified',
                'last_login_at'
            ]);
        });
    }
};
