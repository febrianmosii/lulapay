<?php namespace Lulapay\PaymentGateway\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableUpdateLulapayPaymentgatewayProviders2 extends Migration
{
    public function up()
    {
        Schema::table('lulapay_paymentgateway_providers', function($table)
        {
            $table->dropColumn('host');
        });
    }
    
    public function down()
    {
        Schema::table('lulapay_paymentgateway_providers', function($table)
        {
            $table->string('host', 100)->nullable()->default('NULL');
        });
    }
}
