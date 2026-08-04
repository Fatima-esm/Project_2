<?php

namespace App\Http\Controllers\Coach;

use App\Http\Controllers\Controller;
use App\Models\WorkSchedule;
use App\Models\User;
use Illuminate\Http\Request;
use Spatie\Permission\Traits\HasRoles;  //for role and permission

class CoachScheduleController extends Controller
{
    // عرض جداول العمل المتاحة
    public function index()
    {
        $schedules = WorkSchedule::all();
        return response()->json($schedules);
    }

    //انشاء موعد عمل جديد
    public function store(Request $request)
    {
        $request->validate([
            'days'        => 'required',
            'work_name'  => 'required|string|max:100',
            'start_time' => 'required|date_format:H:i',
            'end_time'   => 'required|date_format:H:i|after:start_time',
        ]);

        $schedule = WorkSchedule::create($request->all());

        return response()->json([
            'message' => 'تم إضافة خطة العمل بنجاح',
            'data'    => $schedule
        ], 201);
    }

    // حذف خطة عمل (جدول زمني)
    public function destroy($schedule_id)
    {
        $schedule = WorkSchedule::findOrFail($schedule_id);
        $schedule->delete();

        return response()->json([
            'message' => 'تم حذف خطة العمل بنجاح'
        ]);
    }

    // عرض مواعيد عمل موظف معين
    public function showStaffSchedules($user_id)
    {
        $user = User::findOrFail($user_id);

        // الفحص المباشر على عمود role في جدول users
        if (!in_array($user->role, ['coach', 'reception'])) {
            return response()->json([
                'message' => 'هذا المستخدم ليس كوتش أو موظف استقبال',
            ], 422);
        }

        return response()->json([
            'user' => $user->full_name,
            'role' => $user->role,
            'schedules' => $user->workSchedules()->get()
        ]);
    }    

    // إضافة أيام عمل لكوتش معين (من الادمن)
    public function assignSchedule(Request $request)
    {
        $request->validate([
            'user_id'      => 'required|exists:users,id',
            'schedule_ids' => 'required|string',
        ]);

        $user = User::findOrFail($request->user_id);

        if (!in_array($user->role, ['coach', 'reception'])) {
            return response()->json([
                'message' => 'لا يمكن تعيين جداول عمل إلا للكوتش أو موظف الاستقبال',
                'error'   => 'invalid_role'
            ], 422);
        }

        $scheduleIds = array_map('trim', explode(',', $request->schedule_ids));
        $scheduleIds = array_filter($scheduleIds, fn($id) => is_numeric($id));

        $user->workSchedules()->sync($scheduleIds);

        return response()->json([
            'message' => 'تم تعيين جداول العمل بنجاح',
            'user' => $user->full_name,
            'role' => $user->role,
            'schedules' => $user->workSchedules()->get()
        ]);
    }

    // تعديل مواعيد عمل موظف
    public function updateStaffSchedules(Request $request, $user_id)
    {
        $request->validate([
            'schedule_ids' => 'required|string',
        ]);

        $user = User::findOrFail($user_id);

        if (!$user->role(['coach', 'reception'])) {
            return response()->json([
                'message' => 'لا يمكن تعديل جداول العمل إلا للكوتش أو موظف الاستقبال',
            ], 422);
        }

        $scheduleIds = array_map('trim', explode(',', $request->schedule_ids));
        $scheduleIds = array_filter($scheduleIds, fn($id) => is_numeric($id));

        $user->workSchedules()->sync($scheduleIds);

        return response()->json([
            'message' => 'تم تعديل جداول العمل بنجاح',
            'user' => $user->full_name,
            'schedules' => $user->workSchedules()->get()
        ]);
    }

    /**
     * عرض جميع الموظفين والكوتشز مع مواعيد عملهم وإمكانية الفلترة
     */
    public function getAllStaffWithSchedules(Request $request): \Illuminate\Http\JsonResponse
    {
        // بناء الاستعلام لجلب المستخدمين الذين لديهم دور كوتش أو موظف استقبال
        $query = User::whereIn('role', ['coach', 'reception'])
            ->with(['workSchedules' => function ($q) use ($request) {
                // الفلترة حسب اسم خطة العمل إذا تم تمريرها في الـ Request
                if ($request->filled('work_name')) {
                    $q->where('work_name', 'like', '%' . $request->work_name . '%');
                }
                // الفلترة حسب اليوم إذا تم تمريره في الـ Request
                if ($request->filled('day')) {
                    $q->where('days', 'like', '%' . $request->day . '%');
                }
            }]);

        // الفلترة حسب الدور (coach أو reception) إذا تم طلبه
        if ($request->filled('role')) {
            $query->where('role', $request->role);
        }

        $staffMembers = $query->get();

        // تنسيق البيانات لتظهر بشكل مرتب وواضح
        $result = $staffMembers->map(function ($user) {
            return [
                'id'        => $user->id,
                'full_name' => $user->full_name,
                'phone'     => $user->phone ?? 'غير متوفر', // تأكد من اسم حقل رقم الجوال في جدولك
                'role'      => $user->role,
                'schedules' => $user->workSchedules->map(function ($schedule) {
                    return [
                        'schedule_id' => $schedule->id,
                        'work_name'   => $schedule->work_name,
                        'days'        => $schedule->days,
                        'start_time'  => $schedule->start_time,
                        'end_time'    => $schedule->end_time,
                    ];
                }),
            ];
        });

        return response()->json([
            'status' => 200,
            'count'  => $result->count(),
            'data'   => $result
        ], 200);
    }


    //


    //



}