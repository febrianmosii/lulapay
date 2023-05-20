<?php namespace Lulapay\Transaction\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableUpdateLulapayTransactionPaymentMethods2 extends Migration
{
    public function up()
    {
        Schema::table('lulapay_transaction_payment_methods', function($table)
        {
            $table->integer('payment_gateway_provider_id')->default(null)->change();
            $table->dropColumn('type_id');
        });
    }
    
    public function down()
    {
        Schema::table('lulapay_transaction_payment_methods', function($table)
        {
            $table->dropColumn('payment_method_type_id');
            $table->integer('type_id')->nullable()->default(NULL);
        });
    }
}
