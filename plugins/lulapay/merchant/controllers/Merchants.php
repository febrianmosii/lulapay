<?php namespace Lulapay\Merchant\Controllers;

use BackendMenu;
use Backend\Classes\Controller;
use BackendAuth;

/**
 * Merchants Back-end Controller
 */
class Merchants extends Controller
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

        BackendMenu::setContext('Lulapay.Merchant', 'merchant', 'merchants');
    }

    public function listExtendQuery($query)
    {
        $user = BackendAuth::getUser();
        $userRole = $user->role->code;

        if ($userRole === 'merchant') {
            $query->whereIn("id", $user->merchants->pluck('id'));
        }
    }
}
