<?php namespace Lulapay\Transaction\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableUpdateLulapayTransactionPaymentMethods3 extends Migration
{
    public function up()
    {
        Schema::table('lulapay_transaction_payment_methods', function($table)
        {
            $table->integer('payment_method_type_id')->unsigned();
            $table->integer('payment_gateway_provider_id')->unsigned()->default(null)->change();
        });
    }
    
    public function down()
    {
        Schema::table('lulapay_transaction_payment_methods', function($table)
        {
            $table->dropColumn('payment_method_type_id');
            $table->integer('payment_gateway_provider_id')->unsigned(false)->default(NULL)->change();
        });
    }
}
