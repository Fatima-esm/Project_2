<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

class CoachFactory extends Factory
{
    protected $model = User::class;

    public function definition(): array
    {
        for ($i = 1; $i <= 5; $i++) {
            User::create([
                'full_name'         => "coach{$i}",
                'email'             => "Coach_{$i}@gmail.com",
                'phone'             => '090809033' . rand(10, 99),
                'password'          => Hash::make('12345678'),
                'role'              => 'coach',
                'status'            => 'pending',
                'active_at'         => 0,
                'membership_number' => 'SG-' . rand(10000, 99999),
                'cv_url'            => null,
                'rejection_reason'  => null,
            ]);
        }
    }
}