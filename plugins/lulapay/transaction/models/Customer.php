<?php namespace Lulapay\Transaction\Models;

use Model;

/**
 * Model
 */
class Customer extends Model
{
    use \October\Rain\Database\Traits\Validation;
    
    use \October\Rain\Database\Traits\SoftDelete;

    protected $dates = ['deleted_at'];

    public $fillable = [
        'name',
        'email',
        'phone'
    ];

    /**
     * @var string The database table used by the model.
     */
    public $table = 'lulapay_transaction_customers';

    /**
     * @var array Validation rules
     */
    public $rules = [
    ];

    public $hasMany = [
        'transactions' => ['Lulapay\Transaction\Models\Transaction']
    ];
}
