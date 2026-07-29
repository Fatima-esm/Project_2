<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run()
    {
        // إعادة تعيين الكاش الخاص بالصلاحيات (مهم جداً)
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Permissions
        
        // صلاحيات الإدارة (Admin)
        $permissions = [
            'manage coaches',      // قبول ورفض المدربين
            'manage staff',        // إضافة موظفي الاستقبال
            'view financial reports', // التقارير المالية
            'manage plans',        // تعديل الباقات والأسعار
            'manage inventory',    // إضافة منتجات المتجر ومراقبة المخزون
            
            // صلاحيات الاستقبال (Receptionist)
            'check-in trainees',   // مسح QR والدخول
            'manual payments',     // تجديد الاشتراك نقداً
            'sell products',       // نظام الـ POS
            'freeze subscriptions', // تجميد الاشتراك
            
            // صلاحيات المدرب (Coach)
            'manage workouts',     // وضع جداول التمارين
            'view assigned trainees', // رؤية المتدربين الخاصين به
            
            // صلاحيات المتدرب (Trainee)
            'purchase products',   // الشراء من المتجر
            'access AI chatbot',   // استخدام المساعد الذكي
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        //.........................................................................................................

        // 2. إنشاء الأدوار وتوزيع الصلاحيات (Roles)

        // دور المسؤول (Admin) - له كل الصلاحيات
        $adminRole = Role::create(['name' => 'admin']);
        $adminRole->givePermissionTo(Permission::all());

        // دور موظف الاستقبال (Receptionist)
        $receptionRole = Role::create(['name' => 'reception']);
        $receptionRole->givePermissionTo([
            'check-in trainees',
            'manual payments',
            'sell products',
            'freeze subscriptions'
        ]);

        // دور المدرب (Coach)
        $coachRole = Role::firstOrCreate(['name' => 'coach']);
        $coachRole->givePermissionTo([
            'manage workouts',
            'view assigned trainees'
        ]);

        // دور المتدرب (Trainee)
        $traineeRole = Role::firstOrCreate(['name' => 'trainee']);
        $traineeRole->givePermissionTo([
            'purchase products',
            'access AI chatbot'
        ]);
        
  
    }
}