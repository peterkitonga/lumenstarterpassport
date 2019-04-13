<?php

namespace App\Providers;

use App\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
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

        $this->registerPolicies();
    }

    /**
     * Checks for the user's access permissions
     *
     * @return void
     */
    public function registerPolicies()
    {
        if (Schema::connection(env('DB_CONNECTION'))->hasTable('roles'))
        {
            $roles = \App\Role::query()->pluck('slug');

            foreach ($roles as $role)
            {
                Gate::define($role, function (User $user) use ($role) {
                    return $user->inRole($role);
                });
            }
        }
    }
}
