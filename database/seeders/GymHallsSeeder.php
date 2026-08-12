<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\GymHall;

class GymHallsSeeder extends Seeder
{
    public function run(): void
    {
        $halls = [
            // --- منشآت رئيسية وملاعب ومسابح (جماعية) ---
            [
                'name' => 'ملعب كرة القدم',
                'type' => 'group',
                'capacity' => 30,
                'status' => 'available',
                'description' => 'ملعب كرة قدم خماسي خارجي مزود بإضاءة ليلية حديثة وعشب صناعي عالي الجودة.'
            ],
            [
                'name' => 'المسبح المغلق',
                'type' => 'group',
                'capacity' => 40,
                'status' => 'available',
                'description' => 'مسبح مغلق ومدفأ بطول 25 متراً مخصص لتدريبات السباحة الجماعية والبطولات.'
            ],
            [
                'name' => 'صالة القوة والتحمل',
                'type' => 'group',
                'capacity' => 40,
                'status' => 'available',
                'description' => 'صالة واسعة مجهزة بالكامل لألعاب القوة الشاملة والتدريب الجماعي الحر.'
            ],
            [
                'name' => 'صالة اللياقة البدنية',
                'type' => 'group',
                'capacity' => 40,
                'status' => 'available',
                'description' => 'قاعة مجهزة بأرضيات خشبية مرنة، مرايا كاملة، ونظام صوتي متطور لحصص اللياقة الحركية.'
            ],
            [
                'name' => 'صالة الفنون القتالية',
                'type' => 'group',
                'capacity' => 20,
                'status' => 'available',
                'description' => 'قاعة مفروشة بالكامل بفرشات الحماية مخصصة للكاراتيه، الملاكمة، والتايكوندو.'
            ],
            [
                'name' => 'قاعة التأمل والاسترخاء',
                'type' => 'group',
                'capacity' => 15,
                'status' => 'available',
                'description' => 'قاعة هادئة ذات إضاءة مريحة وديكورات طبيعية مخصصة لجلسات التوازن الذهني والاسترخاء.'
            ],
            [
                'name' => 'صالة الدراجات الثابتة',
                'type' => 'group',
                'capacity' => 25,
                'status' => 'available',
                'description' => 'مجهزة بدراجات احترافية مع لوحات عرض تتبع الأداء وإضاءة تفاعلية حماسية.'
            ],
            [
                'name' => 'صالة التدريب الوظيفي',
                'type' => 'group',
                'capacity' => 30,
                'status' => 'available',
                'description' => 'تحتوي على حبال التسلق، كرات الحائط، ومعدات تقوية العضلات الوظيفية للمجموعات.'
            ],
            [
                'name' => 'صالة الجمباز',
                'type' => 'group',
                'capacity' => 20,
                'status' => 'available',
                'description' => 'مجهزة بأجهزة التوازن والقفز والمراتب الخاصة بتدريبات الرشاقة والمرونة.'
            ],
            [
                'name' => 'صالة التدريب الدائري',
                'type' => 'group',
                'capacity' => 18,
                'status' => 'available',
                'description' => 'موزعة بأجهزة متسلسلة مخصصة لحرق الدهون ورفع كفاءة اللياقة العامة للمجموعات.'
            ],

            // --- الصالات الفردية (العشرة المطلوبة) ---
            [
                'name' => 'صالة التدريب الشخصي (أ)',
                'type' => 'individual',
                'capacity' => 2,
                'status' => 'available',
                'description' => 'صالة خاصة ومغلقة للتدريب الفردي مع مدرب خاص بأعلى مستويات الخصوصية.'
            ],
            [
                'name' => 'غرفة الفحص والقياس البدني',
                'type' => 'individual',
                'capacity' => 3,
                'status' => 'available',
                'description' => 'مجهزة بأحدث أجهزة تحليل مكونات الجسم ومعدات قياس نسب الدهون والكتلة العضلية.'
            ],
            [
                'name' => 'جناح الاستشفاء الحراري',
                'type' => 'individual',
                'capacity' => 2,
                'status' => 'available',
                'description' => 'مخصص لجلسات الاستشفاء السريع، حمام البخار، والمساج الفردي بعد التمارين الشاقة.'
            ],
            [
                'name' => 'صالة الأوزان الحرة',
                'type' => 'individual',
                'capacity' => 4,
                'status' => 'available',
                'description' => 'مخصصة للمدربين الشخصيين مع متدربيهم لرفع الأوزان الثقيلة بأمان تام.'
            ],
            [
                'name' => 'صالة التدريب الشخصي (ب)',
                'type' => 'individual',
                'capacity' => 2,
                'status' => 'available',
                'description' => 'غرفة خاصة معزولة صوتياً ومجهزة بأدوات مقاومة متكاملة للتدريب الفردي.'
            ],
            [
                'name' => 'صالة التدريب الشخصي (ج)',
                'type' => 'individual',
                'capacity' => 4,
                'status' => 'available',
                'description' => 'غرفة خاصة معزولة صوتياً ومجهزة بأدوات مقاومة متكاملة للتدريب الفردي.'
            ],
            [
                'name' => 'صالة التدريب الشخصي (د)',
                'type' => 'individual',
                'capacity' => 4,
                'status' => 'available',
                'description' => 'غرفة خاصة معزولة صوتياً ومجهزة بأدوات مقاومة متكاملة للتدريب الفردي.'
            ],
            [
                'name' => 'وحدة التأهيل البدني',
                'type' => 'individual',
                'capacity' => 2,
                'status' => 'available',
                'description' => 'مجهزة بأجهزة العلاج الطبيعي والمعدات الرياضية الخاصة بتأهيل الإصابات الرياضية.'
            ],
            [
                'name' => 'ركن تمارين القوة الفردية',
                'type' => 'individual',
                'capacity' => 3,
                'status' => 'available',
                'description' => 'مزود بقفص حديدي متكامل لتمارين الرفع الفردية بإشراف مباشر.'
            ],
            [
                'name' => 'قاعة المرونة والإطالة',
                'type' => 'individual',
                'capacity' => 2,
                'status' => 'available',
                'description' => 'مجهزة بالأدوات المخصصة لتمارين زيادة مرونة العضلات واستطالتها.'
            ],
            [
                'name' => 'صالة التدريب المكثف السريع',
                'type' => 'individual',
                'capacity' => 2,
                'status' => 'available',
                'description' => 'مخصصة للتمارين السريعة ذات الكثافة العالية لشخص أو اثنين كحد أقصى.'
            ],
            [
                'name' => 'مكتب الإرشاد الغذائي',
                'type' => 'individual',
                'capacity' => 3,
                'status' => 'available',
                'description' => 'مكتب مجهز لاستقبال المتدربين بشكل فردي لوضع الأنظمة الغذائية وبرامج المتابعة.'
            ],
        ];

        foreach ($halls as $hall) {
            GymHall::create($hall);
        }
    }
}