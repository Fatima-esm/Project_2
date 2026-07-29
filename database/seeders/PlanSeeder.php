<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Plan;
class PlanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Plan::create(['name' => 'Free Trial', 'duration_days' => 7, 'price' => 0]);
        Plan::create(['name' => 'month', 'duration_days' => 30, 'price' => 50]);
        Plan::create(['name' => '3 months', 'duration_days' => 90, 'price' => 120]);
        Plan::create(['name' => '6 months', 'duration_days' => 180, 'price' => 200]);

    }
}
