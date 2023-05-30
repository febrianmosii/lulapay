<?php namespace Lulapay\Transaction\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableUpdateLulapayTransactionPaymentMethods7 extends Migration
{
    public function up()
    {
        Schema::table('lulapay_transaction_payment_methods', function($table)
        {
            $table->string('sandbox_simulator_url', 255)->nullable();
            $table->integer('payment_gateway_provider_id')->default(null)->change();
            $table->string('description', 255)->default(null)->change();
        });
    }
    
    public function down()
    {
        Schema::table('lulapay_transaction_payment_methods', function($table)
        {
            $table->dropColumn('sandbox_simulator_url');
            $table->integer('payment_gateway_provider_id')->default(NULL)->change();
            $table->string('description', 255)->default('NULL')->change();
        });
    }
}
