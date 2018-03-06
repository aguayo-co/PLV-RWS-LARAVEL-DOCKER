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

include_once 'helpers.php';

Route::name('api.')->group(function () {

    Route::middleware('guest')->group(function () {
        Route::post('users', 'Auth\RegisterController@store')->name('register');
        Route::post('users/login', 'Auth\LoginController@login')->name('login');

        Route::get('users/password/recovery/{email}', 'Auth\ForgotPasswordController@sendResetLinkEmail')
            ->name('password.recovery.email');
        Route::post('users/password/recovery/{email}', 'Auth\ForgotPasswordController@validateResetToken')
            ->name('password.recovery.token');
        Route::post('users/password/reset/{email}', 'Auth\ResetPasswordController@reset')
            ->name('password.reset');
    });

    Route::get('users/{user}', 'Auth\UserController@show')->name('user.get')->where('user', ID_REGEX);

    create_admin_routes('Menu', SLUG_REGEX);
    create_admin_routes('MenuItem', ID_REGEX);

    create_admin_routes('ShippingMethod', SLUG_REGEX);

    create_admin_routes('Banner', SLUG_REGEX);
    create_admin_routes('Brand', SLUG_REGEX);
    create_admin_routes('Campaign', SLUG_REGEX);
    create_admin_routes('Category', SLUG_REGEX);
    create_admin_routes('Color', SLUG_REGEX);
    create_admin_routes('Condition', SLUG_REGEX);
    create_admin_routes('Slider', SLUG_REGEX);
    create_admin_routes('Status', SLUG_REGEX);

    Route::get('products', 'ProductController@index')->name('products');
    Route::get('products/{product}/{slug?}', 'ProductController@show')
        ->name('product.get')->where(['product' => ID_REGEX, 'slug' => SLUG_REGEX]);
    Route::get('products/category/{category}', 'ProductController@withCategory')
        ->name('products.category.get')->where('category', SLUG_REGEX);
    Route::get('products/campaign/{campaign}', 'ProductController@withCampaign')
        ->name('products.campaign.get')->where('campaign', SLUG_REGEX);

    Route::middleware('auth:api')->group(function () {
        Route::patch('users/{user}', 'Auth\UserController@update')->name('user.update')->where('user', ID_REGEX);

        Route::get('users/{user}/addresses', 'AddressController@show')
            ->name('user.addresses.get')->where('user', ID_REGEX);
        Route::post('users/{user}/addresses', 'AddressController@store')
            ->name('user.address.create')->where('user', ID_REGEX);

        Route::post('products', 'ProductController@store')->name('product.create');
        Route::patch('products/{product}/{slug?}', 'ProductController@update')
            ->name('product.update')->where(['product' => ID_REGEX, 'slug' => SLUG_REGEX]);

        Route::middleware('role:admin')->group(function () {
            Route::delete('products/{product}/{slug?}', 'ProductController@delete')
                ->name('product.delete')->where(['product' => ID_REGEX, 'slug' => SLUG_REGEX]);
        });
    });
});
