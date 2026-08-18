<?php

namespace App\Http\Controllers\Coach;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;


class TraineeController extends Controller
{

    // عرض جميع المتدربين عند الكوتش الحالي مع تفاصيلهم (الاشتراك، الهدف، الخطة الحالية)
    public function indexMyTrainees(Request $request)
    {
        $coach = auth()->user();

        if ($coach->role !== 'coach' && $coach->role !== 'admin') {
            return response()->json(['message' => 'غير مصرح لك بالوصول'], 403);
        }

        $trainees = User::where('role', 'trainee')
                        ->where('coach_id', $coach->id)
                        ->with([
                            'subscriptions' => function($query) {
                                $query->latest('expires_at');
                            },
                            'subscriptions.plan', 
                            'goal',               
                            'workoutPlans' => function($query) {
                                $query->where('plan_date', '>=', now()->toDateString())->with('exercise');
                            }
                        ])
                        ->get();

        $formattedTrainees = $trainees->map(function ($trainee) {
            $latestSubscription = $trainee->subscriptions->first();

            return [
                'id'                 => $trainee->id,
                'full_name'          => $trainee->full_name,
                'membership_number'  => $trainee->membership_number,
                'profile_image_url' => $trainee->profile_image_url,
                'current_subscription' => $latestSubscription ? [
                    'plan_name'  => $latestSubscription->plan->name_ar ?? $latestSubscription->plan->name ?? null,
                    'status'     => $latestSubscription->status, // paid, expired, pending
                    'expires_at' => $latestSubscription->expires_at->format('Y-m-d'),
                ] : null,

                'goal' => $trainee->goal->goal_name ?? $trainee->goal->goal_name?? 'غير محدد',
            ];
        });

        return response()->json([
            'status' => 200,
            'message' => 'تم جلب قائمة المتدربين مع تفاصيلهم بنجاح',
            'count' => $formattedTrainees->count(),
            'data' => $formattedTrainees
        ], 200);
    }

    //  عرض تفاصيل متدرب معين مع قياساته وهدفه
    public function showTraineeDetails($id)
    {
        $coach = auth()->user();

        $trainee = User::with(['measurements', 'goal']) 
                       ->where('role', 'trainee')
                       ->where('coach_id', $coach->id)
                       ->find($id);

        if (!$trainee) {
            return response()->json(['message' => 'المتدرب غير موجود أو ليس من ضمن متدربيك'], 404);
        }

        $measurements = $trainee->measurements()->latest()->first();

        return response()->json([
            'status' => 200,
            'message' => 'تم جلب تفاصيل المتدرب بنجاح',
            'data' => [
                'trainee_info' => $trainee,
                'measurements current' => $measurements, // جلب أحدث قياس
                'goal'         => $trainee->goal,         
            ]
        ], 200);
    }    

    //اضافة جلسة جماعية 

    //اضافة جلسة فردية

    //

    // اضافة ملاحظة لمتدرب

    // عرض جميع الخطط الفردية المتاحة عند متدرب

    // اختبار المتدرب خطة متاحة 

    // 






}
