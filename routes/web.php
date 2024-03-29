<?php

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

Route::post('/payments/pay', 'PaymentController@pay')->name('pay');
Route::get('/payments/aprobada', 'PaymentController@aprobada')->name('aprobada');
Route::get('/payments/cancelado', 'PaymentController@cancelado')->name('cancelado');

Auth::routes();

Route::get('/home', 'HomeController@index')->name('home');
