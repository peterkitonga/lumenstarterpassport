<?php

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It is a breeze. Simply tell Lumen the URIs it should respond to
| and give it the Closure to call when that URI is requested.
|
*/
$router = app()->router;

$router->get('/', function () use ($router) {
    return $router->app->version();
});

$router->group(['prefix' => 'mail'], function () use ($router) {
    $router->get('activation', function () use ($router) {
        return new App\Mail\UserActivation(new App\User(['name' => 'Example User', 'activation_code' => str_random(64)]));
    });

    $router->get('reset', function () use ($router) {
        return new App\Mail\UserResetPassword(new App\User(['name' => 'Example User', 'email' => 'example@user.com']), str_random(64));
    });

    $router->get('credentials', function () use ($router) {
        return new App\Mail\UserCredentials(new App\User(['name' => 'Example User', 'password' => str_random(6)]));
    });
});

/*------------------------------------------ Api Version 1 Routes -------------------------------------------*/
$router->group(['prefix' => 'api/v1', 'middleware' => 'cors', 'namespace' => 'V1'], function () use ($router) {
    /*------------------------------------------ Guest Routes -------------------------------------------*/
    $router->group(['prefix' => 'auth'], function () use ($router) {
        $router->post('login', ['uses' => 'Auth\AuthsController@login']);
        $router->post('register', ['uses' => 'Auth\AuthsController@register']);
        $router->get('activate/{code}', ['uses' => 'Auth\AuthsController@activate']);
        $router->post('email/reset/password/link', ['uses' => 'Auth\AuthsController@forgot']);
        $router->post('reset/password', ['uses' => 'Auth\AuthsController@reset']);
    });

    /*------------------------------------------ Auth Routes -------------------------------------------*/
    $router->group(['middleware' => 'auth'], function() use ($router) {
        // Auth Routes
        $router->group(['prefix' => 'auth'], function () use ($router) {
            $router->post('refresh', ['uses' => 'Auth\AuthsController@refresh']);
            $router->get('user', ['uses' => 'Auth\AuthsController@profile']);
            $router->put('user/update', ['uses' => 'Auth\AuthsController@update']);
            $router->put('user/password/update', ['uses' => 'Auth\AuthsController@password']);
            $router->get('logout', ['uses' => 'Auth\AuthsController@logout']);
        });

        
    });
});
