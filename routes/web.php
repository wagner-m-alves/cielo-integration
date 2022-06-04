<?php

use App\Services\CieloService;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

Route::get('/test', function () {
    $orderIdentifier = '0c488395-d9e2-4a98-a599-252f8d528ba3';
    $client = [
        'name'          => 'John Test',
        'cpf'           => '00000000001',
        'postal_code'   => '22750012',
        'street'        => 'Av Marechal Camara',
        'number'        => '123',
        'district'      => 'Centro',
        'city'          => 'Rio de Janeiro',
        'state'         => 'RJ',
        'country'       => 'BRA'
    ];
    $method = 'billet';
    $amount = 15700;
    $creditCard = [
        'holder'    => 'Fulano de Tal',
        'number'    => '0000000000000001',
        'validity'  => '12/2018',
        'cvv'       => '123',
        'flag'      => 'visa'
    ];

    return app(CieloService::class)->generatePayment($orderIdentifier, $client, $method, $amount);
});
