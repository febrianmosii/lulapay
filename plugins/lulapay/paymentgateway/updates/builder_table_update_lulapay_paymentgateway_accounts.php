<?php namespace Lulapay\PaymentGateway\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableUpdateLulapayPaymentgatewayAccounts extends Migration
{
    public function up()
    {
        Schema::table('lulapay_paymentgateway_accounts', function($table)
        {
            $table->string('api_host', 100)->nullable();
        });
    }
    
    public function down()
    {
        Schema::table('lulapay_paymentgateway_accounts', function($table)
        {
            $table->dropColumn('api_host');
        });
    }
}
