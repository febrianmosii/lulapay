<?php namespace Lulapay\Transaction\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableCreateLulapayTransactionTransactions extends Migration
{
    public function up()
    {
        Schema::create('lulapay_transaction_transactions', function($table)
        {
            $table->engine = 'InnoDB';
            $table->increments('id')->unsigned();
            $table->integer('merchant_id')->unsigned();
            $table->integer('user_id')->unsigned();
            $table->integer('lulapay_method_id')->nullable();
            $table->string('invoice_code', 255);
            $table->string('transaction_hash', 64);
            $table->integer('transaction_status_id')->unsigned();
            $table->decimal('total', 10, 0);
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->timestamp('deleted_at')->nullable();
        });
    }
    
    public function down()
    {
        Schema::dropIfExists('lulapay_transaction_transactions');
    }
}
