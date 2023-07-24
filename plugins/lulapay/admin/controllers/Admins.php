<?php namespace Lulapay\Admin\Controllers;

use BackendMenu;
use Backend\Widgets\Filter;
use Backend\Classes\Controller;
use Event;

/**
 * Admins Back-end Controller
 */
class Admins extends Controller
{
    /**
     * @var array Behaviors that are implemented by this controller.
     */
    public $implement = [
        'Backend.Behaviors.FormController',
        'Backend.Behaviors.ListController'
    ];

    /**
     * @var string Configuration file for the `FormController` behavior.
     */
    public $formConfig = 'config_form.yaml';

    /**
     * @var string Configuration file for the `ListController` behavior.
     */
    public $listConfig = 'config_list.yaml';

    public function __construct()
    {
        parent::__construct();        

        BackendMenu::setContext('Lulapay.Admin', 'admin', 'admins');
    }

    public function listFilterExtendScopes($scopes)
    {
        $scopes = $scopes->getScopes();

        if ( ! empty($scopes['merchant'])) {
            Event::listen('backend.list.extendQuery', function($list, $query) use ($scopes){
                $merchantScope = $scopes['merchant']->value;

                if ( ! empty($merchantScope)) {
                    $query->whereHas('merchants', function($q) use ($scopes, $merchantScope) {
                        $merchantId = [];

                            foreach ($merchantScope as $key => $value) {
                                $merchantId[] = $key;
                            }
                            
                            if ($merchantId) {
                                $q->whereIn('merchant_id', $merchantId);
                            }
                    });
                }
            });
        }

    }

    public function listExtendQuery($query)
    {
        $user = \BackendAuth::getUser();
        $userRole = $user->role->code;

        if ($userRole === 'merchant') {
            $query->whereHas('merchants', function($q) use ($user) {
                $q->whereIn('merchant_id', $user->merchants->pluck('id'));
            });
        }
    }
}
