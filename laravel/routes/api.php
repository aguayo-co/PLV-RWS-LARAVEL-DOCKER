<?php

use Illuminate\Http\Request;

/**
 * Per Str::slug.
 */
const SLUG_REGEX = '[-\pL\pN\s]+';
const ID_REGEX = '[0-9]+';

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

    Route::get('users/{user}', 'Auth\UserController@show')->name('user.get')->where('user', ID_REGEX);

    Route::get('menus/{menu}', 'MenuController@show')->name('menu.get')->where('banner', SLUG_REGEX);
    Route::get('banners/{banner}', 'BannerController@show')->name('banner.get')->where('banner', SLUG_REGEX);
    Route::get('products/{product}', 'ProductController@show')->name('product.get')->where('product', ID_REGEX);
    Route::get('products/category/{category}', 'ProductController@withCategory')
        ->name('products.category.get')->where('category', SLUG_REGEX);
    Route::get('products/campaign/{campaign}', 'ProductController@withCampaign')
        ->name('products.campaign.get')->where('campaign', SLUG_REGEX);

    Route::get('colors', 'ColorController@index')->name('colors');
    Route::get('brands', 'BrandController@index')->name('brands');
    Route::get('conditions', 'ConditionController@index')->name('conditions');
    Route::get('statuses', 'StatusController@index')->name('statuses');
    Route::get('categories', 'CategoryController@index')->name('categories');
    Route::get('campaigns', 'CampaignController@index')->name('campaigns');

    Route::middleware('auth:api')->group(function () {
        Route::patch('users/{user}', 'Auth\UserController@update')->name('user.update')->where('user', ID_REGEX);

        Route::get('users/{user}/addresses', 'AddressController@show')
            ->name('user.addresses.get')->where('user', ID_REGEX);
        Route::post('users/{user}/addresses', 'AddressController@store')
            ->name('user.address.create')->where('user', ID_REGEX);

        Route::post('products', 'ProductController@store')->name('product.create');
        Route::patch('products/{product}', 'ProductController@update')
            ->name('product.update')->where('product', ID_REGEX);

        Route::middleware('role:admin')->group(function () {
            Route::post('menus', 'MenuController@store')->name('menu.create');
            Route::post('menus/item', 'MenuItemController@store')->name('menu_item.create');

            Route::post('shipping', 'ShippingMethodController@store')->name('shipping_method.create');

            Route::post('banners', 'BannerController@store')->name('banner.create');
            Route::post('brands', 'BrandController@store')->name('brand.create');
            Route::post('campaign', 'CampaignController@store')->name('campaign.create');
            Route::post('categories', 'CategoryController@store')->name('category.create');
            Route::post('colors', 'ColorController@store')->name('color.create');
            Route::post('conditions', 'ConditionController@store')->name('condition.create');
            Route::post('statuses', 'StatusController@store')->name('status.create');

            Route::patch('banners/{banner}', 'BannerController@update')->name('banner.update');
            Route::patch('brands/{brand}', 'BrandController@update')->name('brand.update');
            Route::patch('campaign/{campaign}', 'CampaignController@update')->name('campaign.update');
            Route::patch('categories/{category}', 'CategoryController@update')->name('category.update');
            Route::patch('colors/{color}', 'ColorController@update')->name('color.update');
            Route::patch('conditions/{condition}', 'ConditionController@update')->name('condition.update');
            Route::patch('statuses/{status}', 'StatusController@update')->name('status.update');
        });
    });
});
