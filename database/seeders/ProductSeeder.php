<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Product; // تأكد من إنشاء نموذج Product إذا لم يكن موجوداً

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        // قائمة ببعض منتجات الجيم والرياضة لتبدو البيانات واقعية
        $gymProducts = [
            'م,شرب بروتين واي',
            'كرياتين نقي',
            'أحماض أمينية BCAA',
            'حزام رفع أثقال جلد',
            'أشرطة معصم للرفع',
            'قفازات تمارين حديد',
            'دمبل قابل للتعديل',
            'حبل مقاومة مطاطي',
            'سجادة تمارين يوغا',
            'زجاجة مياه رياضية (شيكر)',
            'حارق دهون ليدو',
            'فيتامين متعدد (Multi-Vitamin)',
            'طوق قفز للحبل',
            'أسطوانة فوم للمساج والعضلات',
            'حزام تخسيس للخصر',
        ];

        for ($i = 1; $i <= 25; $i++) {
            $productName = $gymProducts[array_rand($gymProducts)] . ' - طراز ' . $i;

            Product::create([
                'name'           => $productName,
                'price'          => rand(50, 200), // سعر عشوائي بين 15 و 300
                'stock_quantity' => rand(5, 40),  // كمية متوفرة عشوائية بين 5 و 100
                'description'    => 'منتج عالي الجودة مصمم خصيصاً لمساعدة الرياضيين على تحقيق أهدافهم في اللياقة البدنية وبناء العضلات.',
                'image'          => null,          
            ]);
        }
    }
}