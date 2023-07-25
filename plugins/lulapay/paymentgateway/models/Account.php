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

    protected $id;
    protected $provider_id;
    protected $name;
    protected $merchant_key;
    protected $client_key;
    protected $server_key;
    protected $api_host;

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
        'name' => 'required|between:4,50|unique:lulapay_paymentgateway_accounts',
        'api_host' => 'required|string:6,255|regex:/^(https?:\/\/[\w\-\.]+(:[0-9]+)?(\/[\w\-\.]*)*\/?)$/'
    ];
}
