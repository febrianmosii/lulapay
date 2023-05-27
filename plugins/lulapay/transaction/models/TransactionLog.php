<?php namespace Lulapay\Transaction\Models;

use Model;

/**
 * Model
 */
class TransactionLog extends Model
{
    use \October\Rain\Database\Traits\Validation;
    
    use \October\Rain\Database\Traits\SoftDelete;

    protected $dates = ['deleted_at'];

    public $fillable = [
        'transaction_id',
        'type',
        'transaction_status_id',
        'data'
    ];

    /**
     * @var string The database table used by the model.
     */
    public $table = 'lulapay_transaction_transaction_logs';

    /**
     * @var array Validation rules
     */
    public $rules = [
    ];
}
