<?php

// Create Transactions
Route::group(['middleware' => 'Lulapay\Transaction\Classes\AuthMiddleware'], function () {
    Route::post('api/v1/transaction/create', 'Lulapay\Transaction\Controllers\Transactions@create');
});

