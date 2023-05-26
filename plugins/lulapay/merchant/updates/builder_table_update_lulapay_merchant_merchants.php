<?php namespace Lulapay\Merchant\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableUpdateLulapayMerchantMerchants extends Migration
{
    public function up()
    {
        Schema::table('lulapay_merchant_merchants', function($table)
        {
            $table->dropColumn('logo');
        });
    }
    
    public function down()
    {
        Schema::table('lulapay_merchant_merchants', function($table)
        {
            $table->string('logo', 255)->nullable()->default('NULL');
        });
    }
}
