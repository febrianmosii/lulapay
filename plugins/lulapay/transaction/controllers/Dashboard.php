<?php namespace Lulapay\Transaction\Controllers;

use BackendMenu;
use DB;
use Backend\Classes\Controller;
use Lulapay\Merchant\Models\Merchant;
use Lulapay\Transaction\Models\Customer;
use Lulapay\Transaction\Models\Transaction;
use BackendAuth;

/**
 * Dashboard Back-end Controller
 */
class Dashboard extends Controller
{
    private $userRole;

    public function __construct()
    {
        parent::__construct();

        $user = BackendAuth::getUser();

        // Set the page title
        $this->pageTitle = 'Dashboard';
        $this->user      = $user;
        $this->userRole  = $user->role->code;

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

        // Add customer filter for merchant user
        if ($this->userRole === 'merchant') {
            $merchants = $this->user->merchants->pluck('id');
            
            $paidTransaction->whereIn("merchant_id", $merchants);

            $totalMerchant = Merchant::whereIn("id", $merchants)->count();
            $totalCustomer = Customer::whereHas('transactions', function($q) use ($merchants) {
                $q->whereIn("merchant_id", $merchants);
            })->count();            
        } else {
            $totalMerchant = Merchant::count();
            $totalCustomer = Customer::count();
        }

        $firstDayOfYear = date('Y-01-01 00:00:00'); // Get the first day of the year
        $currentDate    = date('Y-m-d H:i:s'); // Get the current date
                
        // Displaying box charts
        $paidTransactionAllTime = $paidTransaction->sum('total');
        $paidTransactionYTD     = $paidTransaction->whereBetween('created_at', [$firstDayOfYear, $currentDate])->sum('total');

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
        $this->vars['merchant_transactions']       = $merchantTransactions;
    }

    private function getLatest10Transactions($count = true) 
    {
        $data = [];
        
        if ($count) {
            $query = Transaction::whereTransactionStatusId(2)
            ->select(DB::raw('created_at as date, count(*) as count'))
            ->groupBy(DB::raw('DATE(created_at)'))
            ->latest()
            ->limit(10);

            if ($this->userRole === 'merchant') {
                $query->whereIn("merchant_id", $this->user->merchants->pluck('id'));
            }

            $transactions = $query->get();

            foreach ($transactions as $transaction) {
                $data[] = [
                    'timestamp' => strtotime($transaction->date) * 1000,
                    'value'     => $transaction->count
                ];
            } 
        } else {
            $query = Transaction::whereTransactionStatusId(2)->latest()->limit(10);

            if ($this->userRole === 'merchant') {
                $query->whereIn("merchant_id", $this->user->merchants->pluck('id'));
            }

            $data = $query->get();
        }
        

        return $data;
    }

    private function top5PaymentMethods() 
    {
        $query = DB::table("lulapay_transaction_transactions AS a")
        ->join("lulapay_transaction_payment_methods AS b", "b.id",  "=", "a.payment_method_id")
        ->select(DB::raw('b.name, count(*) as count'))
        ->groupBy("a.payment_method_id")
        ->whereTransactionStatusId(2)
        ->orderBy("count", "DESC")
        ->limit(5);

        if ($this->userRole === 'merchant') {
            $query->whereIn("a.merchant_id", $this->user->merchants->pluck('id'));
        }
        
        return $query->get();
    }

    private function top5Merchants() 
    {
        $query = DB::table("lulapay_transaction_transactions AS a")
        ->join("lulapay_merchant_merchants AS b", "b.id",  "=", "a.merchant_id")
        ->select(DB::raw('b.name, count(*) as count'))
        ->groupBy("a.merchant_id")
        ->whereTransactionStatusId(2)
        ->orderBy("count", "DESC")
        ->limit(5);
        
        if ($this->userRole === 'merchant') {
            $query->whereIn("a.merchant_id", $this->user->merchants->pluck('id'));
        }

        return $query->get();
    }

    private function getDataChart($data, $labelName)
    {
        if (empty($data[0]->total)) {
            return [];
        }

        $series = $data->pluck('total')->toArray();
        $labels = $data->pluck($labelName)->toArray();

        return [
            'series' => 12000,10000,123233,
            'labels' => implode(',', $labels),
        ];
    }
}
