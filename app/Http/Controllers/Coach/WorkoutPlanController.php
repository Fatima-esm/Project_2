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
            'plan_date'   => 'required|date',                
            'notes'       => 'nullable|string|max:500',      
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
            'plan_date'   => 'required|date',                
            'notes'       => 'nullable|string|max:500',      
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
    public function getTraineeWorkoutPlans($traineeId)
    {
        $coach = auth()->user();

        // 1. التحقق من الصلاحيات
        if ($coach->role !== 'coach' && $coach->role !== 'admin') {
            return response()->json(['message' => 'غير مصرح لك بالوصول'], 403);
        }

        // 2. التأكد أن المتدرب تابع لهذا الكوتش (أو أن الأدمن يستعرضهم)
        $traineeQuery = User::where('role', 'trainee')->where('id', $traineeId);
        if ($coach->role === 'coach') {
            $traineeQuery->where('coach_id', $coach->id);
        }
        
        $trainee = $traineeQuery->first();
        if (!$trainee) {
            return response()->json(['message' => 'المتدرب غير موجود أو غير تابع لك'], 404);
        }

        // 3. جلب جميع خطط التمارين الخاصة بهذا المتدرب مع تفاصيل التمرين
        $workoutPlans = WorkoutPlan::where('trainee_id', $traineeId)
                                   ->with('exercise')
                                   ->orderBy('plan_date', 'desc')
                                   ->get();

        return response()->json([
            'status' => 200,
            'message' => 'تم جلب خطط التمارين بنجاح',
            'count' => $workoutPlans->count(),
            'data' => [
                'trainee_name' => $trainee->full_name,
                'workout_plans' => $workoutPlans
            ]
        ], 200);
    }


}