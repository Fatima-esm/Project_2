<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\Salary;
use Carbon\Carbon;

class GenerateMonthlySalaries extends Command
{
    protected $signature = 'salaries:generate {--month=}';
    protected $description = 'توليد رواتب الموظفين والكوتشز تلقائياً (خطة العمل أولاً ثم الدور)';

    public function handle()
    {
        $month = $this->option('month') ?? Carbon::now()->format('Y-m');
        $this->info("توليد رواتب شهر: {$month}");

        $employees = User::whereIn('role', ['coach', 'reception'])
            ->with('workSchedules')
            ->get();

        $created = 0;
        $skipped = 0;

        foreach ($employees as $employee) {
            // تجنب التكرار
            $exists = Salary::where('user_id', $employee->id)
                            ->where('month', $month)
                            ->exists();

            if ($exists) {
                $this->line("تخطي {$employee->full_name} - راتب موجود مسبقاً");
                $skipped++;
                continue;
            }

            // 1) الراتب من خطة العمل إن وُجد
            $schedule = $employee->workSchedules->first();
            $baseSalary = $schedule?->base_salary ?? 0;
            $source = 'خطة العمل: ' . ($schedule->work_name ?? '');

            // 2) Fallback حسب الدور
            if ($baseSalary <= 0) {
                $baseSalary = match ($employee->role) {
                    'coach'     => 800.00,
                    'reception' => 500.00,
                    default     => 400.00,
                };
                $source = 'حسب الدور (' . $employee->role . ')';
            }

            Salary::create([
                'user_id'     => $employee->id,
                'base_salary' => $baseSalary,
                'bonus'       => 0,
                'deduction'   => 0,
                'net_salary'  => $baseSalary,
                'month'       => $month,
                'status'      => 'pending',
                'notes'       => 'راتب تلقائي - ' . $source,
            ]);

            $this->info("✓ {$employee->full_name} → {$baseSalary} ({$source})");
            $created++;
        }

        $this->newLine();
        $this->info("تم إنشاء: {$created} | تم التخطي: {$skipped}");

        return Command::SUCCESS;
    }
}