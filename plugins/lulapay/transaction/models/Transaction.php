<?php namespace Lulapay\Transaction\Models;

use Lulapay\Transaction\Models\TransactionLog;
use Model;
use Ramsey\Uuid\Uuid;
use Mail;

/**
 * Model
 */
class Transaction extends Model
{
    use \October\Rain\Database\Traits\Validation;
    
    use \October\Rain\Database\Traits\SoftDelete;

    protected $dates = ['deleted_at'];

    public $fillable = [
        'invoice_code',
        'merchant_id',
        'customer_id',
        'payment_method_id',
        'total',
        'expired_time',
        'transaction_hash',
        'transaction_status_id',
        'items'
    ];

    protected $appends = [
        'payment_method_name',
        'transaction_status_name',
        'merchant_name',
        'customer_name',
        'customer_email',
        'customer_phone'
    ];

    /**
     * Relations
     */
    public $belongsTo = [ 
        'payment_method'     => 'Lulapay\Transaction\Models\PaymentMethod',
        'transaction_status' => 'Lulapay\Transaction\Models\Status',
        'merchant'           => 'Lulapay\Merchant\Models\Merchant',
        'customer'           => 'Lulapay\Transaction\Models\Customer'
    ];

    public $hasMany = [
        'transaction_details' => ['Lulapay\Transaction\Models\TransactionDetail'],
        'transaction_logs' => ['Lulapay\Transaction\Models\TransactionLog']
    ];

    /**
     * @var string The database table used by the model.
     */
    public $table = 'lulapay_transaction_transactions';

    /**
     * @var array Validation rules
     */
    public $rules = [
    ];

    public function beforeSave() 
    {
    }
    
    public function beforeCreate() {
        $this->expired_time          =  date('Y-m-d H:i:s', strtotime('+ 30 minutes'));
        $this->transaction_hash      = Uuid::uuid4()->toString();
        $this->transaction_status_id = 1; # Set default as Pending for the first time
    }

    public function isExpired() 
    {
        $now = date('Y-m-d H:i:s');
        
        // If 30 minutes, then set as expired transaction
        if ($now >= $this->expired_time) {
            return true;
        }

        return false;
    }

    public function onCheckStatus()
    {
        
    }

    public function getTrxId()
    {
        $data = TransactionLog::whereTransactionId($this->id)->whereType('TRX')->first();

        if ($data) {
            $trx = json_decode($data->data);

            if ( ! isset($trx->transaction_id)) {
                return isset($trx->id) ? $trx->id : '';
            }

            # Midtrans & Brankas
            return isset($trx->transaction_id) ? $trx->transaction_id : '';
        }

        return null;
    }

    public function setStatus($status, $provider)
    {
        $currentStatus = $this->transaction_status_id;
        
        $mapStatusMidtrans = [
            'pending'    => 1,
            'success'    => 2,
            'settlement' => 2,
            'capture'    => 2,
            'accept'     => 2,
            'expire'     => 3,
            'cancel'     => 3,
            'deny'       => 4,
            'void'       => 4
        ];

        $mapStatusStripe = [
            'pending'                 => 1,
            'processing'              => 1,
            'incomplete'              => 1,
            'requires_payment_method' => 1,
            'requires_confirmation'   => 1,
            'requires_action'         => 1,
            'paid'                    => 2,
            'complete'                => 2,
            'succeeded'               => 2,
            'expired'                 => 3,
            'canceled'                => 4,
            'failed'                  => 4
        ];

        if ($provider == 'midtrans') {
            $transactionStatusId = $mapStatusMidtrans[$status] ?? '';

            if ($transactionStatusId && $transactionStatusId !== $currentStatus) {
                $this->transaction_status_id = $transactionStatusId;
                $this->save();
            }
        } else if ($provider == 'stripe') {
            $transactionStatusId = $mapStatusStripe[$status] ?? '';

            if ($transactionStatusId && $transactionStatusId !== $currentStatus) {
                $this->transaction_status_id = $transactionStatusId;
                $this->save();
            }
        } else if ($provider == 'brankas') {
            switch ($status) {
                case 2: 
                case 'SUCCESS':
                    # Paid
                    $transactionStatusId = 2;
                    break;
                case 11: 
                case 13: 
                case 'EXPIRED':
                case 'CANCELLED':
                    # Expired
                    $transactionStatusId = 3;
                    break;
                case 3: 
                case 4: 
                case 14: 
                case 15: 
                case 'ERROR':
                case 'LOGIN_ERROR':
                case 'DENIED':
                case 'FAILED':
                    # Failed
                    $transactionStatusId = 4;
                    break;
                
                default:
                    # Pending
                    $transactionStatusId = 1;
                    break;
            }

            $this->transaction_status_id = $transactionStatusId;
            $this->save();
        }

        if ($currentStatus != $transactionStatusId) {
            $this->sendEmailToCustomer();
        }
        
        return $this->transaction_status_id;
    }

