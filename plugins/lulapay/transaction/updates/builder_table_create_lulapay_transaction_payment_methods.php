<?php namespace Lulapay\Transaction\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableCreateLulapayTransactionPaymentMethods extends Migration
{
    public function up()
    {
        Schema::create('lulapay_transaction_payment_methods', function($table)
        {
            $table->engine = 'InnoDB';
            $table->increments('id')->unsigned();
            $table->integer('payment_gateway_provider_id')->nullable();
            $table->string('name', 12);
            $table->string('code', 12);
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->timestamp('deleted_at')->nullable();
        });
    }
    
    public function down()
    {
        Schema::dropIfExists('lulapay_transaction_payment_methods');
    }
}