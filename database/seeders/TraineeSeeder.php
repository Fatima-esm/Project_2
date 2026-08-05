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
        // 1. جلب جميع الكوتشز المتاحين لاختيار أحدهم عشوائياً لكل متدرب
        $coaches = User::where('role', 'coach')->get();

        if ($coaches->isEmpty()) {
            return; // إن لم يكن هناك كوتشز، يتم إيقاف السيدر لتجنب الأخطاء
        }

        // 2. إنشاء 40 متدرباً
        for ($i = 1; $i <= 40; $i++) {
            // اختيار كوتش عشوائي لهذا المتدرب
            $randomCoach = $coaches->random();

            $trainee = User::create([
                'full_name'         => "Trainee {$i}",
                'age'               => rand(18, 45),
                'gender'            => 'رجل' ,
                'active_at'         => 1,
                'email'             => "trainee_{$i}@gmail.com",
                'phone'             => '96312345' . str_pad($i, 3, '0', STR_PAD_LEFT),
                'password'          => Hash::make('12345678'),
                'membership_number' => 'SG-' . rand(10000, 99999),
                'role'              => 'trainee',
                'coach_id'          => $randomCoach->id, // ربط المتدرب بالكوتش مباشرة عبر عمود coach_id
                'status'            => 'active',
                'email_verified_at' => now(),
            ]);

            $trainee->assignRole('trainee');

            $freePlan = Plan::where('name', 'Free Trial')->first();
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