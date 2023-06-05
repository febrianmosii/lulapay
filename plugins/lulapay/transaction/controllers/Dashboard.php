<?php namespace Lulapay\Transaction\Controllers;

use BackendMenu;
use DB;
use Backend\Classes\Controller;
use Lulapay\Merchant\Models\Merchant;
use Lulapay\Transaction\Models\Customer;
use Lulapay\Transaction\Models\Transaction;

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
        $this->addCss('/plugins/lulapay/transaction/assets/apexcharts/dist/apexcharts.css');
        $this->addJs('/plugins/lulapay/transaction/assets/apexcharts/dist/apexcharts.min.js');
        $this->addJs('/plugins/lulapay/transaction/assets/js/dashboard.js');

        // Display your custom HTML content here

        $paidTransaction = Transaction::whereTransactionStatusId(2);

        $firstDayOfYear = date('Y-01-01 00:00:00'); // Get the first day of the year
        $currentDate    = date('Y-m-d H:i:s'); // Get the current date
                
        // Displaying box charts
        $paidTransactionAllTime = $paidTransaction->sum('total');
        $paidTransactionYTD     = $paidTransaction->whereBetween('created_at', [$firstDayOfYear, $currentDate])->sum('total');
        $totalMerchant          = Merchant::count();
        $totalCustomer          = Customer::count();

        // Displaying transactions by merchant
        $merchantTransactions = $paidTransaction->with('merchant')->select(DB::raw('sum(total) as total'), 'merchant_id')->groupBy('merchant_id')->get();

        $this->vars['all_time_transaction']  = number_format($paidTransactionAllTime);
        $this->vars['ytd_transaction']       = number_format($paidTransactionYTD);
        $this->vars['total_merchant']        = number_format($totalMerchant);
        $this->vars['total_customer']        = number_format($totalCustomer);
        $this->vars['merchant_transactions'] = $this->getDataChart($merchantTransactions, 'merchant.name');
    }

    private function getDataChart($data, $labelName)
    {
        if (empty($data[0]->total)) {
            return [];
        }

        $series = $data->pluck('total')->toArray();
        $labels = $data->pluck($labelName)->toArray();

        return [
            'series' => implode(',', $series),
            'labels' => implode(',', $labels),
        ];
    }
}
