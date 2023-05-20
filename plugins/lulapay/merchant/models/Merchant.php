<?php namespace Lulapay\Merchant\Models;

use Model;

/**
 * Model
 */
class Merchant extends Model
{
    use \October\Rain\Database\Traits\Validation;
    
    use \October\Rain\Database\Traits\SoftDelete;

    protected $dates = ['deleted_at'];


    /**
     * @var string The database table used by the model.
     */
    public $table = 'lulapay_merchant_merchants';

    /**
     * @var array Validation rules
     */
    public $rules = [
    ];
}
