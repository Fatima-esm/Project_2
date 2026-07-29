<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\WorkSchedule;

class WorkScheduleSeeder extends Seeder
{
    public function run(): void
    {
              // صباحي
            WorkSchedule::create([
                'days'        => 'الاحد-الخميس',
                'work_name' => 'صباحي',
                'start_time' => '08:00',
                'end_time'   => '16:00',
            ]);
            // مسائي
            WorkSchedule::create([
                'days'        => ' الاحد-الخميس',
                'work_name' => 'مسائي',
                'start_time' => '16:00',
                'end_time'   => '00:00',
            ]);
      

            WorkSchedule::create([
                'days'        => 'الجمعة-السبت',
                'work_name' => 'صباحي',
                'start_time' => '08:00',
                'end_time'   => '12:00',
            ]);
            WorkSchedule::create([
                'days'        => 'الجمعة-السبت',
                'work_name' => 'مسائي',
                'start_time' => '16:00',
                'end_time'   => '20:00',
            ]);
        }
    }
