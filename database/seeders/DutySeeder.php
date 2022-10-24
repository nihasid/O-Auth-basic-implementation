<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Duties;

class DutySeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        //
        $data = [
            [
                'id' => '7d49960f-b993-4754-9b3d-822cc5c1c3d0',
                'duty_type_group_name' => 'fire extinguishing',
                'duty_group_detail' => 'fire extinguishing',
                'duty_group_slug' => 'fire_extinguishing',
                'status' => true
            ],
            [
                'id' => 'd8c10f1d-ab3b-427b-b910-4d716d0b2e39',
                'duty_type_group_name' => 'first aid',
                'duty_group_detail' => 'first aid',
                'duty_group_slug' => 'first_aid',
                'status' => true
            ],
            [
                'id' => 'e445754e-596b-492b-9db9-1a0517f6c349',
                'duty_type_group_name' => 'special training',
                'duty_group_detail' => 'special training',
                'duty_group_slug' => 'special_training',
                'status' => true
            ],
                
        ]; 
        Duties::insert($data);
    }
}
