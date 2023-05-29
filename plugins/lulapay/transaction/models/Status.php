<?php namespace Lulapay\Transaction\Models;

use Model;

/**
 * Model
 */
class Status extends Model
{
    use \October\Rain\Database\Traits\Validation;
    
    use \October\Rain\Database\Traits\SoftDelete;

    protected $dates = ['deleted_at'];

    protected $fillable = [
        'id'
    ];


    /**
     * @var string The database table used by the model.
     */
    public $table = 'lulapay_transaction_statuses';

    /**
     * @var array Validation rules
     */
    public $rules = [
    ];

}
