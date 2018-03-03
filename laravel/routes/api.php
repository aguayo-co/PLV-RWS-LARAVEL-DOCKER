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

    Route::middleware('guest')->group(function () {
        Route::post('users', 'Auth\RegisterController@store')->name('register');
        Route::post('users/login', 'Auth\LoginController@login')->name('login');

        Route::get('users/password/recovery/{email}', 'Auth\ForgotPasswordController@sendResetLinkEmail')
            ->name('password.recovery.email');
        Route::post('users/password/recovery/{email}', 'Auth\ForgotPasswordController@validateResetToken')
            ->name('password.recovery.token');
    });

    Route::get('users/{user}', 'Auth\UserController@show')->name('user.get')->where('user', '[0-9]+');

    Route::get('menus/{menu}', 'MenuController@show')->name('menu.get');
    Route::get('banners/{banner}', 'BannerController@show')->name('banner.get')->where('banner', '[0-9]+');
    Route::get('products/{product}', 'ProductController@show')->name('product.get')->where('product', '[0-9]+');

    Route::get('colors', 'ColorController@index')->name('colors');
    Route::get('brands', 'BrandController@index')->name('brands');
    Route::get('conditions', 'ConditionController@index')->name('conditions');
    Route::get('statuses', 'StatusController@index')->name('statuses');
    Route::get('categories', 'CategoryController@index')->name('categories');

    Route::middleware('auth:api')->group(function () {
        Route::patch('users/{user}', 'Auth\UserController@update')->name('user.update')->where('user', '[0-9]+');

        Route::get('users/{user}/addresses', 'AddressController@show')
            ->name('user.addresses.get')->where('user', '[0-9]+');
        Route::post('users/{user}/addresses', 'AddressController@store')->name('user.address.create');

        Route::post('products', 'ProductController@store')->name('product.create');
        Route::patch('products/{product}', 'ProductController@update')->name('product.update');

        Route::middleware('role:admin')->group(function () {
            Route::post('menus', 'MenuController@store')->name('menu.create');
            Route::post('menus/item', 'MenuItemController@store')->name('menu_item.create');

            Route::post('shipping', 'ShippingMethodController@store')->name('shipping_method.create');
            Route::post('banners', 'BannerController@store')->name('banner.create');

            Route::post('colors', 'ColorController@store')->name('color.create');
            Route::post('brands', 'BrandController@store')->name('brand.create');
            Route::post('conditions', 'ConditionController@store')->name('condition.create');
            Route::post('statuses', 'StatusController@store')->name('status.create');
            Route::post('categories', 'CategoryController@store')->name('category.create');
        });
    });
});
