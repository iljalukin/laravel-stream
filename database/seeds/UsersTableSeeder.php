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
            ),
            array(
                'name'      => 'user',
                'email'      => 'user@example.org',
                'password'   => Hash::make('user'),
            )
        );

        DB::table('users')->insert( $users );
    }

}
