<?php namespace Lulapay\Transaction\Classes;

use Stripe\Stripe;
use Stripe\Checkout\Session;
use Lulapay\Transaction\Models\TransactionLog;
use Stripe\PaymentIntent;
use Lulapay\PaymentGateway\Models\Account;

class StripeClient
{
    public function __construct()
    {
        // Get midtrans provider account from database
        $account = Account::whereHas('provider', function($q) {
            $q->whereCode('stripe');
        })->first();
        
        Stripe::setApiKey($account->server_key);
    }

    public function charge($transaction, $payment_method) 
    {
        $result['error'] = true;

        // Check if already has request?
        $transactionLog = TransactionLog::whereTransactionId($transaction->id)->whereType('TRX')->first();

        if ($transactionLog) {
            return json_decode($transactionLog->data);
        }

        // Populate items
        $items = [];

        foreach ($transaction->transaction_details as $value) {
            $items[] = [
                'price_data' => [
                    'currency' => 'usd',
                    'product_data' => [
                        'name' => $value->item_name,
                    ],
                    'unit_amount' => $this->convertIdrToUsdCents($value->price), // Amount in cents
                ],
                'quantity' => $value->quantity,
            ];
        }
                
        $data = [
            'payment_method_types' => [
                $payment_method->code
            ],
            'line_items'  => $items,
            'mode'        => 'payment',
            'metadata' => [
                'transaction_hash' => $transaction->transaction_hash, 
            ],
            'success_url' => 'https://lulapay.my.id/transaction/success?transaction-hash='.$transaction->transaction_hash,
            'cancel_url'  => 'https://lulapay.my.id/transaction/failed?transaction-hash='.$transaction->transaction_hash
        ];

        if ($payment_method->code == 'wechat_pay') {
            $data['payment_method_options'] = [
                'wechat_pay' => [
                    'client' => 'web',
                ],
            ];
        }
        
        $response = Session::create($data);
                
        $log = [
            'type'                  => 'TRX',
            'transaction_status_id' => 1,
            'data'                  => json_encode($response)
        ];

        $transaction->transaction_logs()->create($log);

        $transaction->payment_method_id = $payment_method->id;
        $transaction->save();

        $transaction->sendEmailToCustomer();
        
        return $response;
    }

    function convertIdrToUsdCents($amountInIdr)
    {
        $usdExchangeRate = 0.000067;

        $amountInUsd = $amountInIdr * $usdExchangeRate;
        $amountInUsdCents = $amountInUsd * 100;

        return (int) $amountInUsdCents;
    }


    public function checkStatus($transactionId) 
    {
        $session = Session::retrieve($transactionId);
        return $session->payment_status ?? '';
    }
}
