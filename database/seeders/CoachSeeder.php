<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class CoachSeeder extends Seeder
{
    public function run(): void
    {
        // مصفوفة البيانات
        $coaches = [
            [
                'full_name' => 'Coach 1',
                'email' => 'coach1@gmail.com',
                'phone' => '09876054321',
                'password' => Hash::make('12345678'),
                'role' => 'coach',
                'status' => 'active',
                'active_at' => 1,
                'email_verified_at' => now(),
                'membership_number' => 'SG-20011',
            ],
            [
                'full_name' => 'Coach 2',
                'email' => 'coach2@gmail.com',
                'phone' => '09876540322',
                'password' => Hash::make('12345678'),
                'role' => 'coach',
                'status' => 'active',
                'active_at' => 1,
                'email_verified_at' => now(),
                'membership_number' => 'SG-20012',
            ],
            [
                'full_name' => 'Coach 3',
                'email' => 'coach3@gmail.com',
                'phone' => '09897654323',
                'password' => Hash::make('12345678'),
                'role' => 'coach',
                'status' => 'active',
                'active_at' => 1,
                'email_verified_at' => now(),
                'membership_number' => 'SG-20013',
            ],
            [
                'full_name' => 'Coach 4',
                'email' => 'coach4@gmail.com',
                'phone' => '09876504324',
                'password' => Hash::make('12345678'),
                'role' => 'coach',
                'status' => 'active',
                'active_at' => 1,
                'email_verified_at' => now(),
                'membership_number' => 'SG-20014',
            ],
            [
                'full_name' => 'Coach 5',
                'email' => 'coach5@gmail.com',
                'phone' => '09987654325',
                'password' => Hash::make('12345678'),
                'role' => 'coach',
                'status' => 'pending',
                'active_at' => 0,
                'email_verified_at' => now(),
                'membership_number' => 'SG-20015',
            ],
            [
                'full_name' => 'Coach 6',
                'email' => 'coach6@gmail.com',
                'phone' => '09876504326',
                'password' => Hash::make('12345678'),
                'role' => 'coach',
                'status' => 'pending',
                'active_at' => 0,
                'email_verified_at' => now(),
                'membership_number' => 'SG-20016',
            ],
        ];

        foreach ($coaches as $coachData) {
            $user = User::create($coachData);
            
            // إذا كنت تستخدم حزمة Spatie لإدارة الأدوار، يمكنك تفعيل هذا السطر:
            if (method_exists($user, 'assignRole')) {
                $user->assignRole('coach');
            }
        }
    }
}