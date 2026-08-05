<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\CoachProfile; // استدعاء موديل ملف الكوتش
use Illuminate\Support\Facades\Hash;

class CoachSeeder extends Seeder
{
    public function run(): void
    {
        // مصفوفة البيانات الأساسية للكوتشز مع التفاصيل الإضافية والسيرة الذاتية
        $coaches = [
            [
                'user' => [
                    'full_name'         => 'سمير احمد',
                    'email'             => 'sameer@gmail.com',
                    'phone'             => '963876054321',
                    'password'          => Hash::make('12345678'),
                    'role'              => 'coach',
                    'gender'            => 'ذكر',
                    'age'               => 30,
                    'status'            => 'active',
                    'active_at'         => 1,
                    'email_verified_at' => now(),
                    'membership_number' => 'SG-20011',
                ],
                'profile' => [
                    'years_of_experience'      => 5,
                    'about_me'                 => 'مدرب لياقة بدنية واختصاصي كمال أجسام بخبرة واسعة.',
                    'certificates_and_credits' => 'شهادة تدريب معتمدة من الاتحاد الدولي IFBB',
                    'cv_path'                  => 'cvs/sameer.pdf',
                ]
            ],
            [
                'user' => [
                    'full_name'         => 'علي الرفاعي',
                    'email'             => 'ali@gmail.com',
                    'phone'             => '963987654322',
                    'password'          => Hash::make('12345678'),
                    'role'              => 'coach',
                    'gender'            => 'ذكر',
                    'age'               => 30,
                    'status'            => 'active',
                    'active_at'         => 1,
                    'email_verified_at' => now(),
                    'membership_number' => 'SG-20012',
                ],
                'profile' => [
                    'years_of_experience'      => 4,
                    'about_me'                 => 'مدرب كروس فت وتخفيف وزن.',
                    'certificates_and_credits' => 'شهادة CrossFit Level 1',
                    'cv_path'                  => 'cvs/ali.pdf',
                ]
            ],
            [
                'user' => [
                    'full_name'         => 'فراس الاشقر',
                    'email'             => 'feras@gmail.com',
                    'phone'             => '963897654323',
                    'password'          => Hash::make('12345678'),
                    'role'              => 'coach',
                    'gender'            => 'ذكر',
                    'age'               => 35,
                    'status'            => 'active',
                    'active_at'         => 1,
                    'email_verified_at' => now(),
                    'membership_number' => 'SG-20013',
                ],
                'profile' => [
                    'years_of_experience'      => 8,
                    'about_me'                 => 'خبير تغذية رياضية ومدرب حديد.',
                    'certificates_and_credits' => 'دبلوم تغذية رياضية متقدمة',
                    'cv_path'                  => 'cvs/feras.pdf',
                ]
            ],
            [
                'user' => [
                    'full_name'         => 'احمد الاحمد',
                    'email'             => 'ahmad@gmail.com',
                    'phone'             => '09876504324',
                    'password'          => Hash::make('12345678'),
                    'role'              => 'coach',
                    'gender'            => 'ذكر',
                    'age'               => 40,
                    'status'            => 'active',
                    'active_at'         => 1,
                    'email_verified_at' => now(),
                    'membership_number' => 'SG-20014',
                ],
                'profile' => [
                    'years_of_experience'      => 10,
                    'about_me'                 => 'مدرب عام وتأهيل إصابات ملاعب.',
                    'certificates_and_credits' => 'بكالوريوس تربية رياضية + شهادة تأهيل إصابات',
                    'cv_path'                  => 'cvs/ahmad.pdf',
                ]
            ],
            [
                'user' => [
                    'full_name'         => 'سعد الاسعد',
                    'email'             => 'saad@gmail.com',
                    'phone'             => '963987654325',
                    'password'          => Hash::make('12345678'),
                    'role'              => 'coach',
                    'gender'            => 'ذكر',
                    'age'               => 45,
                    'status'            => 'pending',
                    'active_at'         => 0,
                    'email_verified_at' => now(),
                    'membership_number' => 'SG-20015',
                ],
                'profile' => [
                    'years_of_experience'      => 3,
                    'about_me'                 => 'مدرب معتمد.',
                    'certificates_and_credits' => 'شهادة تدريب معتمدة',
                    'cv_path'                  => 'cvs/saad.pdf',
                ]
            ],
            [
                'user' => [
                    'full_name'         => 'محمد اسماعيل',
                    'email'             => 'mohammad@gmail.com',
                    'phone'             => '963876504326',
                    'password'          => Hash::make('12345678'),
                    'role'              => 'coach',
                    'gender'            => 'ذكر',
                    'age'               => 50,
                    'status'            => 'pending',
                    'active_at'         => 0,
                    'email_verified_at' => now(),
                    'membership_number' => 'SG-20016',
                ],
                'profile' => [
                    'years_of_experience'      => 6,
                    'about_me'                 => 'مدرب معتمد.',
                    'certificates_and_credits' => 'شهادة تدريب معتمدة',
                    'cv_path'                  => 'cvs/mohammad.pdf',
                ]
            ],
        ];

        foreach ($coaches as $data) {
            // 1. إنشاء المستخدم الأساسي
            $user = User::create($data['user']);
            
            // تعيين دور الـ coach عبر حزمة Spatie
            if (method_exists($user, 'assignRole')) {
                $user->assignRole('coach');
            }

            // 2. إنشاء السيرة الذاتية والتفاصيل المرتبطة بـ coach_profiles باستخدام بيانات كل كوتش بدقة
            CoachProfile::create(array_merge([
                'user_id' => $user->id,
                'years_of_experience' => 5,
                'about_me' => 'مدرب معتمد.',
                'certificates_and_credits' => 'شهادة تدريب معتمدة',
            ], $data['profile']));
        }
    }
}