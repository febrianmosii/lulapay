<?php namespace Lulapay\Merchant\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableCreateLulapayMerchantMerchants extends Migration
{
    public function up()
    {
        Schema::create('lulapay_merchant_merchants', function($table)
        {
            $table->engine = 'InnoDB';
            $table->increments('id')->unsigned();
            $table->string('name', 255);
            $table->text('address')->nullable();
            $table->string('admin_email', 255)->nullable();
            $table->string('admin_phone', 14)->nullable();
            $table->string('logo', 255)->nullable();
            $table->string('public_key', 64);
            $table->string('server_key', 64);
            $table->string('notif_callback_url', 255)->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->timestamp('deleted_at')->nullable();
            $table->dateTime('disabled_at')->nullable();
        });
    }
    
    public function down()
    {
        Schema::dropIfExists('lulapay_merchant_merchants');
    }
}
