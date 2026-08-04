<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\WorkSchedule;
use App\Models\User;
use Illuminate\Support\Facades\DB; // تأكد من استيراد DB

class WorkScheduleSeeder extends Seeder
{
    public function run(): void
    {
        // 1. إنشاء المواعيد وتخزينها في متغيّرات
        $morningSchedule = WorkSchedule::create([
            'days'       => 'الاحد-الخميس',
            'work_name'  => 'صباحي',
            'start_time' => '08:00',
            'end_time'   => '16:00',
        ]);

        $eveningSchedule = WorkSchedule::create([
            'days'       => 'الاحد-الخميس',
            'work_name'  => 'مسائي',
            'start_time' => '16:00',
            'end_time'   => '00:00',
        ]);

        WorkSchedule::create([
            'days'       => 'الجمعة-السبت',
            'work_name'  => 'صباحي',
            'start_time' => '08:00',
            'end_time'   => '12:00',
        ]);

        WorkSchedule::create([
            'days'       => 'الجمعة-السبت',
            'work_name'  => 'مسائي',
            'start_time' => '16:00',
            'end_time'   => '20:00',
        ]);

        // 2. جلب الكوتشز وتوزيع المواعيد عليهم عبر جدول الربط (coach_schedule)
        $coaches = User::where('role', 'coach')->get();

        foreach ($coaches as $index => $coach) {
            // توزيع المواعيد بالتبادل (صباحي أو مسائي)
            $scheduleId = ($index % 2 == 0) ? $morningSchedule->id : $eveningSchedule->id;

            DB::table('coach_schedule')->updateOrInsert(
                ['user_id' => $coach->id],
                [
                    'work_schedule_id' => $scheduleId,
                    'created_at'       => now(),
                    'updated_at'       => now(),
                ]
            );
        }

        $reception = User::where('role', 'reception')->get();

        foreach ($reception as $index => $recep) {
            // توزيع المواعيد بالتبادل (صباحي أو مسائي)
            $scheduleId = ($index % 2 == 0) ? $morningSchedule->id : $eveningSchedule->id;

            DB::table('coach_schedule')->updateOrInsert(
                ['user_id' => $recep->id],
                [
                    'work_schedule_id' => $scheduleId,
                    'created_at'       => now(),
                    'updated_at'       => now(),
                ]
            );
        }


    }
}