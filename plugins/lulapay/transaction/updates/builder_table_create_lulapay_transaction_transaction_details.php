<?php namespace Lulapay\Transaction\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableCreateLulapayTransactionTransactionDetails extends Migration
{
    public function up()
    {
        Schema::create('lulapay_transaction_transaction_details', function($table)
        {
            $table->engine = 'InnoDB';
            $table->increments('id')->unsigned();
            $table->integer('transaction_id')->unsigned();
            $table->string('item_name', 255);
            $table->integer('quantity')->unsigned();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->timestamp('deleted_at')->nullable();
        });
    }
    
    public function down()
    {
        Schema::dropIfExists('lulapay_transaction_transaction_details');
    }
}
