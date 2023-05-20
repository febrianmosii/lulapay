<?php namespace Lulapay\Transaction\Models;

use Model;

/**
 * Model
 */
class PaymentMethod extends Model
{
    use \October\Rain\Database\Traits\Validation;
    
    use \October\Rain\Database\Traits\SoftDelete;

    protected $dates = ['deleted_at'];


    /**
     * @var string The database table used by the model.
     */
    public $table = 'lulapay_transaction_payment_methods';

    /**
     * @var array Validation rules
     */
    public $rules = [
    ];

    public $belongsTo = [ 
        'payment_method_type' => 'Lulapay\Transaction\Models\PaymentMethodType',
        'payment_gateway_provider' => 'Lulapay\PaymentGateway\Models\Provider',
    ];

    public $attachOne = [
        'avatar' => 'System\Models\File'
    ];
}
