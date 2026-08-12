<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Product;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $products = [
            [
                'name'           => 'دمبل قابل للتعديل مع بار',
                'price'          => 180.00,
                'stock_quantity' => 15,
                'description'    => 'طقم دمبل قابل لتعديل الأوزان مع بار تمرين، مناسب لتقوية العضلات في المنزل والنادي.',
                'image'          => 'products/1.png',
            ],
            [
                'name'           => 'طقم بار تمارين مع أوزان 30',
                'price'          => 200.00,
                'stock_quantity' => 10,
                'description'    => 'بار مستقيم وبار EZ مع أوزان 30 كغ، مثالي لتمارين الصدر والذراعين والظهر.',
                'image'          => 'products/2.png',
            ],
            [
                'name'           => 'جهاز مشي كهربائي احترافي',
                'price'          => 1500.00,
                'stock_quantity' => 5,
                'description'    => 'جهاز مشي كهربائي بشاشة عرض ومقابض أمان، مناسب للجري والمشي اليومي.',
                'image'          => 'products/3.png',
            ],
            [
                'name'           => 'جهاز مشي منزلي قابل للطي',
                'price'          => 900.00,
                'stock_quantity' => 8,
                'description'    => 'جهاز مشي مدمج بمقبض قابل للتعديل، خفيف وسهل التخزين في المنزل.',
                'image'          => 'products/4.png',
            ],
            [
                'name'           => 'جهاز مشي مكتبي صغير',
                'price'          => 650.00,
                'stock_quantity' => 12,
                'description'    => 'مشاية مسطحة للاستخدام تحت المكتب أو في المساحات الصغيرة، هادئة وسهلة النقل.',
                'image'          => 'products/5.png',
            ],
            [
                'name'           => 'سجادة يوغا وتمرين',
                'price'          => 55.00,
                'stock_quantity' => 40,
                'description'    => 'سجادة يوغا مزدوجة الوجه مانعة للانزلاق، مثالية للتمارين الأرضية والإطالة.',
                'image'          => 'products/6.png',
            ],
            [
                'name'           => 'جهاز صعود الدرج الرياضي',
                'price'          => 2200.00,
                'stock_quantity' => 4,
                'description'    => 'جهاز ستيبر احترافي بشاشة لمس، لتقوية الساقين وتحسين اللياقة القلبية.',
                'image'          => 'products/7.png',
            ],
            [
                'name'           => 'ميزان جسم رقمي',
                'price'          => 70.00,
                'stock_quantity' => 25,
                'description'    => 'ميزان إلكتروني زجاجي بدقة عالية لمتابعة الوزن بشكل يومي.',
                'image'          => 'products/8.png',
            ],
            [
                'name'           => 'حبل قفز رياضي',
                'price'          => 35.00,
                'stock_quantity' => 50,
                'description'    => 'حبل قفز بمقابض مريحة، ممتاز لتمارين الكارديو وحرق الدهون.',
                'image'          => 'products/9.png',
            ],
            [
                'name'           => 'زجاجة ماء رياضية',
                'price'          => 25.00,
                'stock_quantity' => 60,
                'description'    => 'زجاجة ماء بسعة كبيرة وغطاء محكم، مناسبة للتمرين والنشاط اليومي.',
                'image'          => 'products/10.png',
            ],
        ];

        foreach ($products as $product) {
            Product::updateOrCreate(
                ['name' => $product['name']],
                $product
            );
        }
    }
}