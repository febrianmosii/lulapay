<?php namespace Lulapay\Transaction\Models;

use Lulapay\Merchant\Models\Merchant;
use Model;
use Ramsey\Uuid\Uuid;

/**
 * Model
 */
class Transaction extends Model
{
    use \October\Rain\Database\Traits\Validation;
    
    use \October\Rain\Database\Traits\SoftDelete;

    protected $dates = ['deleted_at'];

    public $fillable = [
        'invoice_code',
        'merchant_id',
        'customer_id',
        'payment_method_id',
        'total',
        'total_charged',
        'transaction_hash',
        'transaction_status_id',
        'items'
    ];

    /**
     * Relations
     */
    public $belongsTo = [ 
        'merchant' => 'Lulapay\Merchant\Models\Merchant',
        'customer' => 'Lulapay\Transaction\Models\Customer'
    ];

    public $hasMany = [
        'transaction_details' => ['Lulapay\Transaction\Models\TransactionDetail']
    ];

    /**
     * @var string The database table used by the model.
     */
    public $table = 'lulapay_transaction_transactions';

    /**
     * @var array Validation rules
     */
    public $rules = [
    ];

    public function beforeSave() 
    {
    }
    
    public function beforeCreate() {
        $this->transaction_hash      = Uuid::uuid4()->toString();
        $this->transaction_status_id = 1; # Set default as Pending for the first time
    }
}
