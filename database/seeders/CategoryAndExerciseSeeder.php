<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Category;
use App\Models\Exercise;

class CategoryAndExerciseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. الفئات العشر المقترحة مع صور افتراضية اختيارية
        $categoriesData = [
            ['name' => 'تمارين الصدر (Chest)', 'image' => 'categories/chest.jpg'],
            ['name' => 'تمارين الظهر (Back)', 'image' => 'categories/back.jpg'],
            ['name' => 'تمارين الأرجل (Legs)', 'image' => 'categories/legs.jpg'],
            ['name' => 'تمارين الأكتاف (Shoulders)', 'image' => 'categories/shoulders.jpg'],
            ['name' => 'تمارين الذراعين (Arms)', 'image' => 'categories/arms.jpg'],
            ['name' => 'تمارين البطن (Abs)', 'image' => 'categories/abs.jpg'],
            ['name' => 'تمارين كارديو (Cardio)', 'image' => 'categories/cardio.jpg'],
            ['name' => 'تمارين التحمل (Endurance)', 'image' => 'categories/endurance.jpg'],
            ['name' => 'تمارين الإطالة (Stretching)', 'image' => 'categories/stretching.jpg'],
            ['name' => 'تمارين القوة (Strength)', 'image' => 'categories/strength.jpg'],
        ];

        // 2. تمارين مقترحة لكل فئة (4 تمارين لكل فئة)
        $exercisesBycategory = [
            'تمارين الصدر (Chest)' => [
                ['name' => 'ضغط الصدر بالبار مستوي', 'description' => 'تمرين أساسي لبناء كتلة وعضلات الصدر الكلية.', 'target_muscles' => 'الصدر السفلي والأوسط، الترايبس'],
                ['name' => 'ضغط الصدر بالدمبل مائل', 'description' => 'يركز بشكل أكبر على الجزء العلوي لعضلات الصدر.', 'target_muscles' => 'الصدر العلوي'],
                ['name' => 'تفتيح الصدر بالدمبل', 'description' => 'يعمل على مد وعزل ألياف عضلات الصدر بفاعلية.', 'target_muscles' => 'عضلات الصدر الداخلية'],
                ['name' => 'متوازي (Chest Dips)', 'description' => 'تمرين ممتاز لوزن الجسم لتضخيم الجزء السفلي للصدر.', 'target_muscles' => 'الصدر السفلي، الترايبس'],
            ],
            'تمارين الظهر (Back)' => [
                ['name' => 'سحب عالي (Lat Pulldown)', 'description' => 'يستهدف عضلات الظهر العريضة لإعطاء شكل V.', 'target_muscles' => 'العضلات العريضة (Lats)'],
                ['name' => 'سحب أرضي بالسلك', 'description' => 'يقوي سمك عضلات الظهر الوسطى.', 'target_muscles' => 'الظهر الأوسط، البايسبس'],
                ['name' => 'سحب بار منحنٍ (Barbell Row)', 'description' => 'تمرين مركب وقوي لبناء قوة وكتلة الظهر.', 'target_muscles' => 'الظهر بالكامل، التراابيز'],
                ['name' => 'عقبة (Pull-ups)', 'description' => 'تمرين بوزن الجسم لبناء الظهر والذراعين.', 'target_muscles' => 'الظهر العلوي، البايسبس'],
            ],
            'تمارين الأرجل (Legs)' => [
                ['name' => 'سكوات بالبار (Barbell Squat)', 'description' => 'الملك الأبدي لتمارين الجزء السفلي والجسم كله.', 'target_muscles' => 'المخمسات الأمامية، المؤخرة'],
                ['name' => 'بثق الأرجل (Leg Press)', 'description' => 'تمرين آمن نسبياً لتح-ميل أوزان ثقيلة على الأرجل.', 'target_muscles' => 'الأرجل الأمامية'],
                ['name' => 'الرفعة الميتة الرومانية (Romanian Deadlift)', 'description' => 'يستهدف العضلات الخلفية للأرجل والمؤخرة.', 'target_muscles' => 'الخلفيات، أسفل الظهر'],
                ['name' => 'مرجحة السمانة واقفاً', 'description' => 'لتضخيم وتقوية عضلات السمانة.', 'target_muscles' => 'عضلات السمانة (Calves)'],
            ],
            'تمارين الأكتاف (Shoulders)' => [
                ['name' => 'ضغط الكتف بالدمبل', 'description' => 'يستهدف الكتف الأمامي والجانبي بشكل رئيسي.', 'target_muscles' => 'الأكتاف الأمامية والجانبية'],
                ['name' => 'رفرفة جانبي بالدمبل', 'description' => 'التمرين الأساسي لعرض الأكتاف وإبراز الجانب.', 'target_muscles' => 'الكتف الجانبي'],
                ['name' => 'رفرفة أمامي', 'description' => 'يعزل الجزء الأمامي لعضلات الكتف.', 'target_muscles' => 'الكتف الأمامي'],
                ['name' => 'رفرفة خلفي كيبل', 'description' => 'يقوي الكتف الخلفي ويحسن وضعية الجسم.', 'target_muscles' => 'الكتف الخلفي'],
            ],
            'تمارين الذراعين (Arms)' => [
                ['name' => 'مرجحة البايسبس بالبار', 'description' => 'التمرين الأساسي لتضخيم عضلات البايسبس.', 'target_muscles' => 'البايسبس'],
                ['name' => 'دفع الترايبس بالحبل (Pushdown)', 'description' => 'يعمل على إبراز تفاصيل عضلات الترايبس الخلفية.', 'target_muscles' => 'الترايبس'],
                ['name' => 'مرجحة مطرقة (Hammer Curl)', 'description' => 'يقوي عضلة البايسبس والساعد معاً.', 'target_muscles' => 'البايسبس والساعد'],
                ['name' => 'ترايبس بالبار خلف الرأس', 'description' => 'يمدد ويعمل على الرأس الطويل للترايبس.', 'target_muscles' => 'الترايبس'],
            ],
            'تمارين البطن (Abs)' => [
                ['name' => 'تمرين المعدة التقليدي (Crunches)', 'description' => 'يستهدف الجزء العلوي لعضلات البطن.', 'target_muscles' => 'البطن العلوي'],
                ['name' => 'رفع الأرجل معلقة', 'description' => 'من أقوى تمارين الجزء السفلي للبطن.', 'target_muscles' => 'البطن السفلي'],
                ['name' => 'بلانك (Plank)', 'description' => 'يثبت عضلات الجذع والبطن العميقة.', 'target_muscles' => 'الجذع والبطن بالكامل'],
                ['name' => 'تويست روسي (Russian Twist)', 'description' => 'يستهدف عضلات البطن الجانبية (الخواصر).', 'target_muscles' => 'الخواصر والأطراف'],
            ],
            'تمارين كارديو (Cardio)' => [
                ['name' => 'جري على جهاز المشي (Treadmill)', 'description' => 'يحسن صحة القلب ويحرق سعرات حرارية عالية.', 'target_muscles' => 'كامل الجسم، القلب'],
                ['name' => 'دراجة ثابتة (Stationary Bike)', 'description' => 'تمرين كارديو منخفض التأثير على المفاصل.', 'target_muscles' => 'الأرجل، القلب'],
                ['name' => 'نط الحبل', 'description' => 'يرفع نبضات القلب بسرعة ويحرق دهون مكثفة.', 'target_muscles' => 'السمانة، التناسق العام'],
                ['name' => 'جهاز الإليبتيكال', 'description' => 'يحاكي الجري بدون صدمات قوية على الركبتين.', 'target_muscles' => 'الجزء العلوي والسفلي'],
            ],
            'تمارين التحمل (Endurance)' => [
                ['name' => 'قفز القرفصاء (Jump Squat)', 'description' => 'يبني التحمل والقوة الانفجارية في الأرجل.', 'target_muscles' => 'الأرجل، التحمل العام'],
                ['name' => 'متسلق الجبال (Mountain Climbers)', 'description' => 'يرفع اللياقة والتحمل العضلي بسرعة.', 'target_muscles' => 'البطن، الأكتاف، القلب'],
                ['name' => 'تمارين بيربي (Burpees)', 'description' => 'تمرين شامل لرفع معدل ضربات القلب والتحمل.', 'target_muscles' => 'كامل الجسم'],
                ['name' => 'المشي السريع بميل', 'description' => 'يبني تحمل العضلات على المدى الطويل.', 'target_muscles' => 'الأرجل، التحمل'],
            ],
            'تمارين الإطالة (Stretching)' => [
                ['name' => 'إطالة أوتار الركبة', 'description' => 'يقلل التشنجات ويزيد مرونة الجزء الخلفي للأرجل.', 'target_muscles' => 'الخلفيات'],
                ['name' => 'إطالة عضلات الصدر', 'description' => 'يفتح الكتفين ويخفف الضغط على الصدر.', 'target_muscles' => 'الصدر والأكتاف'],
                ['name' => 'إطالة أسفل الظهر', 'description' => 'يرخي عضلات الظهر ويخفف آلام الجلوس الطويل.', 'target_muscles' => 'أسفل الظهر'],
                ['name' => 'إطالة الفخذين الأمامية', 'description' => 'يحسن مرونة الفخذين بعد التمرين.', 'target_muscles' => 'المخمسات الأمامية'],
            ],
            'تمارين القوة (Strength)' => [
                ['name' => 'الرفعة الميتة التقليدية (Deadlift)', 'description' => 'الملك المطلق لتمارين بناء القوة والكتلة.', 'target_muscles' => 'كامل الجسم الخلفي'],
                ['name' => 'كلين آند جيرك (Clean and Jerk)', 'description' => 'تمرين أولمبي يدمج القوة والسرعة والتوافق.', 'target_muscles' => 'كامل الجسم'],
                ['name' => 'دفع الأثقال فوق الرأس (Overhead Press)', 'description' => 'يبني قوة الكتفين والجذع والصلابة العضلية.', 'target_muscles' => 'الأكتاف والجذع'],
                ['name' => 'باربيل شق (Barbell Shrug)', 'description' => 'يرفع من قدرة وزين العنق والكتفين العلوية.', 'target_muscles' => 'عضلات التراابيز'],
            ],
        ];

        // 3. تخزين الفئات وإضافة التمارين الخاصة بكل فئة
        foreach ($categoriesData as $catData) {
            $category = Category::create([
                'name'  => $catData['name'],
                'image' => $catData['image'],
            ]);

            // التحقق من وجود تمارين مخصصة لهذه الفئة وإضافتها
            if (isset($exercisesBycategory[$catData['name']])) {
                foreach ($exercisesBycategory[$catData['name']] as $exercise) {
                    Exercise::create([
                        'category_id'    => $category->id,
                        'name'           => $exercise['name'],
                        'description'    => $exercise['description'],
                        'target_muscles' => $exercise['target_muscles'],
                        'video_url'      => 'https://youtu.be/1-9A3U9QHLM?si=THHn84L3MNly2rlY', // رابط افتراضي
                    ]);
                }
            }
        }
    }
}