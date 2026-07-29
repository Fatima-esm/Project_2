<?php

namespace App\Http\Controllers\Admin\Coach;

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

        // التحقق: هل المستخدم كوتش أو موظف استقبال؟
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

    //عرض جميع الكوتش
    public function getCoachesList( )
    {
        $coaches = User::where('role', 'coach')
            ->with('workSchedules') 
            ->get()
            ->map(function ($coach) {
                $traineesCount = User::where('coach_id', $coach->id)->count();

                $schedules = $coach->workSchedules->map(function ($schedule) {
                    return [
                        'days' => $schedule->days,
                        'work_name' => $schedule->work_name,
                        'start_time' => $schedule->start_time,
                        'end_time' => $schedule->end_time,
                    ];
                });

                return [
                    'id' => $coach->id,
                    'membership_number' => $coach->membership_number, 
                    'full_name' => $coach->full_name,                 
                    'phone' => $coach->phone,                         
                    'trainees_count' => $traineesCount,               
                    'work_schedules' => $schedules,                   
                ];
            });

        return response()->json([
            'status' => 200,
            'message' => 'تم جلب قائمة الكوتشات بنجاح',
            'data' => $coaches
        ]);
    }

    //details coach

    public function coachDetails($id)
    {
        $coach = User::with('workSchedules')
            ->where('role', 'coach')
            ->findOrFail($id);
        $traineesCount = User::where('coach_id', $coach->id)->count();

        return response()->json([
            'status' => 200,
            'message' => 'تم جلب تفاصيل الكوتش بنجاح',
            'data' => [
                'traineesCount'=>$traineesCount,
                'coachs' => $coach,
            ]
        ]);
    }

    //المتدربين عند الكوتش
    public function getCoachTrainees($coach_id)
    {
        // التأكد أن المستخدم موجود وهو كوتش بالفعل
        $coach = User::where('id', $coach_id)
            ->where('role', 'coach')
            ->first();

        if (!$coach) {
            return response()->json([
                'status' => 404,
                'message' => 'الكوتش غير موجود.'
            ], 404);
        }

        // جلب المتدربين الذين لديهم coach_id يطابق هذا الكوتش
        $trainees = User::where('coach_id', $coach->id)
            ->select('id', 'full_name', 'phone', 'email', 'status', 'created_at') // اختر الحقول التي تريدها للمتدرب
            ->get();

        return response()->json([
            'status' => 200,
            'message' => 'تم جلب متدربي الكوتش بنجاح',
            'coach' => [
                'id' => $coach->id,
                'full_name' => $coach->full_name,
                'membership_number' => $coach->membership_number,
                'total_trainees' => $trainees->count(),
            ],
            'trainees' => $trainees
        ]);
    }


    //


    //



}