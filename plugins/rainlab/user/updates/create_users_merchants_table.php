<?php namespace RainLab\User\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class CreateUsersMerchants extends Migration
{

    public function up()
    {
        Schema::create('users_merchants', function($table)
        {
            $table->engine = 'InnoDB';
            $table->integer('user_id')->unsigned();
            $table->integer('merchant_id')->unsigned();
        });
    }

    public function down()
    {
        Schema::dropIfExists('users_merchants');
    }

}
