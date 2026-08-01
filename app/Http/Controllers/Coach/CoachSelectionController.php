<?php

namespace App\Http\Controllers\Coach;

use App\Http\Controllers\Controller;

use Illuminate\Http\Request;
use App\Models\WorkSchedule;
use App\Models\User;
use Spatie\Permission\Traits\HasRoles;  //for role and permission


class CoachSelectionController extends Controller
{
    //all coach

    public function index()
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
                    'email' => $coach->email,
                    'status' => $coach->status,
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

    public function show($id)
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

    // اختيار كوتش من قبل المتدرب
    public function selectCoach(Request $request)
    {
        $request->validate([
            'coach_id' => 'required|exists:users,id'
        ]);

        $user = $request->user();

        // التحقق عما إذا كان لديه كوتش مسبقاً
        if ($user->coach_id) {
            return response()->json([
                'status' => 400,
                'message' => 'لديه كوتش بالفعل. لا يمكنك اختيار كوتش جديد بشكل مباشر، يرجى تقديم طلب تغيير كوتش.'
            ], 400);
        }

        // 1. التأكد أن للمتدرب اشتراكاً نشطاً ومدفوعاً قبل اختيار الكوتش
        $hasActiveSubscription = $user->subscriptions()
            ->where('status', 'paid')
            ->where('expires_at', '>', now())
            ->exists();

        if (!$hasActiveSubscription) {
            return response()->json([
                'status' => 403,
                'message' => 'عذراً، لا يمكنك اختيار كوتش لعدم وجود اشتراك نشط حالياً.'
            ], 403);
        }

        $coachId = $request->coach_id;

        // 2. التحقق من أن المستخدم المختار هو "كوتش" وحسابه نشط
        $coach = User::where('id', $coachId)
            ->where('role', 'coach')
            ->where('status', 'active')
            ->first();

        if (!$coach) {
            return response()->json([
                'status' => 400,
                'message' => 'عذراً، هذا المستخدم ليس كوتش أو أن حسابه غير مفعل من الإدارة.'
            ], 400);
        }

        // 3. التحقق من عدد المتدربين الحاليين لهذا الكوتش (الحد الأقصى 20)
        $traineesCount = User::where('coach_id', $coachId)->count();

        if ($traineesCount >= 20) {
            return response()->json([
                'status' => 400,
                'message' => 'عذراً، لقد وصل هذا الكوتش إلى الحد الأقصى من المتدربين (20 متدرباً). يرجى اختيار كوتش آخر.'
            ], 400);
        }

        // 4. ربط المتدرب بالكوتش المختار
        $user->coach_id = $coachId;
        $user->save();

        return response()->json([
            'status' => 200,
            'message' => 'تم اختيار الكوتش بنجاح',
            'coach' => $coach->full_name
        ]);
    }

    //update coach
    public function requestChangeCoach(Request $request)
    {
        $request->validate([
            'coach_id' => 'required|exists:users,id'
        ]);

        $user = $request->user();

        // التأكد أن لديه كوتش أصلاً ليتمكن من طلب التغيير
        if (!$user->coach_id) {
            return response()->json([
                'status' => 400,
                'message' => 'ليس لديك كوتش حالياً لتطلب تغييره. يمكنك اختيار كوتش مباشرة.'
            ], 400);
        }

        // التأكد أنه ليس نفس الكوتش الحالي
        if ($user->coach_id == $request->coach_id) {
            return response()->json([
                'status' => 400,
                'message' => 'هذا هو كوتشك الحالي بالفعل.'
            ], 400);
        }

        // التحقق من أن الكوتش الجديد المطلوب صحيح ومفعل
        $newCoach = User::where('id', $request->coach_id)
            ->where('role', 'coach')
            ->where('status', 'active')
            ->first();

        if (!$newCoach) {
            return response()->json([
                'status' => 400,
                'message' => 'عذراً، الكوتش المطلوب غير موجود أو غير مفعل.'
            ], 400);
        }

        // التحقق من أن الكوتش الجديد لم يتجاوز 20 متدرباً
        $traineesCount = User::where('coach_id', $request->coach_id)->count();
        if ($traineesCount >= 20) {
            return response()->json([
                'status' => 400,
                'message' => 'عذراً، الكوتش المطلوب وصل للحد الأقصى من المتدربين (20 متدرباً).'
            ], 400);
        }

        // حفظ الطلب المؤقت بانتظار موافقة الإدارة
        $user->coach_id = $request->coach_id;
        $user->save();

        return response()->json([
            'status' => 200,
            'message' => 'تم إرسال طلب تغيير الكوتش بنجاح، وهو بانتظار موافقة الإدارة.',
            'requested_coach' => $newCoach->full_name
        ]);
    }


        



}
