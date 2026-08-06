<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Salary;
use Carbon\Carbon;

class SalarySeeder extends Seeder
{
    public function run(): void
    {
        $employees = User::whereIn('role', ['coach', 'reception'])->get();

        if ($employees->isEmpty()) {
            $this->command->warn('لا يوجد موظفين أو كوتشز.');
            return;
        }

        $month = Carbon::now()->format('Y-m');
        $count = 0;

        foreach ($employees as $employee) {
            $baseSalary = match ($employee->role) {
                'coach'     => 800.00,
                'reception' => 500.00,
                default     => 400.00,
            };

            $bonus     = 50.00;
            $deduction = 0.00;
            $netSalary = ($baseSalary + $bonus) - $deduction;

            Salary::updateOrCreate(
                [
                    'user_id' => $employee->id,
                    'month'   => $month,
                ],
                [
                    'base_salary'    => $baseSalary,
                    'bonus'          => $bonus,
                    'deduction'      => $deduction,
                    'net_salary'     => $netSalary,
                    'status'         => 'pending',
                    'notes'          => 'راتب لشهر ' . $month,
                ]
            );

            $count++;
        }

        $this->command->info("تم إنشاء/تحديث {$count} سجل راتب لشهر {$month}");
    }
}