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
            $table->string('last_name', 100)->nullable()->after('first_name');
            $table->json('phone')->nullable()->after('last_name');
            $table->enum('status', ['active', 'inactive', 'blocked'])
                ->default('active')
                ->after('password');
            $table->boolean('is_super_admin')->default(false)->after('status');
            $table->json('address')->nullable()->after('is_super_admin');
            $table->timestamp('last_login_at')->nullable()->after('address');

            // Legal
            $table->boolean('accepted_terms')->default(false)->after('last_login_at');
            $table->timestamp('accepted_terms_at')->nullable()->after('accepted_terms');
            $table->string('accepted_terms_ip', 45)->nullable()->after('accepted_terms_at');
            $table->string('legal_version', 20)->nullable()->after('accepted_terms_ip');

            // Marketing
            $table->boolean('accept_marketing')->default(false)->after('legal_version');
            $table->timestamp('accept_marketing_at')->nullable()->after('accept_marketing');
            $table->string('accept_marketing_ip', 45)->nullable()->after('accept_marketing_at');

            //
            
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
