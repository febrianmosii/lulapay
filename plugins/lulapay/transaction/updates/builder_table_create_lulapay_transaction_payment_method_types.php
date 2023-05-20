<?php namespace Lulapay\Transaction\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableCreateLulapayTransactionPaymentMethodTypes extends Migration
{
    public function up()
    {
        Schema::create('lulapay_transaction_payment_method_types', function($table)
        {
            $table->engine = 'InnoDB';
            $table->increments('id')->unsigned();
            $table->string('name', 50)->nullable();
            $table->string('code', 50)->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->timestamp('deleted_at')->nullable();
        });
    }
    
    public function down()
    {
        Schema::dropIfExists('lulapay_transaction_payment_method_types');
    }
}
