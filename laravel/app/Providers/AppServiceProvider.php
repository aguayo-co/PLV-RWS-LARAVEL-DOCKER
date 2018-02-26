<?php

namespace App\Providers;

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use Illuminate\Auth\Notifications\ResetPassword as ResetPasswordNotification;
use Illuminate\Notifications\Messages\MailMessage;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        # MySQL < 5.7 do not allow longer strings.
        # https://laravel.com/docs/5.6/migrations#creating-indexes
        Schema::defaultStringLength(191);

        # Change the default rendering method for ResetPassword.
        ResetPasswordNotification::$toMailCallback = function ($notifiable, $token) {
            return (new MailMessage)->view(
                'email.token', ['token' => $token]
            );
        };
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }
}
