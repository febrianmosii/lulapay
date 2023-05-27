<?php namespace Lulapay\Transaction\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableUpdateLulapayTransactionTransactions4 extends Migration
{
    public function up()
    {
        Schema::table('lulapay_transaction_transactions', function($table)
        {
            $table->dateTime('expired_time')->nullable();
            $table->integer('payment_method_id')->default(null)->change();
            $table->integer('customer_id')->default(null)->change();
        });
    }
    
    public function down()
    {
        Schema::table('lulapay_transaction_transactions', function($table)
        {
            $table->dropColumn('expired_time');
            $table->integer('payment_method_id')->default(NULL)->change();
            $table->integer('customer_id')->default(NULL)->change();
        });
    }
}
