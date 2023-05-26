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

    public function registerComponents()
    {
        return [
            '\Lulapay\Transaction\Components\Transaction' => 'transaction'
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
        
        unset($navigation['OCTOBER.CMS.CMS']);

        return [
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
