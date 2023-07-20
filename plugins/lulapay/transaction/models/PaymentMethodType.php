<?php namespace Lulapay\Transaction\Models;

use Model;

/**
 * Model
 */
class PaymentMethodType extends Model
{
    use \October\Rain\Database\Traits\Validation;
    
    use \October\Rain\Database\Traits\SoftDelete;

    protected $dates = ['deleted_at'];


    /**
     * @var string The database table used by the model.
     */
    public $table = 'lulapay_transaction_payment_method_types';

    /**
     * @var array Validation rules
     */
    public $rules = [
        'name' => 'required|between:4,12|unique:lulapay_transaction_payment_method_types',
        'code' => 'required|between:4,12|unique:lulapay_transaction_payment_method_types',
    ];
}
