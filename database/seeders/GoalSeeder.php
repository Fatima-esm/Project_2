<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Goal;
use Illuminate\Support\Facades\DB;

class GoalSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
      DB::table('goals')->insert([
        [
            'goal_name' => 'خسارة الوزن والتنشيف',
            'description' => 'حرق الدهون وتحسين اللياقة البدنية'
        ],
        [
            'goal_name' => 'بناء كتلة عضلية نقية',
            'description' => 'زيادة حجم العضلات والقوة البدنية'
        ],
        [
            'goal_name' => 'تحسين الأداء واللياقة',
            'description' => 'رفع القدرة على التحمل ونشاط الجسم'
        ],
        [
            'goal_name' => 'المحافظة على الوزن الحالي',
            'description' => 'موازنة السعرات الحرارية اليومية'
        ],
    ]);
    }


}
