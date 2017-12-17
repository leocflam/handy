<?php

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It is a breeze. Simply tell Lumen the URIs it should respond to
| and give it the Closure to call when that URI is requested.
|
*/

$router->get('/', function () use ($router) {
    return $router->app->version();
});

$router->group(['prefix' => 'v1', 'namespace' => 'Bankings'], function ($router) {
    $router->post('bank-accounts', 'BankAccountsController@store');
    $router->get('bank-accounts/{uuid}', 'BankAccountsController@show');
    $router->delete('bank-accounts/{uuid}', 'BankAccountsController@destroy');

    $router->post('bank-accounts/{uuid}/transactions/withdraw', 'BankAccountsTransactionsController@withdraw');
    $router->post('bank-accounts/{uuid}/transactions/deposit', 'BankAccountsTransactionsController@deposit');
    $router->post('bank-accounts/{uuid}/transactions/transfer', 'BankAccountsTransactionsController@transfer');
    $router->post('bank-accounts/{uuid}/transactions', 'BankAccountsTransactionsController@store');
});
