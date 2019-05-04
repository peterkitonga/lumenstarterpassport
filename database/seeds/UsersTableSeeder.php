<?php

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UsersTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $guzzle = new \GuzzleHttp\Client();

        $response = $guzzle->get('https://randomuser.me/api/?page=1&results=200&nat=gb&seed=9a7cbf079c0ad2ef');

        $users = json_decode((string) $response->getBody(), true);

        foreach ($users['results'] as $user) {
            $check = \App\User::query()->where('email', 'LIKE', '%'.$user['email'].'%')->first();

            if (!$check) {
                $subscriber = \App\User::query()->create([
                    'name' => $user['name']['first'].' '.$user['name']['last'],
                    'email' => $user['email'],
                    'password' => \Illuminate\Support\Facades\Hash::make($user['login']['password']),
                    'profile_image' => $user['picture']['large'],
                    'is_active' => 1,
                    'email_verified_at' => Carbon\Carbon::now()->toDateTimeString()
                ]);

                DB::table('role_user')->insert([
                    [
                        'role_id' => 2,
                        'user_id' => $subscriber->id
                    ]
                ]);
            }
        }
    }
}
