<?php namespace Lulapay\Merchant\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableCreateLulapayMerchantPaymentMethods extends Migration
{
    public function up()
    {
        Schema::create('lulapay_merchant_payment_methods', function($table)
        {
            $table->engine = 'InnoDB';
            $table->integer('merchant_id')->unsigned();
            $table->integer('payment_method_id')->unsigned();
        });
    }
    
    public function down()
    {
        Schema::dropIfExists('lulapay_merchant_payment_methods');
    }
}
