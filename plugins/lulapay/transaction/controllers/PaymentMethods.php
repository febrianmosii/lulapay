<?php namespace Lulapay\Transaction\Controllers;

use BackendMenu;
use Backend\Classes\Controller;

/**
 * Payment Methods Back-end Controller
 */
class PaymentMethods extends Controller
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

        $this->addCss("/plugins/lulapay/transaction/assets/css/custom.css");

        BackendMenu::setContext('Lulapay.Transaction', 'transaction', 'paymentmethods');
    }
}
