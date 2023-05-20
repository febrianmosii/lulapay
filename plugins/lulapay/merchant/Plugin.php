<?php namespace Lulapay\Merchant;

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
            'merchant' => [
                'label'       => 'Merchants',
                'url'         => Backend::url('lulapay/merchant/merchants'),
                'icon'        => 'oc-icon-building-o',
                'permissions' => ['lulapay.merchant.*'],
                'sideMenu' => [
                    'merchants' => [
                        'label' => 'Merchants',
                        'icon'  => 'oc-icon-building-o',
                        'url'   => Backend::url('lulapay/merchant/merchants')
                    ]
                ]
            ]
        ];
    }
}
