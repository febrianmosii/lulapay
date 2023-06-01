<?php namespace Lulapay\Transaction\Classes;


use Lulapay\Transaction\Models\TransactionLog;
use Lulapay\PaymentGateway\Models\Account;

class BrankasClient
{
    public $host;
    public $apiKey;

    private $accountDestId = [
        'BCA_PERSONAL'        => '8cad8ee8-ff7d-11ed-9ead-42010a880019',
        'BRI_PERSONAL'        => '8734f944-ff71-11ed-ad08-42010a880019',
        'DUMMY_BANK_PERSONAL' => 'c8f445e8-ff0c-11ed-a2fd-42010a880019',
    ];

    public function __construct()
    {
        // Get midtrans provider account from database
        $account = Account::whereHas('provider', function($q) {
            $q->whereCode('brankas');
        })->first();
        
        $this->host   = $account->api_host;
        $this->apiKey = $account->server_key;
    }

    public function charge($transaction, $payment_method) 
    {
        $result['error'] = true;

        // Check if already has request?
        $transactionLog = TransactionLog::whereTransactionId($transaction->id)->whereType('TRX')->first();

        if ($transactionLog) {
            return json_decode($transactionLog->data);
        }

        date_default_timezone_set('UTC');

        $currentDateTime = new \DateTime();
        $currentDateTime->modify('+1 day');

        $expiredTime   = $currentDateTime->format('Y-m-d\TH:i:s\Z');
        $accountDestId = $this->accountDestId[$payment_method->code] ?? '';
        
        $data = [
            'from' => [
                'type'      => 'BANK',
                'bank_code' => $payment_method->code,
                'country'   => 'ID'
            ],
            'client' => [
                'display_name'       => 'Lulapay',
                'logo_url'           => 'https://lulapay.my.id/themes/prismify-bootstrap-starter-kit/assets/images/logo.png',
                'return_url'         => 'https://lulapay.my.id/transaction/success',
                'fail_url'           => 'https://lulapay.my.id/transaction/failed?',
                'deep_link'          => true,
                'short_redirect_uri' => false,
                'language'           => 'UNKNOWN_LANGUAGE'
            ],
            'amount' => [
                'cur' => 'IDR',
                'num' => (int)$transaction->total.'00' # Added two zeros to adjust currency format
            ],
            'destination_account_id' => $accountDestId,
            'customer' => [
                'fname'  => $transaction->customer->name,
                'lname'  => '',
                'mname'  => '',
                'email'  => $transaction->customer->email,
                'mobile' => $transaction->customer->phone,
                'phone'  => $transaction->customer->phone
            ],
            'expiry_date_time' => $expiredTime,
            'reference_id'     => $transaction->transaction_hash,
            'unique_amount'    => 'NONE',
            'signature'        => 'sint ut dolor'
        ];

        $response                = $this->call($data, '/v1/checkout');
        $response->expiry_time   = $expiredTime;

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
        $data = $this->call('', '/v1/transaction?transaction_ids[]='.$transactionId, 'GET');

        if ( ! empty($data->transactions)) {
            return $data->transactions[0]->status;
        }

        return NULL;
    }

    public function call($data, $endpoint, $method = 'POST')
    {
        $curl = curl_init();

        $payload = array(
            CURLOPT_URL => $this->host.$endpoint,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/json',
                'Accept: application/json',
                'x-api-key: '.$this->apiKey
            ),
        );

        if ($method === 'POST') {
            $payload[CURLOPT_POSTFIELDS] = json_encode($data);
        }

        curl_setopt_array($curl, $payload);

        $response = curl_exec($curl);

        return json_decode($response);
    }
}
