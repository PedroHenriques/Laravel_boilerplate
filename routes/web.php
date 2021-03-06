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
})->name('landing');

Auth::routes();

Route::get('/activate', 'Auth\ActivationController@activate')->name('activation');
Route::get('/resend-activation', 'Auth\ActivationController@resend')->name('resendActivation');

Route::get('/home', 'HomeController@index')->name('home');
