<?php namespace Lulapay\Transaction\Components;

use Validator;
use Lulapay\Merchant\Models\Merchant;
use Cms\Classes\ComponentBase;

class Simulator extends ComponentBase
{
    public function componentDetails()
    {
        return [
            'name'        => 'Simulator Component',
            'description' => 'No description provided yet...'
        ];
    }

    public function defineProperties()
    {
        return [];
    }

    public function onRun()
    {
        $merchant = Merchant::find(1);
        
        $this->page['merchants'] = Merchant::select('id', 'name', 'code')->get();
    }

    public function onSubmit()
    {
        $post = \Input::post();

        // Validate the input data
        $rules = [
            'invoice_code' => 'required|string|max:255',
            'name'         => 'required|string|min:3|max:50',
            'email'        => 'required|email|max:110',
            'phone'        => 'required|string|min:8|max:13',
            'item_name'    => 'required|string|max:255',
            'price'        => 'required|numeric|min:10000'
        ];

        $message = [
            'price.min' => 'Transaksi minimal adalah Rp 10.000'
        ];
        
        $validator = Validator::make($post, $rules, $message);

        // If validation fails, display error messages
        if ($validator->fails()) {
            throw new \Exception($validator->errors()->first());
        }

        $errorMessage = "Sedang terjadi gangguan jaringan, coba lagi nanti";

        try {
            $response = $this->createTransactionWithAPI($post);

            if ($response['error']) {
                throw new \Exception($response['message']);
            } else {
                if ($redirectUrl = $response['redirect_url']) {
                    return \Redirect::to($redirectUrl);
                } else {
                    throw new \Exception($errorMessage);
                }
            }
        } catch (\Throwable $th) {
            throw new \Exception($errorMessage);
        }
        
        return \Redirect::to($this->page['url']);
    }

    public function createTransactionWithAPI($data) 
    {
        $payload = [
            'invoice_code'  => $data['invoice_code'],
            'name'          => $data['name'],
            'email'         => $data['email'],
            'phone'         => $data['phone'],
            'total_charged' => $data['price'],
            'items'         => [
                [
                    'item_name' => $data['item_name'],
                    'price'     => $data['price'],
                    'quantity'  => 1
                ]
            ]
        ];

        $merchant = Merchant::find($data['merchant_id']);

        $publicKey = $merchant->public_key;
        $serverKey = $merchant->server_key;

        try {
            $curl = curl_init();

            curl_setopt_array($curl, array(
                CURLOPT_URL => url('/api/v1/transaction/create'),
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_POSTFIELDS => json_encode($payload),
                CURLOPT_HTTPHEADER => array(
                    'Content-Type: application/json',
                    'Accept: application/json',
                    'Public-Key: '.$publicKey,
                    'Server-Key: '.$serverKey
                ),
            ));

            $response = curl_exec($curl);

            return json_decode($response, TRUE);
            
            curl_close($curl);  
        } catch (\Throwable $e) {
            throw new \Exception($e->getMessage());
        }
    }
}
