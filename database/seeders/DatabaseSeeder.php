<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use App\Models\Admin;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */

    public function run(): void
    {
        
        
        $this->call(RolesAndPermissionsSeeder::class);
        
        $this->call([AdminSeeder::class]);
                
        $this->call(PlanSeeder::class);
         
        $this->call(GoalSeeder::class);

        $this->call(WorkScheduleSeeder::class);

    }
}
