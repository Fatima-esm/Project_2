<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Salary;
use App\Models\User;
use Illuminate\Support\Facades\Validator;

class SalaryController extends Controller
{
    // اضافة أو تحديث تفاصيل الراتب لموظف أو كوتش معين
    public function storeOrUpdateSalary(Request $request)
    {
        $admin = auth()->user();
        if ($admin->role !== 'admin') {
            return response()->json(['message' => 'غير مصرح لك بالوصول، هذه الصلاحية للأدمن فقط'], 403);
        }

        $validator = Validator::make($request->all(), [
            'user_id'     => 'required|exists:users,id',
            'base_salary' => 'required|numeric|min:0',
            'bonus'       => 'nullable|numeric|min:0',
            'deduction'   => 'nullable|numeric|min:0',
            'month'       => 'required|string|max:7', // صيغة الشهر مثلاً "2026-08"
            'notes'       => 'nullable|string|max:500', // سبب المكافأة أو الخصم الإجباري
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // التأكد أن المستخدم ليس متدرباً (مسموح للموظفين والكوتشز والأدمن)
        $targetUser = User::find($request->user_id);
        if ($targetUser->role === 'trainee') {
            return response()->json(['message' => 'لا يمكن إضافة راتب للمتدربين'], 400);
        }

        // حساب الراتب الصافي تلقائياً
        $base      = $request->base_salary;
        $bonus     = $request->bonus ?? 0;
        $deduction = $request->deduction ?? 0;
        $netSalary = ($base + $bonus) - $deduction;

        // حفظ الراتب أو تحديثه إذا تم إدخاله مسبقاً لنفس الشهر
        $salary = Salary::updateOrCreate(
            [
                'user_id' => $request->user_id,
                'month'   => $request->month,
            ],
            [
                'base_salary' => $base,
                'bonus'       => $bonus,
                'deduction'   => $deduction,
                'net_salary'  => $netSalary,
                'notes'       => $request->notes,
            ]
        );

        return response()->json([
            'status'  => 200,
            'message' => 'تم حفظ وتحديث تفاصيل الراتب بنجاح',
            'data'    => [
                'employee_name' => $targetUser->full_name,
                'role'          => $targetUser->role,
                'salary_details'=> $salary
            ]
        ], 200);
    }

    // سجل الرواتب لموظف أو كوتش معين
    public function getEmployeeSalaries(Request $request, $userId)
    {
        $admin = auth()->user();
        if ($admin->role !== 'admin') {
            return response()->json(['message' => 'غير مصرح لك بالوصول'], 403);
        }

        $employee = User::find($userId);
        if (!$employee) {
            return response()->json(['message' => 'المستخدم غير موجود'], 404);
        }

        $salaries = Salary::where('user_id', $userId)
                        ->orderBy('month', 'desc')
                        ->get();

        return response()->json([
            'status'  => 200,
            'message' => 'تم جلب سجل الرواتب بنجاح',
            'employee' => [
                'id'        => $employee->id,
                'full_name' => $employee->full_name,
                'role'      => $employee->role,
            ],
            'data'    => $salaries
        ], 200);
    }

}
