<?php namespace Lulapay\Transaction\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableUpdateLulapayTransactionPaymentMethods5 extends Migration
{
    public function up()
    {
        Schema::table('lulapay_transaction_payment_methods', function($table)
        {
            $table->string('name', 50)->change();
        });
    }
    
    public function down()
    {
        Schema::table('lulapay_transaction_payment_methods', function($table)
        {
            $table->string('name', 12)->change();
        });
    }
}
