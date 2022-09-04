<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('username')->nullable()->default(null);
            $table->string('email')->nullable()->default(null);
            $table->string('picture')->nullable()->default(null);
            $table->string('authtoken')->nullable()->default(null);
            $table->string('password')->nullable()->default(null);
            $table->boolean('moderator')->nullable()->default(false);
            $table->boolean('confirmed')->nullable()->default(false);
            $table->bigInteger('created_at');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('users');
    }
}
