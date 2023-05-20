<?php namespace Lulapay\PaymentGateway\Models;

use Model;

/**
 * Model
 */
class Account extends Model
{
    use \October\Rain\Database\Traits\Validation;
    
    use \October\Rain\Database\Traits\SoftDelete;

    protected $dates = ['deleted_at'];


    public $belongsTo = [ 
        'provider' => 'Lulapay\PaymentGateway\Models\Provider',
    ];
    
    /**
     * @var string The database table used by the model.
     */
    public $table = 'lulapay_paymentgateway_accounts';

    /**
     * @var array Validation rules
     */
    public $rules = [
    ];
}
