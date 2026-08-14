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
            'full_name' => 'Admin Mohammad',
            'email' => 'admin_mohammad@gmail.com',
            'phone' => '936876540777',
            'password' => bcrypt('12345678'),
            'role' => 'admin',
            'active_at' => 1,
            'status' => 'active',
            'email_verified_at' => now(),
            'membership_number' => 'ADM-00001',
            'profile_image'     => 'profiles/admin.jpg',

        ]);
        $admin->assignRole('admin');
        
        // add receptionist account
        $reception = [
            [
                'full_name' => 'Reception Rami',
                'email' => 'reception_rami@gmail.com',
                'phone' => '963876543220',
                'password' => bcrypt('12345678'),
                'role' => 'reception',
                'status' => 'active',
                'active_at' => 1,
                'email_verified_at' => now(),
                'membership_number' => 'REC-22001',
                'profile_image'     => 'profiles/rami.jpg',

            ],
            [
                'full_name' => 'Reception Hani',
                'email' => 'reception_hani@gmail.com',
                'phone' => '963876504329',
                'password' => bcrypt('12345678'),
                'role' => 'reception',
                'status' => 'active',
                'active_at' => 1,
                'email_verified_at' => now(),
                'membership_number' => 'REC-22002',
                'profile_image'     => 'profiles/hani.jpg',
            ],
            [
                'full_name' => 'Reception Anas',
                'email' => 'reception_anas@gmail.com',
                'phone' => '963876500353',
                'password' => bcrypt('12345678'),
                'role' => 'reception',
                'status' => 'active',
                'active_at' => 1,
                'email_verified_at' => now(),
                'membership_number' => 'REC-22003',
                'profile_image'     => 'profiles/anas.jpg',

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
