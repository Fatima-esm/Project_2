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
            ->where('status', 'active')
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
                    'profile_image' => $coach->profile_image ? asset('storage/' . $coach->profile_image) : null,
                    'trainees_count' => $traineesCount, 
                    'is_available' => $coach->isAvailableForTrainees(),
                    'work_schedules' => $schedules, 
                                      
                ];
            });

        return response()->json([
            'status' => 200,
            'message' => 'تم جلب قائمة الكوتشات بنجاح',
            'data' => $coaches
        ]);
    }

    // details coach
    public function show($id)
    {
        $coach = User::where('role', 'coach')
            ->with(['workSchedules', 'coachProfile', 'salaries'])
            ->findOrFail($id);

        $traineesCount = User::where('coach_id', $coach->id)->count();

        $schedules = $coach->workSchedules->map(function ($schedule) {
            return [
                'id' => $schedule->id,
                'days' => $schedule->days,
                'work_name' => $schedule->work_name,
                'start_time' => $schedule->start_time,
                'end_time' => $schedule->end_time,
            ];
        });

        // جلب أحدث راتب مسجل للكوتش من جدول salaries
        $latestSalary = $coach->salaries->sortByDesc('created_at')->first();
        $profile = $coach->coachProfile;

        return response()->json([
            'status' => 200,
            'message' => 'تم جلب تفاصيل الكوتش بنجاح',
            'data' => [
                'id' => $coach->id,
                'membership_number' => $coach->membership_number,
                'full_name' => $coach->full_name,
                'email' => $coach->email,
                'phone' => $coach->phone,
                'gender' => $coach->gender,
                'age' => $coach->age,
                'status' => $coach->status,
                'status_reason' => $coach->status_reason,
                'active_at' => $coach->active_at,
                'profile_image' => $coach->profile_image ? asset('storage/' . $coach->profile_image) : null,
                'salary' => $latestSalary ? $latestSalary->net_salary : 0,
                'trainees_count' => $traineesCount,
                'is_available' => $coach->isAvailableForTrainees(),
                'profile_image' => $coach->profile_image ? asset('storage/' . $coach->profile_image) : null,
                
                // تفاصيل جدول coach_profiles
                'years_of_experience' => $profile->years_of_experience ?? null,
                'about_me' => $profile->about_me ?? $coach->about_me,
                'certificates_and_credits' => $profile->certificates_and_credits ?? null,
                'cv_url' => $profile && $profile->cv_path ? asset('storage/' . $profile->cv_path) : null,
                'id_card_image' => $profile && $profile->id_card_image ? asset('storage/' . $profile->id_card_image) : null,
                
                'created_at' => $coach->created_at ? $coach->created_at->format('Y-m-d H:i:s') : null,
                'updated_at' => $coach->updated_at ? $coach->updated_at->format('Y-m-d H:i:s') : null,
                // جداول العمل
                'work_schedules' => $schedules,
                
            ]
        ], 200);
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
                'message' => 'لديك كوتش بالفعل. لا يمكنك اختيار كوتش جديد بشكل مباشر، يرجى تقديم طلب تغيير كوتش من ادارة النادي.'
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

        $AvailableForTrainees = $coach->isAvailableForTrainees();

        if (!$AvailableForTrainees) {
            return response()->json([
                'status'  => 400,
                'message' => 'عذراً، لقد وصل هذا الكوتش إلى الحد الأقصى من المتدربين. يرجى اختيار كوتش آخر.'
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
