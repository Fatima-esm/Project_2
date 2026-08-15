<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ClubService;

class ClubServiceSeeder extends Seeder
{
    public function run(): void
    {
        $services = [
            [
                'name'       => 'موقف سيارات',
                'icon'       => 'parking',
                'status'     => 'available',
                'sort_order' => 1,
                'is_active'  => true,
            ],
            [
                'name'       => 'إنترنت',
                'icon'       => 'wifi',
                'status'     => 'available',
                'sort_order' => 2,
                'is_active'  => true,
            ],
            [
                'name'       => 'غرف تبديل',
                'icon'       => 'locker',
                'status'     => 'available',
                'sort_order' => 3,
                'is_active'  => true,
            ],
            [
                'name'       => 'دش',
                'icon'       => 'shower',
                'status'     => 'available',
                'sort_order' => 4,
                'is_active'  => true,
            ],
            [
                'name'       => 'كافتيريا',
                'icon'       => 'coffee',
                'status'     => 'available',
                'sort_order' => 5,
                'is_active'  => true,
            ],
        ];

        foreach ($services as $service) {
            ClubService::updateOrCreate(
                ['name' => $service['name']],
                $service
            );
        }
    }
}