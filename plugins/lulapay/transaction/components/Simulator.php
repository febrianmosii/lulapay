<?php namespace Lulapay\Transaction\Components;

use Validator;
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

    public function onSubmit()
    {
        $post = \Input::post();

        // Validate the input data
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
        
        $validator = Validator::make($post, $rules);

        // If validation fails, display error messages
        if ($validator->fails()) {
            throw new \Exception($validator->errors()->first());
        }
        
        $response = $this->createTransactionWithAPI($post);

        if ($response['error']) {
            throw new \Exception($response['message']);
        } else {
            if ($redirectUrl = $response['redirect_url']) {
                return \Redirect::to($redirectUrl);
            } else {
                throw new \Exception("Sedang terjadi gangguan jaringan, coba lagi nanti.");
            }
        }
        
        return \Redirect::to($this->page['url']);
    }

    public function createTransactionWithAPI($data) 
    {
        $publicKey = env('SIMULATOR_PUBLIC_KEY');
        $serverKey = env('SIMULATOR_SERVER_KEY');

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
                CURLOPT_POSTFIELDS => json_encode($data),
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
