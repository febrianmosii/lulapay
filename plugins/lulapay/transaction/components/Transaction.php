<?php namespace Lulapay\Transaction\Components;

use Redirect;
use DB;
use Flash;
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
    
                return Redirect::to('/404');
            }
        }
        
        if ( ! $this->transaction) {
            $this->setStatusCode(404);
    
            return Redirect::to('/404');
        }

        if ($currentPageUrl === 'Cart') {
            return $this->cartPage();
        } else if ($currentPageUrl === 'Checkout') {
            return $this->checkOutPage();
        } else if ($currentPageUrl === 'Pay') {
            return $this->payPage();
        }
    }

    public function payPage() 
    {
        try {
            DB::beginTransaction();
            
            $response = $this->processMidtransPayment();
            
            $this->page['sandbox']               = env('MIDTRANS_PRODUCTION') === false;
            $this->page['payment_method']        = $this->transaction->payment_method;
            $this->page['transaction_status_id'] = $this->transaction->transaction_status_id;
            $this->page['status']                = $this->transaction->getStatusLabel();
    
            // If credit card
            if ( ! empty($response->redirect_url)) {
                $this->page['redirect_url'] = $response->redirect_url;
            } else {
                $vaNumber = '';
    
                if (isset($response->permata_va_number)) {
                    $vaNumber = $response->permata_va_number;
                } elseif (isset($response->va_numbers)) { // bca, bri, bni
                    $vaNumber = $response->va_numbers[0]->va_number;
                } elseif (isset($response->bill_key)) {
                    $vaNumber = $response->bill_key;
                }
        
                $expiryTime = $response->expiry_time ?? '';

                // Set default expiry time = 24 hours
                if ( ! $expiryTime) {
                    $expiryTime = date('d F Y H:i:s', strtotime($response->transaction_time.' + 1 day'));
                }
                
                $this->page['deeplink_url']   = $this->getDeeplinkUrl($response);
                $this->page['order_id']       = explode('|', $response->order_id)[0];
                $this->page['amount_charged'] = $response->gross_amount;
                $this->page['payment_code']   = $response->payment_code ?? '';
                $this->page['merchant_id']    = $response->merchant_id ?? '';
                $this->page['expiry_time']    = $expiryTime;
                $this->page['va_number']      = $vaNumber;
                $this->page['back_link_url']  = $this->pageUrl('cart/index', ['transactionHash' => $this->transaction->transaction_hash]);
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            dd($e);
            Flash::error($e->getMessage());
            
            return Redirect::back();;
        }
    }

    public function cartPage() 
    {
        $isSelectedMethod = $this->isSelectedMethod();

        if ($isSelectedMethod) {    
            $payUrl    = $this->pageUrl('checkout/payment', ['transactionHash' => $this->transaction->transaction_hash, 'methodId' => $this->transaction->payment_method_id]);
            
            return Redirect::to($payUrl);
        }
        
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
        $isSelectedMethod = $this->isSelectedMethod();

        if ($isSelectedMethod) {    
            $payUrl    = $this->pageUrl('checkout/payment', ['transactionHash' => $this->transaction->transaction_hash, 'methodId' => $this->transaction->payment_method_id]);
            
            return Redirect::to($payUrl);
        }
        
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
        
        $this->setConfigMidtrans();
        
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

        $paymentCode     = $this->payment_method->code;
        $paymentTypeCode = $this->payment_method->payment_method_type->code;

        if ($paymentTypeCode === 'e-wallet') {
            $transaction_data = array(
                'transaction_details' => $transaction_details,
                'payment_type'        => $paymentCode
            );

            if (in_array($paymentCode, ['shopeepay', 'qris'])) {
                $transaction_data = array(
                    'payment_type'        => $paymentCode,
                    'transaction_details' => $transaction_details,
                    'item_details'        => $items,
                    'customer_details'    => $customer_details,
                    'custom_expiry'       => [
                        'expiry_duration' => 60,
                        'unit'            => 'minute'
                    ]         
                );        

                if ($paymentCode == 'shopeepay') {
                    $transaction_data['shopeepay'] = [
                        'callback_url' => "htt  ps://midtrans.com/"
                    ];
                } else {
                    $transaction_data['qris'] = [
                        'acquirer' => "gopay"
                    ];
                }
            } else {
                $transaction_data = array(
                    'transaction_details' => $transaction_details,
                    'payment_type'        => $paymentCode
                );

                if ($paymentCode === 'qris') {
                    $transaction_data['acquirer'] = 'gopay';
                }
            }
        } else if ($paymentTypeCode === 'over-the-counter') {
            $transaction_data = array(
                'payment_type'        => 'cstore',
                'transaction_details' => $transaction_details,
                'cstore'              => [
                    'store' => 'alfamart',
                    'message' => 'Pembayaran Lulapay'
                ],
                'custom_expiry'       => [
                    'expiry_duration' => 24,
                    'unit'            => 'hour'
                ]         
            );        
        } else {
            // Transaction data to be sent
            $transaction_data = array(
                'transaction_details' => $transaction_details,
                'item_details'        => $items,
                'customer_details'    => $customer_details,
                'custom_expiry'       => [
                    'expiry_duration' => 60,
                    'unit'            => 'minute'
                ]         
            );
        }

        if ($paymentTypeCode == 'bank-transfer') {
            if ($paymentCode == 'permata') {
                $transaction_data['payment_type'] = 'permata';
            } else {
                $transaction_data['payment_type'] = 'bank_transfer';
        
                if ($paymentCode !== 'mandiri') {
                    $transaction_data['bank_transfer'] = [
                        'bank' => $paymentCode
                    ];
                } else {             
                    $transaction_data['payment_type'] = 'echannel';
                    $transaction_data['echannel'] = [
                        'bill_info1' => 'Payment for:',
                        'bill_info2' => 'debt'
                    ];
                }
            }
        }

        if ($paymentTypeCode == 'card-payment') {
            $transaction_data['enabled_payments'] = [
                'credit_card'
            ];
            
            $transaction_data['credit_card'] = [
                'secure' => true    
            ];

            
            $response = \Midtrans\Snap::createTransaction($transaction_data);
        } else {
            $response = \Midtrans\CoreApi::charge($transaction_data);
        }

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

    public function setConfigMidtrans() 
    {
        // Get midtrans provider account from database
        $account = Account::whereHas('provider', function($q) {
            $q->whereCode('midtrans');
        })->first();


        // Set your Merchant Server Key
        \Midtrans\Config::$serverKey = $account->server_key;
        // Set to Development/Sandbox Environment (default). Set to true for Production Environment (accept real transaction).
        \Midtrans\Config::$isProduction = env('MIDTRANS_PRODUCTION');
    }    

    public function onCheckStatus()
    {
        $transactionHash = \Request::segment(2);

        if ($transactionHash) {
            $transaction = $this->getTrxByTransactionHash();
            
            if ($transaction) {
                $id = $transaction->getMidtransTrxId();
                
                if ($id) {
                    $this->setConfigMidtrans();

                    $transactionStatus = \Midtrans\Transaction::status($id);
    
                    if ( ! empty($transactionStatus->transaction_status)) {
                        $status = $transaction->setStatus($transactionStatus->transaction_status, 'midtrans');

                        $log = [
                            'type'                  => 'User-RQ',
                            'transaction_status_id' => $status,
                            'data'                  => json_encode($transactionStatus)
                        ];
                
                        $transaction->transaction_logs()->create($log);
        
                    }
                }
            }
        }
        
        $this->page['status'] = $transaction->getStatusLabel();

        Flash::success("Status telah diperbarui");
    }

    private function isSelectedMethod() 
    {
        return $this->transaction->payment_method_id ? true : false;
    }

    private function getDeeplinkUrl($array) 
    {
        $array = json_decode(json_encode($array), TRUE);

        $result = '';

        if ( ! empty($array['actions'])) {
            foreach ($array['actions'] as $item) {
                if ($item['name'] == 'deeplink-redirect') {
                    $result = $item['url'];
                    break;
                }
            }
        }
        
        return $result;
    }
}
