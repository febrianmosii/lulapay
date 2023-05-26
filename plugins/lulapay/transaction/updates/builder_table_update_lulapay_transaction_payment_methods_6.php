<?php namespace Lulapay\Transaction\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableUpdateLulapayTransactionPaymentMethods6 extends Migration
{
    public function up()
    {
        Schema::table('lulapay_transaction_payment_methods', function($table)
        {
            $table->string('description', 255)->nullable();
            $table->text('tata_cara')->nullable();
            $table->integer('payment_gateway_provider_id')->default(null)->change();
        });
    }
    
    public function down()
    {
        Schema::table('lulapay_transaction_payment_methods', function($table)
        {
            $table->dropColumn('description');
            $table->dropColumn('tata_cara');
            $table->integer('payment_gateway_provider_id')->default(NULL)->change();
        });
    }
}
