<?php namespace Lulapay\Transaction\Models;

use Model;

/**
 * Model
 */
class TransactionDetail extends Model
{
    use \October\Rain\Database\Traits\Validation;
    
    use \October\Rain\Database\Traits\SoftDelete;

    protected $dates = ['deleted_at'];

    public $fillable = [
        'transaction_id',
        'item_name',
        'quantity',
        'price'
    ];
    
    /**
     * @var string The database table used by the model.
     */
    public $table = 'lulapay_transaction_transaction_details';

    /**
     * @var array Validation rules
     */
    public $rules = [
    ];
}
