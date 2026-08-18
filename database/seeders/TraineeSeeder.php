<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Plan;

use App\Models\Subscription;
use Illuminate\Support\Facades\Hash;

class TraineeSeeder extends Seeder
{
    public function run(): void
    {
        $coaches = User::where('role', 'coach')
                        ->where('status', 'active')
                        ->get();
                        
        if ($coaches->isEmpty()) {
            return;  }

        for ($i = 1; $i <= 40; $i++) {
            $randomCoach = $coaches->random();

            $trainee = User::create([
                'full_name'         => "Trainee {$i}",
                'age'               => rand(18, 45),
                'gender'            => 'رجل' ,
                'active_at'         => 1,
                'email'             => "trainee_{$i}@gmail.com",
                'phone'             => '963123422' . str_pad($i, 3, '0', STR_PAD_LEFT),
                'password'          => Hash::make('12345678'),
                'membership_number' => 'SG-' . rand(10000, 99999),
                'role'              => 'trainee',
                'coach_id'          => $randomCoach->id, 
                'status'            => 'active',
                'profile_image'     => 'profiles/user.jpg',

                'email_verified_at' => now(),
            ]);

            $trainee->assignRole('trainee');

            $freePlan = Plan::where('price', 0)->first();
            Subscription::create([
                'user_id'    => $trainee->id,
                'plan_id'    => $freePlan->id, 
                'price'      => 0,
                'status'     => 'paid', 
                'starts_at'  => now(),
                'expires_at' => now()->addDays($freePlan->duration_days),
            ]);
        }
    }
}