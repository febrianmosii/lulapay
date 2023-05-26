<?php namespace Lulapay\Merchant\Models;

use Lulapay\Transaction\Models\PaymentMethod;
use Ramsey\Uuid\Uuid;
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


    public $attachOne = [
        'logo' => 'System\Models\File'
    ];


    /**
     * Relations
     */
    public $belongsToMany = [
        'payment_methods' => [PaymentMethod::class, 'table' => 'lulapay_merchant_payment_methods']
    ];

    public function beforeCreate() 
    {
        $this->public_key = Uuid::uuid4()->toString();
        $this->server_key = Uuid::uuid4()->toString();
    }   

    // public function getPaymentMethodsOptions() 
    // {
    //     $result = [];

    //     foreach (PaymentMethod::all() as $method) {
    //         $result[$method->id] = [$method->payment_gateway_provider->name.' - '.$method->name, $method->payment_method_type->name];
    //     }

    //     return $result;
    // }

    public function getPaymentMethodsOptions()
    {
        $result = [];

        foreach (PaymentMethod::all() as $method) {
            $groupName = $method->payment_method_type->name;
            $optionLabel = $method->payment_gateway_provider->name.' - '.str_replace(' (Brankas)', '', $method->name);

            if (!array_key_exists($groupName, $result)) {
                $result[$groupName] = [];
            }

            $result[$groupName][$method->id] = $optionLabel;
        }

        return $result;
    }
}
