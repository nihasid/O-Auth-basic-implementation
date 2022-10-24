<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Positions;

class PositionSeeder extends Seeder
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
            'id' => '5e49df4c-eb02-44fa-b199-07230b89ad77',
            'position_code' => '2',
            'position_name' => 'project leader',
            'position_category' => 'management level',
            'status' => true
            ],
            [
                'id' => 'ff6765f8-5f83-4d1f-b5d7-0ffa8320f95d',
                'position_code' => '3',
                'position_name' => 'developer',
                'position_category' => '',
                'status' => true
            ]
        ];
        Positions::insert($data);

    }
}
