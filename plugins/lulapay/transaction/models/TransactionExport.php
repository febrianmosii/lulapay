<?php namespace Lulapay\Transaction\Models;

use BackendAuth;
use Lulapay\Transaction\Models\Transaction;

class TransactionExport extends \Backend\Models\ExportModel
{
    public function exportData($columns, $sessionKey = null)
    {
        $user     = BackendAuth::getUser();
        $userRole = $user->role->code;

        if ($userRole === 'merchant') {
            $transactions = Transaction::whereIn("merchant_id", $user->merchants->pluck('id'))->get();;
        } else {
            $transactions = Transaction::all();
        }

        $transactions->each(function($transaction) use ($columns) {
            $transaction->addVisible($columns);
        });

        return $transactions->toArray();
    }
}
