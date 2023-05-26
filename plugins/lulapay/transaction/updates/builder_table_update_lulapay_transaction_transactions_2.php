<?php namespace Lulapay\Transaction\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableUpdateLulapayTransactionTransactions2 extends Migration
{
    public function up()
    {
        Schema::table('lulapay_transaction_transactions', function($table)
        {
            $table->integer('user_id')->nullable()->unsigned();
        });
    }
    
    public function down()
    {
        Schema::table('lulapay_transaction_transactions', function($table)
        {
            $table->dropColumn('user_id');
        });
    }
}
