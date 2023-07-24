<?php namespace Lulapay\Merchant\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableUpdateLulapayMerchantMerchants2 extends Migration
{
    public function up()
    {
        Schema::table('lulapay_merchant_merchants', function($table)
        {
            $table->string('code', 255)->nullable();
        });
    }
    
    public function down()
    {
        Schema::table('lulapay_merchant_merchants', function($table)
        {
            $table->dropColumn('code');
        });
    }
}
