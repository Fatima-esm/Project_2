<?php

namespace Database\Seeders;

use App\Models\Admin;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\User;

class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // add admin account
        $admin = User::create([
            'full_name' => 'Admin',
            'email' => 'admin@gmail.com',
            'phone' => '09876540321',
            'password' => bcrypt('12345678'),
            'role' => 'admin',
            'active_at' => 1,
            'status' => 'active',
            'email_verified_at' => now(),
            'membership_number' => 'ADM-001',
        ]);
        $admin->assignRole('admin');
        
        // add receptionist account
        $reception = [
            [
                'full_name' => 'Reception 1',
                'email' => 'reception1@gmail.com',
                'phone' => '0987654322',
                'password' => bcrypt('12345678'),
                'role' => 'reception',
                'status' => 'active',
                'active_at' => 1,
                'email_verified_at' => now(),
                'membership_number' => 'REC-20001',
            ],
            [
                'full_name' => 'Reception 2',
                'email' => 'reception2@gmail.com',
                'phone' => '09876504323',
                'password' => bcrypt('12345678'),
                'role' => 'reception',
                'status' => 'active',
                'active_at' => 1,
                'email_verified_at' => now(),
                'membership_number' => 'REC-20002',
            ],
            [
                'full_name' => 'Reception 3',
                'email' => 'reception3@gmail.com',
                'phone' => '09876500323',
                'password' => bcrypt('12345678'),
                'role' => 'reception',
                'status' => 'active',
                'active_at' => 1,
                'email_verified_at' => now(),
                'membership_number' => 'REC-20003',
            ],
        ];

        foreach ($reception as $recepData) {
            $user = User::create($recepData);
            
            // إذا كنت تستخدم حزمة Spatie لإدارة الأدوار، يمكنك تفعيل هذا السطر:
            if (method_exists($user, 'assignRole')) {
                $user->assignRole('reception');
            }
        }


        
    }
}
