<?php

// Create Transactions
Route::group(['middleware' => 'Lulapay\Transaction\Classes\AuthMiddleware'], function () {
    Route::post('api/v1/transaction/create', 'Lulapay\Transaction\Controllers\Transactions@create');
    Route::get('api/v1/transaction/status', 'Lulapay\Transaction\Controllers\Transactions@status');
});

Route::group(['prefix' => 'api/v1/transaction/notif/'], function() {
    Route::post('midtrans', 'Lulapay\Transaction\Controllers\Transactions@notifMidtrans');
    Route::post('brankas', 'Lulapay\Transaction\Controllers\Transactions@notifBrankas');
    Route::post('stripe', 'Lulapay\Transaction\Controllers\Transactions@notifStripe');
    Route::post('lulapay', 'Lulapay\Transaction\Controllers\Transactions@notifLulapay');
});

