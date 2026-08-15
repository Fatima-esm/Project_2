<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ClubEvent;

class ClubEventSeeder extends Seeder
{
    public function run(): void
    {
        $events = [
            // 5 قادمة (متاحة) 
            [
                'title'       => 'حصة زومبا جماعية',
                'description' => 'حصة زومبا حماسية لحرق الدهون وتحسين اللياقة، مناسبة لجميع المستويات.',
                'image'       => 'images/event1.png',
                'event_date'  => '2026-08-20',
                'start_time'  => '18:00',
                'end_time'    => '19:00',
                'status'      => 'available',
                'is_active'   => true,
            ],
            [
                'title'       => 'ورشة تضخيم العضلات',
                'description' => 'ورشة عملية لتمارين التضخيم بإشراف مدربين متخصصين.',
                'image'       => 'images/event2.png',
                'event_date'  => '2026-08-22',
                'start_time'  => '17:00',
                'end_time'    => '19:00',
                'status'      => 'available',
                'is_active'   => true,
            ],
            [
                'title'       => 'يوجا للاسترخاء',
                'description' => 'جلسة يوجا لتحسين المرونة وتقليل التوتر.',
                'image'       => 'images/event3.png',
                'event_date'  => '2026-08-25',
                'start_time'  => '19:00',
                'end_time'    => '20:00',
                'status'      => 'available',
                'is_active'   => true,
            ],
            [
                'title'       => 'HIIT حرق دهون',
                'description' => 'تمرين عالي الكثافة لحرق أقصى سعرات في أقل وقت.',
                'image'       => 'images/event4.png',
                'event_date'  => '2026-08-28',
                'start_time'  => '20:00',
                'end_time'    => '20:45',
                'status'      => 'available',
                'is_active'   => true,
            ],
            [
                'title'       => 'تحدي الجري 5 كم',
                'description' => 'تحدي جري جماعي مع جوائز للمراكز الأولى.',
                'image'       => 'images/event5.png',
                'event_date'  => '2026-09-01',
                'start_time'  => '07:00',
                'end_time'    => '08:30',
                'status'      => 'available',
                'is_active'   => true,
            ],

            // ===== 2 منتهية =====
            [
                'title'       => 'يوم مفتوح للعائلات',
                'description' => 'فعاليات تعريفية وأنشطة للعائلات داخل النادي.',
                'image'       => 'images/event6.png',
                'event_date'  => '2026-08-10',
                'start_time'  => '10:00',
                'end_time'    => '14:00',
                'status'      => 'available',
                'is_active'   => true,
            ],
            [
                'title'       => 'محاضرة التغذية الرياضية',
                'description' => 'أساسيات التغذية لبناء العضلات وحرق الدهون.',
                'image'       => 'images/event7.png',
                'event_date'  => '2026-08-12',
                'start_time'  => '17:30',
                'end_time'    => '18:30',
                'status'      => 'available',
                'is_active'   => true,
            ],
        ];

        foreach ($events as $event) {
            ClubEvent::updateOrCreate(
                [
                    'title'      => $event['title'],
                    'event_date' => $event['event_date'],
                ],
                $event
            );
        }
    }
}