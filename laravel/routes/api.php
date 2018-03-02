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

Route::name('api.')->group(function () {
    Route::post('users', 'Auth\RegisterController@store')->name('register');
    Route::post('users/login', 'Auth\LoginController@login')->name('login');

    Route::get('users/password/recovery/{email}', 'Auth\ForgotPasswordController@sendResetLinkEmail')
        ->name('password.recovery.email');
    Route::post('users/password/recovery/{email}', 'Auth\ForgotPasswordController@validateResetToken')
        ->name('password.recovery.token');

    Route::get('menus/{menu}', 'MenuController@show')->name('menu.get');

    Route::get('banners/{banner}', 'BannerController@show')->name('banner.get')->where('banner', '[0-9]+');

    Route::get('users/{user}', 'Auth\UserController@show')->name('user.get')->where('user', '[0-9]+');

    Route::middleware('auth:api')->group(function () {
        Route::patch('users/{user}', 'Auth\UserController@update')->name('user.update')->where('user', '[0-9]+');

        Route::get('users/{user}/addresses', 'AddressController@show')->name('user.addresses.get')->where('user', '[0-9]+');
        Route::post('users/{user}/addresses', 'AddressController@store')->name('user.address.create');

        Route::post('menus', 'MenuController@store')->name('menu.create');
        Route::post('menus/item', 'MenuItemController@store')->name('menu_item.create');

        Route::post('shipping', 'ShippingMethodController@store')->name('shipping_method.create');

        Route::post('banners', 'BannerController@store')->name('banner.create');
    });
});
