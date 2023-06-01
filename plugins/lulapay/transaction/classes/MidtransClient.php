<?php namespace Lulapay\Transaction\Classes;


use Lulapay\Transaction\Models\TransactionLog;
use Lulapay\PaymentGateway\Models\Account;

class MidtransClient
{
    public function __construct()
    {
        // Get midtrans provider account from database
        $account = Account::whereHas('provider', function($q) {
            $q->whereCode('midtrans');
        })->first();
        
        
        \Midtrans\Config::$serverKey    = $account->server_key;
        \Midtrans\Config::$clientKey    = $account->merchant_key;
        \Midtrans\Config::$isProduction = env('ENV_PRODUCTION');
    }

    public function charge($transaction, $payment_method) 
    {
        $result['error'] = true;

        // Check if already has request?
        $transactionLog = TransactionLog::whereTransactionId($transaction->id)->whereType('TRX')->first();

        if ($transactionLog) {
            return json_decode($transactionLog->data);
        }
                
        $transaction_details = array(
            'order_id'     => $transaction->invoice_code.'|'.time(),
            'gross_amount' => $transaction->total
        );

        // Populate items
        $items = [];

        foreach ($transaction->transaction_details as $key => $value) {
            $items[] = [
                'id'       => 'item-'.$key ,
                'price'    => $value->price,
                'quantity' => $value->quantity,
                'name'     => $value->item_name
            ];
        }

        // Populate customer's info
        $customer_details = array(
            'first_name' => $transaction->customer->name,
            'last_name'  => '',
            'email'      => $transaction->customer->email,
            'phone'      => $transaction->customer->phone,
        );

        $paymentCode     = $payment_method->code;
        $paymentTypeCode = $payment_method->payment_method_type->code;

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
                        'expiry_duration' => 24,
                        'unit'            => 'hour'
                    ]         
                );        

                if ($paymentCode == 'shopeepay') {
                    $transaction_data['shopeepay'] = [
                        'callback_url' => "https://midtrans.com/"
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
                    'expiry_duration' => 24,
                    'unit'            => 'hour'
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

        $transaction->transaction_logs()->create($log);

        $transaction->payment_method_id = $payment_method->id;
        $transaction->save();
        
        return $response;
    }

    public function checkStatus($transactionId) 
    {
        return \Midtrans\Transaction::status($transactionId);
    }
}
