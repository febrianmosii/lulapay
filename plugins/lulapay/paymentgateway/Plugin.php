<?php namespace Lulapay\PaymentGateway;

use System\Classes\PluginBase;
use Backend;

class Plugin extends PluginBase
{
    public function registerComponents()
    {
    }

    public function registerSettings()
    {
    }

    public function registerNavigation()
    {
        return [
            'paymentgateway' => [
                'label'       => 'Payment Gateways',
                'url'         => Backend::url('lulapay/paymentgateway/providers'),
                'icon'        => 'icon-connectdevelop',
                'permissions' => ['lulapay.paymentgateway.*'],
                'sideMenu' => [
                    'providers' => [
                        'label' => 'Providers',
                        'icon'  => 'icon-connectdevelop',
                        'url'   => Backend::url('lulapay/paymentgateway/providers')
                    ],
                    'accounts' => [
                        'label' => 'Accounts',
                        'icon'  => 'icon-key',
                        'url'   => Backend::url('lulapay/paymentgateway/accounts')
                    ],
                ]
            ]
        ];
    }
}
