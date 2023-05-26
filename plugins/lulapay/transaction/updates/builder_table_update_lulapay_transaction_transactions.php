<?php namespace Lulapay\Transaction\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableUpdateLulapayTransactionTransactions extends Migration
{
    public function up()
    {
        Schema::table('lulapay_transaction_transactions', function($table)
        {
            $table->integer('payment_method_id')->nullable();
            $table->string('name', 50)->nullable();
            $table->string('email', 110)->nullable();
            $table->string('phone', 15)->nullable();
            $table->dropColumn('user_id');
            $table->dropColumn('lulapay_method_id');
        });
    }
    
    public function down()
    {
        Schema::table('lulapay_transaction_transactions', function($table)
        {
            $table->dropColumn('payment_method_id');
            $table->dropColumn('name');
            $table->dropColumn('email');
            $table->dropColumn('phone');
            $table->integer('user_id')->unsigned();
            $table->integer('lulapay_method_id')->nullable()->default(NULL);
        });
    }
}
