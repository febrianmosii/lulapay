<?php namespace Lulapay\Transaction;

use System\Classes\PluginBase;
use Backend;

class Plugin extends PluginBase
{
    public function pluginDetails()
    {
        return [
            'name'        => 'Transaction',
            'description' => '',
            'icon'        => 'icon-money',
        ];
    }

    public function boot()
    {
        // listen for the display event of the Dashboard controller
        \Event::listen('backend.page.beforeDisplay', function($controller, $action){
            // redirect from dashboard to somewhere else
            if ($action == 'index' && $controller instanceof \Backend\Controllers\Index){
                return Backend::redirect('lulapay/transaction/dashboard');
            }
        });
    }

    public function registerComponents()
    {
        return [
            '\Lulapay\Transaction\Components\Transaction' => 'transaction',
            '\Lulapay\Transaction\Components\Simulator'   => 'simulator'
        ];
    }

    public function registerSettings()
    {
    }

    public function registerNavigation()
    {
        $navigationManager = \BackendMenu::instance();

        // Remove lulapay.Blog navigation items
        $navigation = $navigationManager->listMainMenuItems();
        $navigationManager->removeMainMenuItem('October.Cms', 'cms');
        
        unset(
            $navigation['OCTOBER.CMS.CMS']
        );

        return [
            'dashboard' => [
                'label'       => 'Dashboard',
                'url'         => Backend::url('lulapay/transaction/dashboard'),
                'icon'        => 'icon-bar-chart',
                'permissions' => ['lulapay.transaction.*'],
                'order'       => 1
            ],
            'transaction' => [
                'label'       => 'Transactions',
                'url'         => Backend::url('lulapay/transaction/transactions'),
                'icon'        => 'icon-money',
                'permissions' => ['lulapay.transaction.*'],
                'sideMenu' => [
                    'transactions' => [
                        'label' => 'Transactions',
                        'icon'  => 'icon-money',
                        'url'   => Backend::url('lulapay/transaction/transactions'),
                        // 'permissions' => ['lulapay.transaction.access_transaction']
                    ],
                    'paymentmethods' => [
                        'label' => 'Payment Method',
                        'icon'  => 'icon-credit-card',
                        'url'   => Backend::url('lulapay/transaction/paymentmethods'),
                        // 'permissions' => ['lulapay.transaction.access_transaction']
                    ],
                    'paymentmethodtypes' => [
                        'label' => 'Payment Method Types',
                        'icon'  => 'icon-cog',
                        'url'   => Backend::url('lulapay/transaction/paymentmethodtypes'),
                        // 'permissions' => ['lulapay.transaction.access_transaction']
                    ],
                ]
            ]
        ];
    }

}
