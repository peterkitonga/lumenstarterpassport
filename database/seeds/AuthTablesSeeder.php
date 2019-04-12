<?php

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AuthTablesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        // Users table
        DB::table('users')->truncate();
        DB::table('users')->delete();

        \App\User::query()->create([
            'name' => 'Default Admin',
            'email' => 'admin@admin.com',
            'password' => Hash::make('password'),
            'is_active' => 1,
            'email_verified_at' => Carbon\Carbon::now()->toDateTimeString(),
            'created_at' => Carbon\Carbon::now()->toDateTimeString(),
            'updated_at' => Carbon\Carbon::now()->toDateTimeString()
        ]);

        \App\User::query()->create([
            'name' => 'Default Subscriber',
            'email' => 'subscriber@subscriber.com',
            'password' => Hash::make('password'),
            'is_active' => 1,
            'email_verified_at' => Carbon\Carbon::now()->toDateTimeString(),
            'created_at' => Carbon\Carbon::now()->toDateTimeString(),
            'updated_at' => Carbon\Carbon::now()->toDateTimeString()
        ]);

        echo "\033[32m Users successfully inserted. \033[0m".PHP_EOL;

        // Roles table
        DB::table('roles')->truncate();
        DB::table('roles')->delete();

        \App\Role::query()->create([
            'name' => 'Administrator',
            'slug' => 'administrator'
        ]);

        \App\Role::query()->create([
            'name' => 'Subscriber',
            'slug' => 'subscriber'
        ]);

        echo "\033[32m Roles successfully inserted. \033[0m".PHP_EOL;

        // Attach Role to Users
        DB::table('role_user')->truncate();
        DB::table('role_user')->delete();

        DB::table('role_user')->insert([
            [
                'role_id' => 1,
                'user_id' => 1
            ]
        ]);

        DB::table('role_user')->insert([
            [
                'role_id' => 2,
                'user_id' => 2
            ]
        ]);

        echo "\033[32m Roles attached to users successfully. \033[0m".PHP_EOL;
    }
}
