<?php

use Illuminate\Http\Request;

/**
 * Api routes
 *
 * Global auth middleware is managed here, but be sure to check
 * permissions and other middleware added on the controllers directly.
 */

include_once 'helpers.php';

Route::name('api.')->group(function () {

    # User account management and password reset..
    Route::middleware('guest')->group(function () {
        Route::post('users', 'Auth\UserController@store')->name('register');
        Route::post('users/login', 'Auth\LoginController@login')->name('login');

        Route::get('users/password/recovery/{email}', 'Auth\ForgotPasswordController@sendResetLinkEmail')
            ->name('password.recovery.email');
        Route::post('users/password/recovery/{email}', 'Auth\ForgotPasswordController@validateResetToken')
            ->name('password.recovery.token');
        Route::post('users/password/reset/{email}', 'Auth\ResetPasswordController@reset')
            ->name('password.reset');
    });

    Route::get('users/{user}', 'Auth\UserController@show')->name('user.get')->where('user', ID_REGEX);
    Route::get('users/group/{group}', 'GroupController@show')->name('users.group.get')->where('group', SLUG_REGEX);


    # Standard CRUD routes for admin managed elements.
    create_crud_routes('Menu', SLUG_REGEX);
    create_crud_routes('MenuItem', ID_REGEX);

    create_crud_routes('ShippingMethod', SLUG_REGEX);

    create_crud_routes('Banner', SLUG_REGEX);
    create_crud_routes('Brand', SLUG_REGEX);
    create_crud_routes('Campaign', SLUG_REGEX);
    create_crud_routes('Category', SLUG_REGEX);
    create_crud_routes('Color', SLUG_REGEX);
    create_crud_routes('Condition', SLUG_REGEX);
    create_crud_routes('Group', SLUG_REGEX);
    create_crud_routes('Slider', SLUG_REGEX);
    create_crud_routes('Status', SLUG_REGEX);

    create_private_crud_routes('Order', ID_REGEX);
    create_private_crud_routes('Purchase', ID_REGEX);

    # Public product routes
    Route::get('products', 'ProductController@index')->name('products');
    Route::get('products/{product}/{slug?}', 'ProductController@show')
        ->name('product.get')->where(['product' => ID_REGEX, 'slug' => SLUG_REGEX]);
    Route::get('products/category/{category}', 'CategoryController@show')
        ->name('products.category.get')->where('category', SLUG_REGEX);
    Route::get('products/campaign/{campaign}', 'CampaignController@show')
        ->name('products.campaign.get')->where('campaign', SLUG_REGEX);

    # Auth routes.
    # Only authenticated requests here.
    Route::middleware('auth:api')->group(function () {
        # Routes for user account and profile administration.
        Route::patch('users/{user}', 'Auth\UserController@update')->name('user.update')->where('user', ID_REGEX);
        Route::get('users/{user}/addresses', 'AddressController@show')
            ->name('user.addresses.get')->where('user', ID_REGEX);
        Route::post('users/{user}/addresses', 'AddressController@store')
            ->name('user.address.create')->where('user', ID_REGEX);

        # Routes for Product administration.
        Route::post('products', 'ProductController@store')->name('product.create');
        Route::patch('products/{product}/{slug?}', 'ProductController@update')
            ->name('product.update')->where(['product' => ID_REGEX, 'slug' => SLUG_REGEX]);

        Route::middleware('role:admin')->group(function () {
            Route::delete('products/{product}/{slug?}', 'ProductController@delete')
                ->name('product.delete')->where(['product' => ID_REGEX, 'slug' => SLUG_REGEX]);
        });

        # Routes for shopping cart and payments.
        Route::get('/shopping_cart', 'OrderController@getCart')->name('shopping_cart');
        Route::patch('/shopping_cart/products', 'OrderController@addProducts')->name('shopping_cart.products');
        Route::delete('/shopping_cart/products', 'OrderController@removeProducts');
        Route::get('/shopping_cart/payment', 'OrderController@createPayment')->name('shopping_cart.payment');
    });
});
