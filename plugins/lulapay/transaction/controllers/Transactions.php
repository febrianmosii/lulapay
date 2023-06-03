<?php namespace Lulapay\Transaction\Controllers;

use Backend\Classes\Controller;
use Cms\Classes\Controller as CMS_Controller;
use BackendMenu;
use Lulapay\PaymentGateway\Models\Account;
use DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Validator;
use Lulapay\Transaction\Models\Customer;
use Lulapay\Transaction\Models\Transaction;
use Stripe\Exception\SignatureVerificationException;
use Stripe\Webhook;


class Transactions extends Controller
{
    public $implement = ['Backend\Behaviors\ListController', 'Backend\Behaviors\FormController'];
    
    public $listConfig = 'config_list.yaml';
    public $formConfig = 'config_form.yaml';

    private $transactionData = [];

    public function __construct()
    {
        parent::__construct();

        BackendMenu::setContext('Lulapay.Transaction', 'transaction', 'transactions');
    }

    public function writeTrxLog() 
    {

    }

    public function create(Request $request)
    {
        // Retrieve the request data
        $this->transactionData = Input::all();

        // Validate the request data
        $rules = [
            'invoice_code'      => 'required|string|max:255',
            'name'              => 'required|string|max:50',
            'email'             => 'required|email|max:110',
            'phone'             => 'required|string|max:15',
            'total_charged'     => 'required|numeric|digits_between:1,10|not_in:0',
            'items'             => 'required|array',
            'items.*.price'     => 'required|numeric',
            'items.*.quantity'  => 'required|numeric|digits_between:1,10',
            'items.*.item_name' => 'required|string|max:255',        
        ];

        $validator = Validator::make($this->transactionData, $rules);

        if ($validator->fails()) {
            return Response::json([
                'error'   => true,
                'message' => 'Validation error',
                'errors'  => $validator->errors(),
            ], 400);
        }

        // Validate total
        if ( ! $this->isTotalValid()) {
            return Response::json([
                'error'   => true,
                'message' => 'Total charged is not match with the items total price'
            ], 400);
        }

        // Validate total
        if ($transactionHash = $this->checkInvoiceCode()) {
            $cmsController = new CMS_Controller();
            $checkoutUrl   = $cmsController->pageUrl('cart/index', ['transactionHash' => $transactionHash]);

            return Response::json([
                'error'        => true,
                'message'      => $this->transactionData['invoice_code'].' already has payment page',
                'redirect_url' => $checkoutUrl
            ], 200);
        }
        
        // Create TRX
        $insert = [
            'invoice_code' => $this->transactionData['invoice_code'],
            'merchant_id'  => $request->merchant->id,
            'customer_id'  => $this->getCustomerId(),
            'total'        => $this->transactionData['total_charged'],
        ];

        try {
            DB::beginTransaction();

            $transaction = new Transaction();
            $transaction->fill($insert);
            $transaction->save();

            $transaction->transaction_details()->createMany($this->transactionData['items']);
            
            $cmsController = new CMS_Controller();
            $checkoutUrl   = $cmsController->pageUrl('cart/index', ['transactionHash' => $transaction->transaction_hash]);

            DB::commit();

            return Response::json([
                'error'        => false,
                'message'      => 'Transaction Created Successfully',
                'redirect_url' => $checkoutUrl
            ], 201);
        } catch (\Throwable $th) {
            dd($th);
            DB::rollback();

            return Response::json([
                'error'   => true,
                'message' => 'Error occurred - Please contact the administrator for details',
            ], 500);
        }
    }

    private function getCustomerId() 
    {
        $customer = Customer::whereEmail($this->transactionData['email'])->first();

        if ($customer) {
            return $customer->id;
        }

        $customer        = new Customer();
        $customer->name  = $this->transactionData['name'];
        $customer->phone = $this->transactionData['phone'];
        $customer->email = $this->transactionData['email'];
        $customer->save();

        return $customer->id;
    }

    private function isTotalValid() 
    {
        $totalPrice   = 0;
        $totalCharged = $this->transactionData['total_charged'];


        foreach ($this->transactionData['items'] as $value) {
            $totalPrice += $value['price'] * $value['quantity'];
        }

        return $totalCharged == $totalPrice;
    }


