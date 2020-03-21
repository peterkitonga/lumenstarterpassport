# Lumen Starter Passport

A starter for RESTful APIs built with the Lumen framework. This starter is meant to serve as a starting point for those building a lightweight API for their app.

## Frameworks and Tools

Below are the frameworks and dependencies used to code the project.

| Dependency              | Version             |
|------------------------:|--------------------:|
| PHP                     | 7.1.3 & greater     |
| Lumen                   | 5.7.*               |
| guzzlehttp/guzzle       | --                  |
| fruitcake/laravel-cors  | --                  |
| flipbox/lumen-generator | --                  |
| dusterio/lumen-passport | --                  |

## Quick start

* Clone the repo: `git clone https://github.com/PeterKitonga/lumenstarterpassport.git`
* Go into project folder and run `composer install` to install all dependencies
* Create environment configurations file `cp .env.example .env` and modify the mail, database, cache, file systems
* Run `php artisan key:generate` to get the app key cipher for encryption
* Run migrations `php artisan migrate`
* Run `php artisan passport:install` to generate the oauth keys for generating API tokens for authentication
* Copy and paste the printed keys from your terminal to your env variables:
    1. `PERSONAL_ACCESS_CLIENT_ID` and `PERSONAL_ACCESS_CLIENT_SECRET` which are the first two printed lines on the terminal
    2. `PASSWORD_GRANT_CLIENT_ID` and `PASSWORD_GRANT_CLIENT_SECRET` which are the last two printed lines on the terminal
* Run authentication seeders `php artisan db:seed --class=AuthTablesSeeder`
* In case you want some dummy users to play around with run `php artisan db:seed --class=UsersTableSeeder`
* Run other seeders `php artisan db:seed`
* If using the public driver as your `FILESYSTEM_DRIVER`, then:-
    1. create a directory named `public` under `/pathtoproject/storage/app/`
    2. create symbolic link to `/pathtoproject/public/storage` e.g.
    ```ln -s /var/www/php/lumenstarterpassport/storage/app/public/ /var/www/php/lumenstarterpassport/public/storage```
* In case you have a front end, don't forget to update the `WEB_CLIENT_URL` env variable to the URL of your front end
* Run application `php artisan serve`

## Lumen PHP Framework

[![Build Status](https://travis-ci.org/laravel/lumen-framework.svg)](https://travis-ci.org/laravel/lumen-framework)
[![Total Downloads](https://poser.pugx.org/laravel/lumen-framework/d/total.svg)](https://packagist.org/packages/laravel/lumen-framework)
[![Latest Stable Version](https://poser.pugx.org/laravel/lumen-framework/v/stable.svg)](https://packagist.org/packages/laravel/lumen-framework)
[![Latest Unstable Version](https://poser.pugx.org/laravel/lumen-framework/v/unstable.svg)](https://packagist.org/packages/laravel/lumen-framework)
[![License](https://poser.pugx.org/laravel/lumen-framework/license.svg)](https://packagist.org/packages/laravel/lumen-framework)

Laravel Lumen is a stunningly fast PHP micro-framework for building web applications with expressive, elegant syntax. We believe development must be an enjoyable, creative experience to be truly fulfilling. Lumen attempts to take the pain out of development by easing common tasks used in the majority of web projects, such as routing, database abstraction, queueing, and caching.

## Official Documentation

Documentation for the framework can be found on the [Lumen website](http://lumen.laravel.com/docs).

## Security Vulnerabilities

If you discover a security vulnerability within Lumen, please send an e-mail to Taylor Otwell at taylor@laravel.com. All security vulnerabilities will be promptly addressed.

## License

The Lumen framework is open-sourced software licensed under the [MIT license](http://opensource.org/licenses/MIT)
