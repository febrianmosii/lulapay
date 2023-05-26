<?php namespace Lulapay\Transaction\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableUpdateLulapayTransactionTransactions3 extends Migration
{
    public function up()
    {
        Schema::table('lulapay_transaction_transactions', function($table)
        {
            $table->integer('customer_id')->nullable()->unsigned();
            $table->integer('payment_method_id')->default(null)->change();
            $table->dropColumn('name');
            $table->dropColumn('email');
            $table->dropColumn('phone');
            $table->dropColumn('user_id');
        });
    }
    
    public function down()
    {
        Schema::table('lulapay_transaction_transactions', function($table)
        {
            $table->dropColumn('customer_id');
            $table->integer('payment_method_id')->default(NULL)->change();
            $table->string('name', 50)->nullable()->default('NULL');
            $table->string('email', 110)->nullable()->default('NULL');
            $table->string('phone', 15)->nullable()->default('NULL');
            $table->integer('user_id')->nullable()->unsigned()->default(NULL);
        });
    }
}
