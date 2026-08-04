<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Salary;
use App\Models\User;
use Carbon\Carbon;
use App\Models\WorkSchedule;

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
            'base_salary' => 'nullable|numeric|min:0',
            'bonus'       => 'nullable|numeric|min:0',
            'deduction'   => 'nullable|numeric|min:0',
            'month'       => 'nullable|string|max:7', // اختيارياً، إذا لم يُرسل يتم أخذ الشهر الحالي تلقائياً
            'status'      => 'nullable|in:pending,paid',
            'notes'       => 'nullable|string|max:500', 
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $targetUser = User::find($request->user_id);
        if ($targetUser->role === 'trainee') {
            return response()->json(['message' => 'لا يمكن إضافة راتب للمتدربين'], 400);
        }

        // تحديد الشهر تلقائياً للشهر الحالي إذا لم يتم إرساله في الطلب
        $month = $request->month ?? Carbon::now()->format('Y-m');

        $existingSalary = Salary::where('user_id', $request->user_id)
                                ->where('month', $month)
                                ->first();

        $base      = $request->has('base_salary') ? $request->base_salary : ($existingSalary->base_salary ?? 0);
        $bonus     = $request->has('bonus') ? $request->bonus : ($existingSalary->bonus ?? 0);
        $deduction = $request->has('deduction') ? $request->deduction : ($existingSalary->deduction ?? 0);
        $status = $request->has('status') ? $request->status : ($existingSalary->status ?? 'pending');

        $netSalary = ($base + $bonus) - $deduction;

        $salary = Salary::updateOrCreate(
            [
                'user_id' => $request->user_id,
                'month'   => $month,
            ],
            [
                'base_salary' => $base,
                'bonus'       => $bonus,
                'deduction'   => $deduction,
                'net_salary'  => $netSalary,
                'status'      => $status,
                'notes'       => $request->notes ?? ($existingSalary->notes ?? null),
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

    public function assignSalaryByWorkSchedule(Request $request)
    {
        $admin = auth()->user();
        if ($admin->role !== 'admin') {
            return response()->json(['message' => 'غير مصرح لك بالوصول، هذه الصلاحية للأدمن فقط'], 403);
        }

        $validator = Validator::make($request->all(), [
            'role'             => 'required|in:coach,reception',
            'work_schedule_id' => 'required|exists:work_schedules,id',
            'monthly_salary'   => 'required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $role             = $request->role;
        $workScheduleId   = $request->work_schedule_id;
        $monthlySalary    = $request->monthly_salary;
        
        $month = Carbon::now()->format('Y-m'); 

        // 1. جلب تفاصيل خطة العمل (Work Schedule) من الجدول الخاص بها
        $workSchedule = WorkSchedule::find($workScheduleId);

        // 2. جلب المستخدمين المطابقين للدور والمرتبطين بجدول العمل
        $employees = User::where('role', $role)
                         ->whereHas('schedules', function ($query) use ($workScheduleId) {
                             $query->where('work_schedule_id', $workScheduleId);
                         })
                         ->get();

        if ($employees->isEmpty()) {
            return response()->json([
                'status' => 404,
                'message' => 'لا يوجد موظفون أو كوتشز مطابقين لهذا الدور ومرتبطين بخطة العمل المحددة'
            ], 404);
        }

        $updatedCount = 0;
        $affectedUsersList = []; // مصفوفة لتخزين تفاصيل المستخدمين (الاسم والـ ID)

        foreach ($employees as $employee) {
            // التحقق مما إذا كان السجل موجوداً مسبقاً للحفاظ على حالته أو ضبطه كـ pending إذا كان جديداً
            $existingSalary = Salary::where('user_id', $employee->id)
                                    ->where('month', $month)
                                    ->first();

            $status = $existingSalary ? $existingSalary->status : 'pending';

            Salary::updateOrCreate(
                [
                    'user_id' => $employee->id,
                    'month'   => $month,
                ],
                [
                    'base_salary' => $monthlySalary,
                    'net_salary'  => \DB::raw("base_salary + bonus - deduction"), 
                    'status'      => $status, // إبقاء الحالة السابقة أو تعيينها كـ 'pending' افتراضياً للسجلات الجديدة
                    'notes'       => "تم تحديث الراتب الأساسي بناءً على خطة العمل رقم: {$workScheduleId}",
                ]
            );

            // إضافة تفاصيل المستخدم للقائمة
            $affectedUsersList[] = [
                'id'        => $employee->id,
                'full_name' => $employee->full_name,
                'role'      => $employee->role,
            ];

            $updatedCount++;
        }

        return response()->json([
            'status'  => 200,
            'message' => "تم تحديد الراتب الشهري بنجاح على (" . $updatedCount . ") من الـ " . ($role === 'coach' ? 'مدربين' : 'موظفي الاستقبال'),
            'data'    => [
                'monthly_salary'   => $monthlySalary,
                'month'            => $month,
                // تفاصيل خطة العمل
                'work_schedule_details' => [
                    'id'         => $workSchedule->id,
                    'days'       => $workSchedule->days,
                    'work_name'  => $workSchedule->work_name,
                    'start_time' => $workSchedule->start_time,
                    'end_time'   => $workSchedule->end_time,
                ],
                // قائمة بأسماء وأرقام المستخدمين الذين تم تطبيق الراتب عليهم
                'affected_users_count' => $updatedCount,
                'users'                => $affectedUsersList,
            ]
        ], 200);
    }

    // إتمام دفع الراتب لموظف أو كوتش معين وتحديد طريقة الدفع
    public function paySalary(Request $request, $salaryId)
    {
        $admin = auth()->user();
        if ($admin->role !== 'admin') {
            return response()->json(['message' => 'غير مصرح لك بالوصول، هذه الصلاحية للأدمن فقط'], 403);
        }

        $validator = Validator::make($request->all(), [
            'payment_method' => 'required|string|max:100', // طريقة الدفع (نقداً، تحويل بنكي، بطاقة، إلخ)
            'notes'          => 'nullable|string|max:500',  // ملاحظات إضافية عملية الدفع
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $salaryRecord = Salary::find($salaryId);
        if (!$salaryRecord) {
            return response()->json(['status' => 404, 'message' => 'سجل الراتب غير موجود'], 404);
        }

        // التحقق مما إذا كان الراتب مدفوعاً مسبقاً
        if ($salaryRecord->status === 'paid') {
            return response()->json(['status' => 400, 'message' => 'هذا الراتب تم دفعُه مسبقاً بالفعل'], 400);
        }

        // تحديث حالة الراتب إلى مدفوع، وتحديد طريقة الدفع، ووقت الدفع الحالي
        $salaryRecord->update([
            'status'         => 'paid',
            'payment_method' => $request->payment_method,
            'paid_at'        => Carbon::now(), // تاريخ ووقت عملية الدفع
            'notes'          => $request->notes ?? $salaryRecord->notes,
        ]);

        $employee = User::find($salaryRecord->user_id);

        return response()->json([
            'status'  => 200,
            'message' => 'تم إتمام دفع الراتب بنجاح وتحديث حالته إلى مدفوع',
            'data'    => [
                'employee_name'  => $employee->full_name,
                'role'           => $employee->role,
                'salary_details' => $salaryRecord
            ]
        ], 200);
    }

    //تفاصيل رواتب الموظفين أو الكوتشز بناءً على خطة العمل والدور والشهر
    // عرض جميع الموظفين مع الفلترة حسب الدور وحالة الراتب وكافة التفاصيل المطلوبة
    public function getAllEmployeesSalaries(Request $request)
    {
        $admin = auth()->user();
        if ($admin->role !== 'admin') {
            return response()->json(['message' => 'غير مصرح لك بالوصول، هذه الصلاحية للأدمن فقط'], 403);
        }

        $validator = Validator::make($request->all(), [
            'role'   => 'nullable|in:coach,reception', // فلترة حسب الدور
            'status' => 'nullable|in:pending,paid',      // فلترة حسب حالة الراتب
            'month'  => 'nullable|string|max:7',         // فلترة اختياري لشهر معين (مثلاً "2026-08")
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $role   = $request->role;
        $status = $request->status;
        $month  = $request->month ?? Carbon::now()->format('Y-m'); // الشهر الحالي افتراضياً

        // جلب المستخدمين الذين ليسوا متدربين (أي كوتش أو موظف استقبال)
        $query = User::whereIn('role', ['coach', 'reception']);

        // تطبيق فلترة الدور إذا تم إرساله
        if ($role) {
            $query->where('role', $role);
        }

        $employees = $query->with([
            'schedules.workSchedule', // لجلب جدول العمل المرتبط بالمستخدم
            'salaries' => function ($q) use ($month, $status) {
                $q->where('month', $month);
                if ($status) {
                    $q->where('status', $status);
                }
            }
        ])->get();

        $result = [];

        foreach ($employees as $employee) {
            // إذا كانت فلترة الحالة مفعلة والموظف لا يملك سجل راتب بهذا الشهر/الحالة، نتخطاه
            $salary = $employee->salaries->first();
            if ($status && !$salary) {
                continue;
            }

            // استخراج تفاصيل خطة العمل إن وجدت
            $scheduleDetails = null;
            if ($employee->schedules && $employee->schedules->isNotEmpty()) {
                // نأخذ جدول العمل الأول المرتبط به كمثال
                $workSchedule = $employee->schedules->first()->workSchedule;
                if ($workSchedule) {
                    $scheduleDetails = [
                        'work_name' => $workSchedule->work_name,
                        'days'      => $workSchedule->days,
                    ];
                }
            }

            $result[] = [
                'employee_id'    => $employee->id,
                'employee_name'  => $employee->full_name,
                'role'           => $employee->role,
                'work_schedule'  => $scheduleDetails ?? [
                    'work_name' => 'غير متوفر',
                    'days'      => 'غير متوفر',
                ],
                'salary_id'      => $salary->id ?? null,
                'base_salary'    => $salary->base_salary ?? 0,
                'bonus'          => $salary->bonus ?? 0,
                'deduction'      => $salary->deduction ?? 0,
                'net_salary'     => $salary->net_salary ?? 0,
                'month'          => $month,
                'status'         => $salary->status ?? 'pending', // افتراضياً قيد الانتظار إذا لم يتم إنشاء سجل بعد
                'notes'          => $salary->notes ?? null,];
        }

        return response()->json([
            'status'  => 200,
            'message' => 'تم جلب بيانات رواتب الموظفين بنجاح',
            'count'   => count($result),
            'data'    => $result
        ], 200);
    }


}
