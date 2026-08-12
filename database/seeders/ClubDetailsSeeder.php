<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\ClubDetail;

class ClubDetailsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        ClubDetail::Create(
            [
                'name'         => 'SMART GYM',
                'phone'        => '+962712345678',
                'email'        => 'SmartGym334@gmail.com',
                'location'     => 'دمشق - البرامكة',
                'details'      => ' ',
                'opening_time' => '06:00:00',
                'closing_time' => '00:00:00',
                'status'       => 'open',
                'image'        => 'images/SmartGym.png',
            ]
        );
    }
}
