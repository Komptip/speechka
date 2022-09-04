<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCommentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('comments', function (Blueprint $table) {
            $table->id();
            $table->text('content', 1000)->nullable()->default(null);
            $table->bigInteger('user_id')->nullable()->default(null);
            $table->bigInteger('post_id')->nullable()->default(null);
            $table->bigInteger('reply_to')->nullable()->default(null);
            $table->bigInteger('created_at');
            $table->boolean('active');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('comments');
    }
}
