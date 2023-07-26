<?php namespace Lulapay\Merchant\Models;

use BackendAuth;
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
        'name'               => 'required|between:6,255',
        'code'               => 'required|between:6,255|unique:lulapay_merchant_merchants',
        'admin_phone'        => 'required|digits_between:8,13|numeric',
        'admin_email'        => 'required|between:6,255|email',
        'notif_callback_url' => 'required|string:6,255|regex:/^(https?:\/\/[\w\-\.]+(:[0-9]+)?(\/[\w\-\.]*)*\/?)$/',
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

    public function getMerchantsOptions()
    {
        $result = [];

        $query = Merchant::select("id", "name");

        $user = BackendAuth::getUser();
        $userRole = $user->role->code;

        if ($userRole === 'merchant') {
            $query->whereIn("id", $user->merchants->pluck('id'));
        }

        $merchants = $query->get();

        foreach ($merchants as $merchant) {
            $optionLabel = $merchant->name;

            $result[$merchant->id] = $optionLabel;
        }

        return $result;
    }
}
