<?php

// Create Transactions
Route::group(['middleware' => 'Lulapay\Transaction\Classes\AuthMiddleware'], function () {
    Route::post('api/v1/transaction/create', 'Lulapay\Transaction\Controllers\Transactions@create');
});

Route::post('api/v1/transaction/notif/midtrans', 'Lulapay\Transaction\Controllers\Transactions@notifMidtrans');
Route::post('api/v1/transaction/notif/brankas', 'Lulapay\Transaction\Controllers\Transactions@notifBrankas');
