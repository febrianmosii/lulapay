<?php namespace Lulapay\Merchant\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableCreateLulapayMerchantPaymentAccounts extends Migration
{
    public function up()
    {
        Schema::create('lulapay_merchant_payment_accounts', function($table)
        {
            $table->engine = 'InnoDB';
            $table->integer('merchant_id')->unsigned();
            $table->integer('payment_account_id')->unsigned();
        });
    }
    
    public function down()
    {
        Schema::dropIfExists('lulapay_merchant_payment_accounts');
    }
}
