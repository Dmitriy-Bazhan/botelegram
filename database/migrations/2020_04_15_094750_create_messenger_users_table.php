<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateMessengerUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('messenger_users', function (Blueprint $table) {
            $table->increments('id');
            $table->string('id_chat');
            $table->string('name');
            $table->string('first_name')->default('unknow');
            $table->string('last_name')->default('unknow');
            $table->string('user_name')->default('unknow');
            $table->string('user_info');
            $table->boolean('user_agree')->default(true);
            $table->string('response');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('messenger_users');
    }
}
