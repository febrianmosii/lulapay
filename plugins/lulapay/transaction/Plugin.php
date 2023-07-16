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

    public function register()
    {
        $this->registerConsoleCommand('transaction:set_expired_transaction', \Lulapay\Transaction\Console\SetExpiredTransaction::class);
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

    public function registerSchedule($schedule)
    {
        // Set expired transaction
        $schedule->call(function () {

        })->everyMinute();
    }


    public function registerPermissions()
    {
        return [
            'lulapay.access_dashboard' => [
                'tab'   => 'Menu',
                'label' => 'Access Dashboard'
            ],
            'lulapay.access_merchants' => [
                'tab'   => 'Menu',
                'label' => 'Manage Merchants'
            ],
            'lulapay.access_payment_gateways' => [
                'tab'   => 'Menu',
                'label' => 'Manage Payment Gateways'
            ],
            'lulapay.access_transactions' => [
                'tab'   => 'Menu',
                'label' => 'Manage Transactions > Transactions'
            ],
            'lulapay.access_transaction_payment_methods' => [
                'tab'   => 'Menu',
                'label' => 'Manage Transactions > Payment Methods'
            ],
            'lulapay.access_transaction_payment_method_types' => [
                'tab'   => 'Menu',
                'label' => 'Manage Transactions > Payment Method Types'
            ],
            'lulapay.access_menu' => [
                'tab'   => 'Menu',
                'label' => 'Manage Admins'
            ],
        ];
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
            $navigation['OCTOBER.CMS.CMS'],
            $navigation['OCTOBER.SYSTEM.SYSTEM']
        );

        return [
            'dashboard' => [
                'label'       => 'Dashboard',
                'url'         => Backend::url('lulapay/transaction/dashboard'),
                'icon'        => 'icon-bar-chart',
                'permissions' => ['lulapay.access_dashboard'],
                'order'       => 1
            ],
            'transaction' => [
                'label'       => 'Transactions',
                'url'         => Backend::url('lulapay/transaction/transactions'),
                'icon'        => 'icon-money',
                'permissions' => ['lulapay.*'],
                'order'       => 4,
                'sideMenu' => [
                    'transactions' => [
                        'label' => 'Transactions',
                        'icon'  => 'icon-money',
                        'url'   => Backend::url('lulapay/transaction/transactions'),
                        'permissions' => ['lulapay.access_transaction']
                    ],
                    'paymentmethods' => [
                        'label' => 'Payment Method',
                        'icon'  => 'icon-credit-card',
                        'url'   => Backend::url('lulapay/transaction/paymentmethods'),
                        'permissions' => ['lulapay.access_payment_methods']
                    ],
                    'paymentmethodtypes' => [
                        'label' => 'Payment Method Types',
                        'icon'  => 'icon-cog',
                        'url'   => Backend::url('lulapay/transaction/paymentmethodtypes'),
                        'permissions' => ['lulapay.access_payment_method_types']
                    ],
                ]
            ]
        ];
    }

}
