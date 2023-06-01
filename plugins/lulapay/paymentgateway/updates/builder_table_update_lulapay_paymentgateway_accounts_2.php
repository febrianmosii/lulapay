<?php namespace Lulapay\PaymentGateway\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableUpdateLulapayPaymentgatewayAccounts2 extends Migration
{
    public function up()
    {
        Schema::table('lulapay_paymentgateway_accounts', function($table)
        {
            $table->string('client_key', 255)->change();
            $table->string('server_key', 255)->change();
        });
    }
    
    public function down()
    {
        Schema::table('lulapay_paymentgateway_accounts', function($table)
        {
            $table->string('client_key', 38)->change();
            $table->string('server_key', 38)->change();
        });
    }
}
