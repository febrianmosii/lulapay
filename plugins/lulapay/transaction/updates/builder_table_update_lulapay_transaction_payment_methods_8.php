<?php namespace Lulapay\Transaction\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableUpdateLulapayTransactionPaymentMethods8 extends Migration
{
    public function up()
    {
        Schema::table('lulapay_transaction_payment_methods', function($table)
        {
            $table->boolean('is_active')->nullable()->default(true);
            $table->integer('payment_gateway_provider_id')->default(null)->change();
        });
    }
    
    public function down()
    {
        Schema::table('lulapay_transaction_payment_methods', function($table)
        {
            $table->dropColumn('is_active');
            $table->integer('payment_gateway_provider_id')->default(NULL)->change();
        });
    }
}
