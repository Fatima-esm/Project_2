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
            'email' => 'admin@gymapp.com',
            'phone' => '0987654321',
            'password' => bcrypt('12345678'),
            'role' => 'admin',
            'active_at' => 1,
            'status' => 'active',
            'email_verified_at' => now(),
            'membership_number' => 'ADM-001',
        ]);
        $admin->assignRole('admin');
        
        // add receptionist account
        $reception = User::create([
            'full_name' => 'Reception',
            'email' => 'reception@gymapp.com',
            'phone' => '0987654322',
            'password' => bcrypt('12345678'),
            'role' => 'reception',
            'status' => 'active',
            'active_at' => 1,
            'email_verified_at' => now(),
            'membership_number' => 'REC-001',
        ]);
        $reception->assignRole('reception');


        
    }
}
