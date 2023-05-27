<?php namespace Lulapay\Transaction\Components;

use Cms\Classes\ComponentBase;
use Lulapay\Transaction\Models\Transaction as TransactionModel;
use Lulapay\Transaction\Models\PaymentMethod;

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
        dd('q');
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
}
