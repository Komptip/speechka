<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class LoadDataToUserAuthTokens extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::table('users')->get()->each(function($user){
            DB::table('user_auth_tokens')
            ->insert([
                'user_id' => $user->id,
                'token' => $user->authtoken
            ]);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('user_auth_tokens', function (Blueprint $table) {
            //
        });
    }
}
