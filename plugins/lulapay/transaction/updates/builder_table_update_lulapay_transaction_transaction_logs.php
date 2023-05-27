<?php namespace Lulapay\Transaction\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableUpdateLulapayTransactionTransactionLogs extends Migration
{
    public function up()
    {
        Schema::table('lulapay_transaction_transaction_logs', function($table)
        {
            $table->integer('transaction_status_id')->unsigned();
        });
    }
    
    public function down()
    {
        Schema::table('lulapay_transaction_transaction_logs', function($table)
        {
            $table->dropColumn('transaction_status_id');
        });
    }
}
