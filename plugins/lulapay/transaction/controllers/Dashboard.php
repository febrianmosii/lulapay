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

        $this->vars['all_time_transaction']        = number_format($paidTransactionAllTime);
        $this->vars['ytd_transaction']             = number_format($paidTransactionYTD);
        $this->vars['total_merchant']              = number_format($totalMerchant);
        $this->vars['total_customer']              = number_format($totalCustomer);
        $this->vars['top_5_payment_methods']       = $this->top5PaymentMethods();
        $this->vars['top_5_payment_merchants']     = $this->top5Merchants();
        $this->vars['latest_10_days_summaries']    = $this->getLatest10Transactions();
        $this->vars['latest_10_days_transactions'] = $this->getLatest10Transactions(false);
        $this->vars['merchant_transactions']       = $this->getDataChart($merchantTransactions, 'merchant.name');
    }

    private function getLatest10Transactions($count = true) 
    {
        $data = [];
        
        if ($count) {
            $transactions = Transaction::whereTransactionStatusId(2)
            ->select(DB::raw('created_at as date, count(*) as count'))
            ->groupBy(DB::raw('DATE(created_at)'))
            ->latest()
            ->limit(10)
            ->get();

            foreach ($transactions as $transaction) {
                $data[] = [
                    'timestamp' => strtotime($transaction->date) * 1000,
                    'value'     => $transaction->count
                ];
            } 
        } else {
            $data = Transaction::whereTransactionStatusId(2)->latest()->limit(10)->get();
        }
        

        return $data;
    }

    private function top5PaymentMethods() 
    {
        return DB::table("lulapay_transaction_transactions AS a")
        ->join("lulapay_transaction_payment_methods AS b", "b.id",  "=", "a.payment_method_id")
        ->select(DB::raw('b.name, count(*) as count'))
        ->groupBy("a.payment_method_id")
        ->whereTransactionStatusId(2)
        ->orderBy("count", "DESC")
        ->limit(5)
        ->get();
    }

    private function top5Merchants() 
    {
        return DB::table("lulapay_transaction_transactions AS a")
        ->join("lulapay_merchant_merchants AS b", "b.id",  "=", "a.merchant_id")
        ->select(DB::raw('b.name, count(*) as count'))
        ->groupBy("a.merchant_id")
        ->whereTransactionStatusId(2)
        ->orderBy("count", "DESC")
        ->limit(5)
        ->get();
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
