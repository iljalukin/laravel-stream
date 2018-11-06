<?php

use Illuminate\Database\Seeder;

class UsersTableSeeder extends Seeder {

    public function run()
    {
        DB::table('users')->delete();


        $users = array(
            array(
                'name'      => 'admin',
                'email'      => 'admin@example.org',
                'password'   => Hash::make('admin'),
		'api_token'   => str_random(32),
            ),
            array(
                'name'      => 'user',
                'email'      => 'user@example.org',
                'password'   => Hash::make('user'),
		'api_token'  => str_random(32),
            )
        );

        DB::table('users')->insert( $users );
    }

}
