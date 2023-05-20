<?php namespace Lulapay\PaymentGateway\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableUpdateLulapayPaymentgatewayProviders extends Migration
{
    public function up()
    {
        Schema::table('lulapay_paymentgateway_providers', function($table)
        {
            $table->string('host', 100)->nullable();
        });
    }
    
    public function down()
    {
        Schema::table('lulapay_paymentgateway_providers', function($table)
        {
            $table->dropColumn('host');
        });
    }
}
