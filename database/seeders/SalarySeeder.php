<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class SalarySeeder extends Seeder
{
    public function run(): void
    {
        // 1. جلب الموظفين والكوتشز فقط (باستثناء المتدربين)
        $employees = User::whereIn('role', ['coach', 'reception'])->get();

        if ($employees->isEmpty()) {
            return;
        }

        foreach ($employees as $employee) {
            // 2. تحديد الراتب الأساسي بناءً على دور المستخدم
            $baseSalary = match ($employee->role) {
                'coach'     => 800.00,
                'reception' => 500.00,
                default     => 400.00,
            };

            // قيم افتراضية للمكافآت والخصومات (يمكنك جعلها 0 أو توليدها عشوائياً)
            $bonus = 50.00;     // مكافأة تجريبية
            $deduction = 0.00;  // خصم تجريبي

            // 3. حساب صافي الراتب (الأساسي + المكافآت - الخصومات)
            $netSalary = ($baseSalary + $bonus) - $deduction;

            // 4. إدخال أو تحديث سجل الراتب في الجدول بناءً على الموظف والشهر
            DB::table('salaries')->updateOrInsert(
                [
                    'user_id' => $employee->id,
                    'month'   => now()->format('Y-m'), 
                ],
                [
                    'base_salary'    => $baseSalary,
                    'bonus'          => $bonus,
                    'deduction'      => $deduction,
                    'net_salary'     => $netSalary,
                    'status'         => 'pending', // الحالة الافتراضية
                    'notes'          => 'راتب لشهر ' . now()->format('Y-m'),
                    'payment_method' => 'cash',
                    'created_at'     => now(),
                    'updated_at'     => now(),
                ]
            );
        }
    }
}