<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use \App\Models\Users;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // 
        Users::factory()->count(1)
                        ->create()
                        ->each(
                            function($user) {
                                $user->assignRole('super-admin');
                            }
                        );

        Users::factory()->count(2)
                        ->create()
                        ->each(
                            function($user) {
                                $user->assignRole('pro-admin');
                            }
                        );
    }
}
