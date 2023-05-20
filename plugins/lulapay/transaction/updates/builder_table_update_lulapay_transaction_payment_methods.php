<?php namespace Lulapay\Transaction\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableUpdateLulapayTransactionPaymentMethods extends Migration
{
    public function up()
    {
        Schema::table('lulapay_transaction_payment_methods', function($table)
        {
            $table->integer('type_id')->nullable();
        });
    }
    
    public function down()
    {
        Schema::table('lulapay_transaction_payment_methods', function($table)
        {
            $table->dropColumn('type_id');
        });
    }
}
