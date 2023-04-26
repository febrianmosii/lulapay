<?php namespace Lulapay\Transaction\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableCreateLulapayTransactionCustomers extends Migration
{
    public function up()
    {
        Schema::create('lulapay_transaction_customers', function($table)
        {
            $table->engine = 'InnoDB';
            $table->increments('id')->unsigned();
            $table->string('name', 25);
            $table->string('phone', 14)->nullable();
            $table->string('email', 55)->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->timestamp('deleted_at')->nullable();
        });
    }
    
    public function down()
    {
        Schema::dropIfExists('lulapay_transaction_customers');
    }
}
