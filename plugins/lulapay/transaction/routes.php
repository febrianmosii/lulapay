<?php

// Create Transactions
Route::group(['middleware' => 'Lulapay\Transaction\Classes\AuthMiddleware'], function () {
    Route::post('api/v1/transaction/create', 'Lulapay\Transaction\Controllers\Transactions@create');
    Route::get('api/v1/transaction/status', 'Lulapay\Transaction\Controllers\Transactions@status');
});

Route::post('api/v1/transaction/notif/midtrans', 'Lulapay\Transaction\Controllers\Transactions@notifMidtrans');
Route::post('api/v1/transaction/notif/brankas', 'Lulapay\Transaction\Controllers\Transactions@notifBrankas');
Route::post('api/v1/transaction/notif/stripe', 'Lulapay\Transaction\Controllers\Transactions@notifStripe');
