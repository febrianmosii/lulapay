<?php namespace Lulapay\Transaction\Components;

use Redirect;
use DB;
use Flash;
use Cms\Classes\ComponentBase;
use Lulapay\PaymentGateway\Models\Account;
use Lulapay\Transaction\Classes\StripeClient;
use Lulapay\Transaction\Classes\BrankasClient;
use Lulapay\Transaction\Classes\MidtransClient;
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

            if ( ! $this->payment_method && $currentPageUrl != 'Thanks Page') {
                $this->setStatusCode(404);
    
                return Redirect::to('/404');
            }
        }
        
        if ( ! $this->transaction && $currentPageUrl != 'Thanks Page') {
            $this->setStatusCode(404);
    
            return Redirect::to('/404');
        }

        if ($currentPageUrl === 'Cart') {
            return $this->cartPage();
        } else if ($currentPageUrl === 'Checkout') {
            return $this->checkOutPage();
        } else if ($currentPageUrl === 'Pay') {
            return $this->payPage();
        } else if ($currentPageUrl === 'Thanks Page') {
            return $this->thanksPage();
        }
    }

    public function thanksPage()
    {   
        if ( ! empty($_GET['transaction_id'])) {
            $transaction = TransactionLog::where('data', 'LIKE', '%'.$_GET['transaction_id'].'%')->first()->transaction;

            $provider = $transaction->payment_method->payment_gateway_provider->code;

            if ($transaction) {
                $currentPage = $this->controller->getPage()->meta_title;

                $backUrl = $this->pageUrl('checkout/payment', ['transactionHash' => $transaction->transaction_hash, 'methodId' => $transaction->payment_method_id]);

                $this->page['pay_url'] = $backUrl;
                
                if ($transaction->transaction_status_id == 1) {
                    return Redirect::to($backUrl);
                }
                
                if ($currentPage == 'Pembayaran Anda Berhasil' && $transaction->transaction_status_id != 2) {
                    return Redirect::to(url('/transaction/failed').'?transaction-hash='.$transaction->transaction_hash);
                } else if ($currentPage == 'Pembayaran Anda Gagal' && ! in_array($transaction->transaction_status_id, [3,4])) {
                    return Redirect::to(url('/transaction/success').'?transaction-hash='.$transaction->transaction_hash);
                }                
            }
        } else if (! empty($_GET['transaction-hash'])) {
            $currentPage = $this->controller->getPage()->meta_title;
            $transaction = TransactionModel::whereTransactionHash($_GET['transaction-hash'])->first();
            
            if ($transaction) {
                $backUrl = $this->pageUrl('checkout/payment', ['transactionHash' => $transaction->transaction_hash, 'methodId' => $transaction->payment_method_id]);

                $this->page['pay_url'] = $backUrl;
                
                if ($transaction->transaction_status_id == 1) {
                    return Redirect::to($backUrl);
                }

                if ($currentPage == 'Pembayaran Anda Berhasil' && $transaction->transaction_status_id != 2) {
                    return Redirect::to(url('/transaction/failed').'?transaction-hash='.$transaction->transaction_hash);
                } else if ($currentPage == 'Pembayaran Anda Gagal' && ! in_array($transaction->transaction_status_id, [3,4])) {
                    return Redirect::to(url('/transaction/success').'?transaction-hash='.$transaction->transaction_hash);
                }                

            }
        } else {
            return Redirect::to('/404');
        }   
    }

    public function payPage() 
    {
        try {
            DB::beginTransaction();
            
            $provider = $this->payment_method->payment_gateway_provider->code;

            if ($provider === 'midtrans') {
                $client   = new MidtransClient();
            } else if ($provider === 'brankas') {
                $client   = new BrankasClient();
            } else if ($provider === 'stripe') {
                $client   = new StripeClient();
            }
            
            $response = $client->charge($this->transaction, $this->payment_method);
                        
            $this->page['sandbox']               = env('ENV_PRODUCTION') === false;
            $this->page['payment_method']        = $this->transaction->payment_method;
            $this->page['transaction_status_id'] = $this->transaction->transaction_status_id;
            $this->page['status']                = $this->transaction->getStatusLabel();
            $this->page['page_state']            = 'midtrans';

            $expiryTime      = $response->expiry_time ?? '';
            $transactionTime = $response->response->transaction_time ?? '';

            // Set default expiry time = 24 hours
            if ( ! $expiryTime) {
                $expiryTime = date('d F Y H:i:s', strtotime($transactionTime.' + 1 day'));
            }
            
            // If credit card
            if ( ! empty($response->redirect_url)) {
                $this->page['redirect_url'] = $response->redirect_url;
            } else if ( ! empty($response->redirect_uri)) {
                $this->page['redirect_url'] = $response->redirect_uri;
                $this->page['page_state']   = 'brankas';
                $this->page['expiry_time']  = date('d F Y H:i:s', strtotime($expiryTime));
            } else if ( ! empty($response->url)) {
                $this->page['redirect_url'] = $response->url;
                $this->page['page_state']   = 'stripe';
                $this->page['expiry_time']  = date('d F Y H:i:s', strtotime('@'.$response->expires_at));
            } else {
                $vaNumber = '';
    
                if (isset($response->permata_va_number)) {
                    $vaNumber = $response->permata_va_number;
                } elseif (isset($response->va_numbers)) { // bca, bri, bni
                    $vaNumber = $response->va_numbers[0]->va_number;
                } elseif (isset($response->bill_key)) {
                    $vaNumber = $response->bill_key;
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
        $merchantMethods = $this->transaction->merchant->payment_methods;

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


        return TransactionModel::with(['merchant', 'merchant.payment_methods'=> function($q){
            $q->whereIsActive(TRUE);
        }])->whereTransactionHash($transactionHash)->first();
    }

    public function onCheckStatus()
    {
        $transactionHash = \Request::segment(2);

        if ($transactionHash) {
            $transaction = $this->getTrxByTransactionHash();
            
            if ($transaction) {
                $provider = $transaction->payment_method->payment_gateway_provider->code;
                
                $id = $transaction->getTrxId();

                if ($provider === 'midtrans' && $id) {
                    $client = new MidtransClient();
                    $transactionStatus = $client->checkStatus($id);
    
                    if ( ! empty($transactionStatus->transaction_status)) {
                        $status = $transaction->setStatus($transactionStatus->transaction_status, $provider);

                        $log = [
                            'type'                  => 'User-RQ',
                            'transaction_status_id' => $status,
                            'data'                  => json_encode($transactionStatus)
                        ];
                
                        $transaction->transaction_logs()->create($log);
                    }
                } else if ($provider === 'brankas' && $id) {
                    $client = new BrankasClient();
                    $transactionStatus = $client->checkStatus($id);
    
                    if ( ! empty($transactionStatus)) {
                        $status = $transaction->setStatus($transactionStatus, $provider);

                        $log = [
                            'type'                  => 'User-RQ',
                            'transaction_status_id' => $status,
                            'data'                  => json_encode($transactionStatus)
                        ];
                
                        $transaction->transaction_logs()->create($log);
                    }
                } else if ($provider === 'stripe' && $id) {
                    $client = new StripeClient();
                    $transactionStatus = $client->checkStatus($id);
    
                    if ( ! empty($transactionStatus)) {
                        $status = $transaction->setStatus($transactionStatus, $provider);

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
        
        $this->page['transaction_status_id'] = $transaction->transaction_status_id;
        $this->page['payment_method']        = $transaction->payment_method;
        $this->page['status']                = $transaction->getStatusLabel();
        $this->page['sandbox']               = env('ENV_PRODUCTION') === false;

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
