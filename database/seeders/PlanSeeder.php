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
        Plan::create([
            'name'          => 'تجربة مجانية',
            'duration_days' => 7,
            'price'         => 0,
            'details'       => 'باقة تجريبية لمدة 7 أيام تتيح لك استكشاف خدمات النادي الأساسية.'
        ]);

        Plan::create([
            'name'          => 'الباقة الفضية',
            'duration_days' => 30,
            'price'         => 50,
            'details'       => 'باقة شهرية (30 يوماً) تشمل الدخول لصالة الألعاب الرياضية والمعدات الأساسية.'
        ]);

        Plan::create([
            'name'          => 'الباقة الذهبية',
            'duration_days' => 90,
            'price'         => 120,
            'details'       => 'باقة ربع سنوية لمدة 3 أشهر (90 يوماً) تشمل الصالة الرياضية وجلسات تدريبية مخصصة.'
        ]);

        Plan::create([
            'name'          => 'الباقة الماسية',
            'duration_days' => 180,
            'price'         => 200,
            'details'       => 'باقة نصف سنوية لمدة 6 أشهر (180 يوماً) تشمل كافة مميزات النادي، حصص جماعية، ومدرب خاص.'
        ]);
    }
}