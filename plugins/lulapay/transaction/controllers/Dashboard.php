<?php namespace Lulapay\Transaction\Controllers;

use BackendMenu;
use Backend\Classes\Controller;

/**
 * Dashboard Back-end Controller
 */
class Dashboard extends Controller
{
    
    public function __construct()
    {
        parent::__construct();

        // Set the page title
        $this->pageTitle = 'Custom Page';

        BackendMenu::setContext('Lulapay.Transaction', 'dashboard');
    }

    public function index()
    {
        $this->addCss('/plugins/lulapay/transaction/assets/css/dashboard.css');
        // Display your custom HTML content here
    }
}
