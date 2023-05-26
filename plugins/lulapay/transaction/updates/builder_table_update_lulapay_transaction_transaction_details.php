<?php namespace Lulapay\Transaction\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableUpdateLulapayTransactionTransactionDetails extends Migration
{
    public function up()
    {
        Schema::table('lulapay_transaction_transaction_details', function($table)
        {
            $table->decimal('price', 10, 0)->nullable();
        });
    }
    
    public function down()
    {
        Schema::table('lulapay_transaction_transaction_details', function($table)
        {
            $table->dropColumn('price');
        });
    }
}
