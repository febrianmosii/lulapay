<?php namespace Lulapay\PaymentGateway\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableCreateLulapayPaymentgatewayAccounts extends Migration
{
    public function up()
    {
        Schema::create('lulapay_paymentgateway_accounts', function($table)
        {
            $table->engine = 'InnoDB';
            $table->increments('id')->unsigned();
            $table->integer('provider_id')->unsigned();
            $table->string('name', 50);
            $table->string('merchant_key', 25);
            $table->string('client_key', 38);
            $table->string('server_key', 38);
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->timestamp('deleted_at')->nullable();
        });
    }
    
    public function down()
    {
        Schema::dropIfExists('lulapay_paymentgateway_accounts');
    }
}
