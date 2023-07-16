<?php namespace Lulapay\Merchant;

use System\Classes\PluginBase;
use Backend;

class Plugin extends PluginBase
{
    public function boot()
    {
        \Backend\FormWidgets\Relation::extend(function ($widget) {
            $widget->addViewPath(base_path() . '/plugins/lulapay/partials/widgets/form/partials');
        });
    }

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
                'permissions' => ['lulapay.access_merchants'],
                'order'       => 2,
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
