<?php

namespace App\Providers;

use Carbon\Carbon;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Boot the authentication services for the application.
     *
     * @return void
     */
    public function boot()
    {
        \Dusterio\LumenPassport\LumenPassport::routes($this->app, ['prefix' => 'api/v1/oauth']);
        \Dusterio\LumenPassport\LumenPassport::tokensExpireIn(Carbon::now()->addMonth(), env('PASSWORD_GRANT_CLIENT_ID'));
    }
}
