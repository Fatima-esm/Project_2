<?php

namespace App\Http\Controllers\Coach;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\WorkoutPlan;
use App\Models\User;

use Illuminate\Support\Facades\Validator;

class WorkoutPlanController extends Controller
{
    //اضافة خطة لمتدرب
    public function assignWorkoutPlan(Request $request, $traineeId)
    {
        $coach = auth()->user();

        if ($coach->role !== 'coach' && $coach->role !== 'admin') {
            return response()->json(['message' => 'غير مصرح لك بالوصول'], 403);
        }

        $trainee = User::where('role', 'trainee')
                    ->where('coach_id', $coach->id)
                    ->find($traineeId);

        if (!$trainee) {
            return response()->json(['message' => 'المتدرب غير موجود أو غير تابع لك'], 404);
        }

        // 2. التحقق من صحة المدخلات
        $validator = Validator::make($request->all(), [
            'exercise_id' => 'required|exists:exercises,id', 
            'sets'        => 'required|integer|min:1',       
            'reps'        => 'required|integer|min:1',       
            'rest_time'   => 'nullable|string|max:50',       
            'plan_date'   => 'required|date|after_or_equal:today',
            'notes'       => 'nullable|string|max:500',      
        ],[
            'plan_date.after_or_equal' => 'تاريخ الخطة يجب أن يكون اليوم أو بعده، ولا يُسمح بتاريخ سابق',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // 3. إنشاء خطة التمرين للمتدرب
        $workoutPlan = WorkoutPlan::create([
            'coach_id'    => $coach->id,
            'trainee_id'  => $trainee->id,
            'exercise_id' => $request->exercise_id,
            'sets'        => $request->sets,
            'reps'        => $request->reps,
            'rest_time'   => $request->rest_time,
            'plan_date'   => $request->plan_date,
            'notes'       => $request->notes,
        ]);

        // جلب الخطة مع تفاصيل التمرين التابع لها لعرضها بوضوح
        $workoutPlan->load('exercise');

        return response()->json([
            'status' => 201,
            'message' => 'تم تعيين خطة التمرين بنجاح للمتدرب',
            'data' => $workoutPlan
        ], 201);

    }

    // تعديل خطة تمرين موجودة للمتدرب
    public function updateWorkoutPlan(Request $request, $planId)
    {
        $coach = auth()->user();    
        if ($coach->role !== 'coach' && $coach->role !== 'admin') {
            return response()->json(['message' => 'غير مصرح لك بالوصول'], 403);
        }

        $workoutPlan = WorkoutPlan::where('coach_id', $coach->id)->find($planId);

        if (!$workoutPlan) {
            return response()->json(['message' => 'خطة التمرين غير موجودة أو غير تابعة لك'], 404);
        }

        // 2. التحقق من صحة المدخلات
        $validator = Validator::make($request->all(), [
            'exercise_id' => 'required|exists:exercises,id', 
            'sets'        => 'required|integer|min:1',       
            'reps'        => 'required|integer|min:1',       
            'rest_time'   => 'nullable|string|max:50',       
            'plan_date'   => 'required|date|after_or_equal:today',
            'notes'       => 'nullable|string|max:500',      
        ],[
            'plan_date.after_or_equal' => 'تاريخ الخطة يجب أن يكون اليوم أو بعده، ولا يُسمح بتاريخ سابق',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // 3. تحديث خطة التمرين
        $workoutPlan->update([
            'exercise_id' => $request->exercise_id,
            'sets'        => $request->sets,
            'reps'        => $request->reps,
            'rest_time'   => $request->rest_time,
            'plan_date'   => $request->plan_date,
            'notes'       => $request->notes,
        ]);

        // جلب الخطة مع تفاصيل التمرين التابع لها لعرضها بوضوح
        $workoutPlan->load('exercise');

        return response()->json([
            'status' => 200,
            'message' => 'تم تحديث خطة التمرين بنجاح',
            'data' => $workoutPlan
        ], 200);
    }

    // عرض جميع خطط التمارين الخاصة بمتدرب معين
    public function getTraineeWorkoutPlans(Request $request, $traineeId)
    {
        $coach = auth()->user();

        if (!in_array($coach->role, ['coach', 'admin'])) {
            return response()->json(['message' => 'غير مصرح لك بالوصول'], 403);
        }

        $traineeQuery = User::where('role', 'trainee')->where('id', $traineeId);
        if ($coach->role === 'coach') {
            $traineeQuery->where('coach_id', $coach->id);
        }

        $trainee = $traineeQuery->first();
        if (!$trainee) {
            return response()->json(['message' => 'المتدرب غير موجود أو غير تابع لك'], 404);
        }

        $query = WorkoutPlan::where('trainee_id', $traineeId)
            ->with('exercise:id,name,description,video_url,target_muscles,category_id')
            ->orderBy('plan_date')
            ->orderBy('id');

        // اختياري: من تاريخ معيّن
        if ($request->filled('from_date')) {
            $query->whereDate('plan_date', '>=', $request->from_date);
        }
        if ($request->filled('to_date')) {
            $query->whereDate('plan_date', '<=', $request->to_date);
        }

        $plans = $query->get();

        // تجميع حسب التاريخ
        $grouped = $plans->groupBy(fn ($p) => \Carbon\Carbon::parse($p->plan_date)->format('Y-m-d'));

        $days = [];
        $dayNumber = 1;

        foreach ($grouped as $date => $dayPlans) {
            $days[] = [
                'day_number' => $dayNumber,
                'date'       => $date,
                'day_label'  => 'اليوم ' . $dayNumber,
                'is_today'   => $date === now()->toDateString(),
                'exercises_count' => $dayPlans->count(),
                'exercises'  => $dayPlans->map(function ($plan) {
                    return [
                        'plan_id'     => $plan->id,
                        'exercise_id' => $plan->exercise_id,
                        'name'        => $plan->exercise->name ?? null,
                        'description' => $plan->exercise->description ?? null,
                        'video_url'   => $plan->exercise->video_url ?? null,
                        'target_muscles' => $plan->exercise->target_muscles ?? null,
                        'sets'        => $plan->sets,
                        'reps'        => $plan->reps,
                        'rest_time'   => $plan->rest_time,
                        'notes'       => $plan->notes,
                    ];
                })->values(),
            ];
            $dayNumber++;
        }

        // تمارين اليوم فقط (اختياري في نفس الرد)
        $todayBlock = collect($days)->firstWhere('is_today');

        return response()->json([
            'status'  => 200,
            'message' => 'تم جلب خطط التمارين بنجاح',
            'data'    => [
                'trainee' => [
                    'id'   => $trainee->id,
                    'name' => $trainee->full_name,
                ],
                'today' => $todayBlock ?: null,
                'days'  => $days,
                'total_days'      => count($days),
                'total_exercises' => $plans->count(),
            ],
        ]);
    }

}