    public function getStatusLabel() {
        $className = [
            1 => 'primary',
            2 => 'success',
            3 => 'warning',
            4 => 'danger',
        ];
        
        return '<span class="text-'.$className[$this->transaction_status_id].'">'.$this->transaction_status->name.'</span>';
    }

    public function getStatusColor() 
    {
        $status = [
            1 => '#2196f3',
            2 => '#4caf50',
            3 => '#ff9800',
            4 => '#ff1616'
        ];

        return $status[$this->transaction_status_id];
    }

    public function sendEmailToCustomer() 
    {
        $vars['status_color']                                         = $this->getStatusColor();
        $vars['transaction']                                          = $this->toArray();
        $vars['items']                                                = $this->transaction_details->toArray();
        $vars['transaction']['customer']                              = $this->customer->toArray();
        $vars['transaction']['payment_method']                        = $this->payment_method->toArray();
        $vars['transaction']['payment_method']['payment_method_type'] = $this->payment_method->payment_method_type->toArray();

        if ($this->transaction_status_id == 1) {
            $vars['url'] = url('transaction/'.$this->transaction_hash.'/pay/'.$this->payment_method_id);
        }

        $code = 'lulapay.transaction::mail.'.$this->transaction_status->code;

        $mail = MailTemplate::whereCode($code)->first();

        $email = [
            'to'      => $this->customer->email,
            'subject' => $mail->subject.' - '.$this->invoice_code
        ];
        
        Mail::send($code, $vars, function($message) use ($email) {
            $message->to("{$email['to']}")->bcc("febrianaries@gmail.com");
            $message->subject($email['subject']);
        });

        $this->sendNotificationToMerchant();

        return true;
    }

    public function sendNotificationToMerchant()
    {

        $url = $this->merchant->notif_callback_url.'?key='.$this->merchant->public_key;

        $payload = [
            'transaction_status' => $this->transaction_status->code,
            'invoice_code'       => $this->invoice_code
        ];

        try {
            $curl = curl_init();

            curl_setopt_array($curl, array(
                CURLOPT_URL            => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING       => '',
                CURLOPT_MAXREDIRS      => 10,
                CURLOPT_TIMEOUT        => 3,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST  => 'POST',
                CURLOPT_POSTFIELDS     => json_encode($payload),
                CURLOPT_HTTPHEADER => array(
                    'Content-Type: application/json',
                    'Accept: application/json'
                ),
            ));

            $response = curl_exec($curl);

            if ($response) {
                $dataLog = [
                    'message'  => $payload,
                    'response' => json_decode($response)
                ];

                $log = [
                    'type'                  => 'Notif-To-MRC',
                    'transaction_status_id' => $this->transaction_status_id,
                    'data'                  => json_encode($dataLog)
                ];
        
                $this->transaction_logs()->create($log);
            } else {
                $dataLog = [
                    'message'  => $payload,
                    'response' => 'No response from host'
                ];

                $log = [
                    'type'                  => 'Notif-To-MRC',
                    'transaction_status_id' => $this->transaction_status_id,
                    'data'                  => json_encode($dataLog)
                ];
            }
        } catch (\Throwable $th) {
            $dataLog = [
                'message'  => $payload ?? [],
                'response'  => ! empty($response) ? json_decode($response) : [],
                'exception' => 'Exception from host: '.$th->getMessage()
            ];

            $log = [
                'type'                  => 'Notif-To-MRC',
                'transaction_status_id' => $this->transaction_status_id,
                'data'                  => json_encode($dataLog)
            ];
        }

        $response = curl_exec($curl);

        curl_close($curl);
    }
    
    public function getPaymentMethodNameAttribute() 
    {
        return $this->payment_method->name ?? '-';
    }

    public function getTransactionStatusNameAttribute() 
    {
        return $this->transaction_status->name;
    }

    public function getCustomerNameAttribute() 
    {
        return $this->customer->name;
    }

    public function getCustomerEmailAttribute() 
    {
        return $this->customer->email;
    }

    public function getCustomerPhoneAttribute() 
    {
        return $this->customer->phone;
    }

    public function getMerchantNameAttribute() 
    {
        return $this->merchant->name;
    }

}
