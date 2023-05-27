<?php namespace Lulapay\Transaction\Components;

use Cms\Classes\ComponentBase;
use Lulapay\PaymentGateway\Models\Account;
use Lulapay\Transaction\Models\Transaction as TransactionModel;
use Lulapay\Transaction\Models\PaymentMethod;
use Lulapay\Transaction\Models\TransactionLog;

class Transaction extends ComponentBase
{
    protected $transaction;
    protected $payment_method;

    public function onRun() 
    {
        // Get the current page URL
        $currentPageUrl = $this->controller->getPage()->title;

        $this->transaction = $this->getTrxByTransactionHash();

        if ($methodId = $this->param('methodId')) {
            $this->payment_method = PaymentMethod::find($methodId);

            if ( ! $this->payment_method) {
                $this->setStatusCode(404);
    
                return \Redirect::to('/404');
            }
        }
        
        if ( ! $this->transaction) {
            $this->setStatusCode(404);
    
            return \Redirect::to('/404');
        }

        if ($currentPageUrl === 'Cart') {
            $this->cartPage();
        } else if ($currentPageUrl === 'Checkout') {
            $this->checkOutPage();
        } else if ($currentPageUrl === 'Pay') {
            $this->payPage();
        }
    }

    public function payPage() 
    {
        // Validate what provider is selected
        $provider = $this->payment_method->payment_gateway_provider->code;

        if ($provider === 'midtrans') {
            $response = $this->processMidtransPayment();

            if (isset($response->permata_va_number)) {
                $vaNumber = $response->permata_va_number;
            } elseif (isset($response->va_numbers)) { // bca, bri, bni
                $vaNumber = $response->va_numbers[0]->va_number;
            } elseif (isset($response->bill_key)) {
                $vaNumber = $response->bill_key;
            }
    
            $this->page['payment_method'] = $this->transaction->payment_method;
            $this->page['status']         = $this->transaction->transaction_status()->getLabel();
            $this->page['order_id']       = explode('|', $response->order_id)[0];
            $this->page['amount_charged'] = $response->gross_amount;
            $this->page['expiry_time']    = date('d F Y H:i:s', strtotime($response->expiry_time));
            $this->page['va_numeber']     = $vaNumber;
            
        }
    }

    public function cartPage() 
    {
        $paymentMethods  = [];
        $merchantMethods = $this->transaction->merchant->payment_methods()->get();

        foreach ($merchantMethods as $method) {
            $groupName = $method->payment_method_type->name;
            $optionLabel = str_replace(' (Brankas)', '', $method->name);

            if (!array_key_exists($groupName, $paymentMethods)) {
                $paymentMethods[$groupName] = [];
            }

            $paymentMethods[$groupName][$method->id]['id']          = $method->id;
            $paymentMethods[$groupName][$method->id]['name']        = $optionLabel;
            $paymentMethods[$groupName][$method->id]['provider']    = $method->payment_gateway_provider->name;
            $paymentMethods[$groupName][$method->id]['description'] = $method->description;
            $paymentMethods[$groupName][$method->id]['logo']        = $method->logo->getThumb(80, 25);
        }
        
        $this->page['is_expired']       = $this->transaction->isExpired();
        $this->page['customer']         = $this->transaction->customer;
        $this->page['payment_methods']  = $paymentMethods;
        $this->page['transaction_hash'] = $this->transaction->transaction_hash;
        $this->page['items']            = $this->transaction->transaction_details;
    }

    public function checkoutPage() 
    {
        $this->page['back_link_url']    = $this->pageUrl('cart/index', ['transactionHash' => $this->transaction->transaction_hash]);;
        $this->page['payment_method']   = $this->payment_method;
        $this->page['transaction_hash'] = $this->transaction->transaction_hash;
    }

    
    public function componentDetails()
    {
        return [
            'name'        => 'Transaction Component',
            'description' => 'No description provided yet...'
        ];
    }

    public function defineProperties()
    {
        return [];
    }

    private function getTrxByTransactionHash()
    {
        $transactionHash = $this->param('transactionHash');

        return TransactionModel::whereTransactionHash($transactionHash)->first();
    }

    private function processMidtransPayment()
    {
        $result['error'] = true;

        // Check if already has request?
        $transactionLog = TransactionLog::whereTransactionId($this->transaction->id)->whereType('TRX')->first();

        if ($transactionLog) {
            return json_decode($transactionLog->data);
        }
        
        // Get midtrans provider account from database
        $account = Account::whereHas('provider', function($q) {
            $q->whereCode('midtrans');
        })->first();

        if ( ! $account) {
            $result['message'] = 'Provider account not found';

            return $result;
        }
        
        // Set your Merchant Server Key
        \Midtrans\Config::$serverKey = $account->server_key;
        // Set to Development/Sandbox Environment (default). Set to true for Production Environment (accept real transaction).
        \Midtrans\Config::$isProduction = false;
        // Set sanitization on (default)

        $transaction_details = array(
            'order_id'     => $this->transaction->invoice_code.'|'.time(),
            'gross_amount' => $this->transaction->total
        );

        // Populate items
        $items = [];

        foreach ($this->transaction->transaction_details as $key => $value) {
            $items[] = [
                'id'       => 'item-'.$key ,
                'price'    => $value->price,
                'quantity' => $value->quantity,
                'name'     => $value->item_name
            ];
        }

        // Populate customer's info
        $customer_details = array(
            'first_name' => $this->transaction->customer->name,
            'last_name'  => '',
            'email'      => $this->transaction->customer->email,
            'phone'      => $this->transaction->customer->phone,
        );

        // Transaction data to be sent
        $transaction_data = array(
            'transaction_details' => $transaction_details,
            'item_details'        => $items,
            'customer_details'    => $customer_details
        );

        if ($this->payment_method->payment_method_type->code == 'bank-transfer') {
            $transaction_data['payment_type'] = 'bank_transfer';
    
            if ($this->payment_method->code !== 'mandiri') {
                $transaction_data['bank_transfer'] = [
                    'bank' => $this->payment_method->code
                ];
            } else {             
                $transaction_data['payment_type'] = 'echannel';
                $transaction_data['echannel'] = [
                    'bill_info1' => 'Payment for:',
                    'bill_info2' => 'debt'
                ];}
        }

        $response = \Midtrans\CoreApi::charge($transaction_data);

        $log = [
            'type'                  => 'TRX',
            'transaction_status_id' => 1,
            'data'                  => json_encode($response)
        ];

        $this->transaction->transaction_logs()->create($log);

        $this->transaction->payment_method_id = $this->payment_method->id;
        $this->transaction->save();
        
        return $response;
    }
}
