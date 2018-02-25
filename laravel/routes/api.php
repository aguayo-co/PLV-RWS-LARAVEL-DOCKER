<?php

use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::post('users', 'Auth\RegisterController@register')->name('register');
Route::post('users/login', 'Auth\LoginController@login')->name('login');

Route::get('users/password/recovery/{email}', 'Auth\ForgotPasswordController@sendResetLinkEmail')
  ->name('password.recovery.email');
Route::post('users/password/recovery/{email}', 'Auth\ForgotPasswordController@validateResetToken')
  ->name('password.recovery.token');
Route::post('user/password/reset', 'Auth\ResetPasswordController@reset')->name('password.reset');


Route::middleware('auth:api')->group(function () {
    Route::get('users', 'Auth\UserController@index')->name('user');
    Route::get('users/{user}', 'Auth\UserController@user')->name('user');
});