    private function checkInvoiceCode() 
    {
        $transaction = Transaction::whereInvoiceCode($this->transactionData['invoice_code'])->first();

        return $transaction ? $transaction->transaction_hash : '';
    }

    public function notifMidtrans() 
    {
        // Get midtrans provider account from database
        $account = Account::whereHas('provider', function($q) {
            $q->whereCode('midtrans');
        })->first();


        // Set your Merchant Server Key
        \Midtrans\Config::$serverKey = $account->server_key;
        // Set to Development/Sandbox Environment (default). Set to true for Production Environment (accept real transaction).
        \Midtrans\Config::$isProduction = env('MIDTRANS_PRODUCTION');

        try {
            $notif = new \Midtrans\Notification();

            if ($notif ) {
                $notif = $notif->getResponse();
    
                $invoiceCode = explode('|', $notif->order_id)[0];
        
                $transaction = Transaction::whereInvoiceCode($invoiceCode)->first();
        
                if ($transaction) {
                    $status = $transaction->setStatus($notif->transaction_status, 'midtrans');
        
                    $log = [
                        'type'                  => 'Notif-RS',
                        'transaction_status_id' => $status,
                        'data'                  => json_encode($notif)
                    ];
            
                    $transaction->transaction_logs()->create($log);
                }

                return Response::json([
                    'error'   => false,
                    'message' => "Success",
                    "data"    => $notif
                ], 200);
            }
        } catch (\Exception $e) {
            $erroMessage = $e->getMessage();

            if ($transaction) {
                $log = [
                    'type'                  => 'Notif-RS-Error',
                    'transaction_status_id' => $status,
                    'data'                  => json_encode($notif)
                ];
        
                $transaction->transaction_logs()->create($log);
            }

            return Response::json([
                'error'   => true,
                'message' => $erroMessage
            ], 500);
        }
    }

    public function notifBrankas() 
    {
        $post = \Input::all();
        
        if ( ! empty($post['reference_id']) && ! empty($post['transaction_id'])) {
            $transactionId     = $post['transaction_id'];
            $transactionHash   = $post['reference_id'];
            $transactionStatus = $post['status'];
            
            $transaction = Transaction::whereHas('transaction_logs',function ($x) use ($transactionId) {
                return $x->where('type', 'TRX')->where('data', 'LIKE', '%'.$transactionId.'%');
            })->whereTransactionHash($transactionHash)->first();

            if ($transaction) {
                if ( ! empty($transactionStatus)) {
                    $transaction->setStatus($transactionStatus, 'brankas');
                }
            }
        }
    }

    public function notifStripe(Request $request)
    {
        $payload = $request->getContent();
        $headerStripeSignature = $request->header('stripe-signature');
        $endpointSecret = 'whsec_OxpL11srn6tbaPwSd0BoToc56SsokOsv';

        try {
            $event = Webhook::constructEvent(
                $payload,
                $headerStripeSignature,
                $endpointSecret
            );

            // Retrieve the payment object from the event
            $paymentObject = $event->data->object;
            $paymentId = $paymentObject->id;
            $paymentStatus = $paymentObject->payment_status;

            if (isset($paymentObject->metadata->transaction_hash)) {
                $transaction = Transaction::whereTransactionHash($paymentObject->metadata->transaction_hash)->first();

                if ($transaction) {
                    $transaction->setStatus($paymentStatus, 'stripe');
                    
                    $log = [
                        'type'                  => 'Notif-RS',
                        'transaction_status_id' => $transaction->transaction_status_id,
                        'data'                  => json_encode($paymentObject)
                    ];
            
                    $transaction->transaction_logs()->create($log);
                }

                return Response::json([
                    'error'   => false,
                    'message' => "webhook handled successfully",
                    'transaction_hash' => $paymentObject->metadata->transaction_hash,
                    "status" => $paymentStatus
                ], 200);
            }

            return Response::json([
                'error'   => true,
                'message' => "transaction not found"
            ], 404);
        } catch (SignatureVerificationException $e) {
            return Response::json([
                'error'   => true,
                'message' => $e->getMessage(),
                'get' => $_GET,
                'post' => $_POST,
                'request' => $request->all(),
                'headers' => $headers = collect($request->header())->transform(function ($item) {
                    return $item[0];
                })
            ], 400);
        }
    }
}
