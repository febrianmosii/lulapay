<?php namespace Lulapay\Transaction\Controllers;

use Backend\Classes\Controller;
use Cms\Classes\Controller as CMS_Controller;
use BackendMenu;
use DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Validator;
use Lulapay\Transaction\Models\Customer;
use Lulapay\Transaction\Models\Transaction;
use System\Classes\PluginManager;

class Transactions extends Controller
{
    public $implement = ['Backend\Behaviors\ListController', 'Backend\Behaviors\FormController'];
    
    public $listConfig = 'config_list.yaml';
    public $formConfig = 'config_form.yaml';

    private $transactionData = [];

    public function __construct()
    {
        parent::__construct();

        BackendMenu::setContext('Lulapay.Transaction', 'transaction', 'transactions');
    }

    public function create(Request $request)
    {
        // Retrieve the request data
        $this->transactionData = Input::all();

        // Validate the request data
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

        $validator = Validator::make($this->transactionData, $rules);

        if ($validator->fails()) {
            return Response::json([
                'error'   => true,
                'message' => 'Validation error',
                'errors'  => $validator->errors(),
            ], 400);
        }

        // Validate total
        if ( ! $this->isTotalValid()) {
            return Response::json([
                'error'   => true,
                'message' => 'Total charged is not match with the items total price'
            ], 400);
        }
        
        // Create TRX
        $insert = [
            'invoice_code' => $this->transactionData['invoice_code'],
            'merchant_id'  => $request->merchant->id,
            'customer_id'  => $this->getCustomerId(),
            'total'        => $this->transactionData['total_charged'],
        ];

        try {
            DB::beginTransaction();

            $transaction = new Transaction();
            $transaction->fill($insert);
            $transaction->save();

            $transaction->transaction_details()->createMany($this->transactionData['items']);
            
            $cmsController = new CMS_Controller();
            $checkoutUrl   = $cmsController->pageUrl('cart/index');

            return Response::json([
                'error'        => false,
                'message'      => 'Transaction Created Successfully',
                'redirect_url' => $checkoutUrl.'?transaction-id='.$transaction->transaction_hash
            ], 201);
        } catch (\Throwable $th) {
            dd($th);
            DB::rollback();

            return Response::json([
                'error'   => true,
                'message' => 'Error occurred - Please contact the administrator for details',
            ], 500);
        }
    }

    private function getCustomerId() 
    {
        $customer = Customer::whereEmail($this->transactionData['email'])->first();

        if ($customer) {
            return $customer->id;
        }

        $customer        = new Customer();
        $customer->name  = $this->transactionData['name'];
        $customer->phone = $this->transactionData['phone'];
        $customer->email = $this->transactionData['email'];
        $customer->save();

        return $customer->id;
    }

    private function isTotalValid() 
    {
        $totalPrice   = 0;
        $totalCharged = $this->transactionData['total_charged'];


        foreach ($this->transactionData['items'] as $value) {
            $totalPrice += $value['price'] * $value['quantity'];
        }


        return $totalCharged === $totalPrice;
    }
}
