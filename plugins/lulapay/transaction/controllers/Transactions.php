<?php namespace Lulapay\Transaction\Controllers;

use Backend\Classes\Controller;
use BackendMenu;
use Backend;

class Transactions extends Controller
{
    public $implement = ['Backend\Behaviors\ListController', 'Backend\Behaviors\FormController'];
    
    public $listConfig = 'config_list.yaml';
    public $formConfig = 'config_form.yaml';

    public function __construct()
    {
        parent::__construct();

        BackendMenu::setContext('Lulapay.Transaction', 'transaction', 'transaction');
    }
}
