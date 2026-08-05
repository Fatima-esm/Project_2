<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;

use Illuminate\Http\Request;

class ManagementCoachController extends Controller
{
    // عرض الكوتش الذين قاموا بالتسجيل وينتظرون موافقة الإدارة
    public function getPendingCoaches(Request $request)
    {
        $admin = auth()->user();

        if ($admin->role !== 'admin') {
            return response()->json(['message' => 'غير مصرح لك بالوصول، هذه الصلاحية للأدمن فقط'], 403);
        }

        // جلب المدربين الجدد قيد الانتظار حصراً
        $pendingCoaches = User::where('role', 'coach')
                            ->where('status', 'pending') 
                            ->with('coachProfile')
                            ->get();

        $formattedCoaches = $pendingCoaches->map(function ($coach) {
            return [
                'id'            => $coach->id,
                'full_name'     => $coach->full_name,
                'email'         => $coach->email,
                'phone'         => $coach->phone ?? 'غير متوفر',
                'status'        => $coach->status,
                'active_at'     => $coach->active_at,
                'cv_url'        => $coach->coachProfile && $coach->coachProfile->cv_path 
                                ? asset('storage/' . $coach->coachProfile->cv_path) 
                                : null,
                'created_at'    => $coach->created_at->format('Y-m-d H:i'),
            ];
        });

        return response()->json([
            'status' => 200,
            'message' => 'تم جلب قائمة الطلبات الجديدة بنجاح',
            'count' => $formattedCoaches->count(),
            'data' => $formattedCoaches
        ], 200);
    }

    //قبول أو رفض طلبات الكوتش   
    public function updateCoachStatus(Request $request, $id)
    {
        $admin = auth()->user();

        if ($admin->role !== 'admin') {
            return response()->json(['message' => 'غير مصرح لك بالوصول'], 403);
        }

        $request->validate([
            'active_at' => 'required|in:0,1', // 1 للقبول، 0 للرفض
            'reason'    => 'nullable|string|max:500', // سبب القبول أو الرفض
        ]);

        $coach = User::where('role', 'coach')->find($id);

        if (!$coach) {
            return response()->json(['message' => 'المدرب غير موجود'], 404);
        }

        // تحديث الحالة، التفعيل، والسبب
        $coach->active_at = $request->active_at;
        $coach->status = $request->active_at == 1 ? 'active' : 'rejected';
        $coach->status_reason = $request->reason; // حفظ سبب القبول أو الرفض
        $coach->save();

        $actionText = $request->active_at == 1 ? 'قبول وتفعيل' : 'رفض';

        return response()->json([
            'status' => 200,
            'message' => "تم {$actionText} حساب المدرب بنجاح",
            'data' => [
                'id'            => $coach->id,
                'full_name'     => $coach->full_name,
                'status'        => $coach->status,
                'active_at'     => $coach->active_at,
                'status_reason' => $coach->status_reason,
            ]
        ], 200);
    }
    
    public function getRejectedCoaches(Request $request)
    {
        $admin = auth()->user();

        if ($admin->role !== 'admin') {
            return response()->json(['message' => 'غير مصرح لك بالوصول'], 403);
        }

        // جلب المدربين الذين حالة حسابهم 'rejected'
        $rejectedCoaches = User::where('role', 'coach')
                            ->where('status', 'rejected')
                            ->with('coachProfile')
                            ->get();

        $formattedCoaches = $rejectedCoaches->map(function ($coach) {
            return [
                'id'            => $coach->id,
                'full_name'     => $coach->full_name,
                'email'         => $coach->email,
                'phone'         => $coach->phone ?? 'غير متوفر',
                'status'        => $coach->status,
                'active_at'     => $coach->active_at,
                'rejection_reason' => $coach->status_reason, // سبب الرفض السابق
                'cv_url'        => $coach->coachProfile && $coach->coachProfile->cv_path 
                                ? asset('storage/' . $coach->coachProfile->cv_path) 
                                : null,
                'updated_at'    => $coach->updated_at->format('Y-m-d H:i'),
            ];
        });

        return response()->json([
            'status' => 200,
            'message' => 'تم جلب قائمة المدربين المرفوضين بنجاح',
            'count' => $formattedCoaches->count(),
            'data' => $formattedCoaches
        ], 200);
    }

    public function reactivateCoach(Request $request, $id)
    {
        $admin = auth()->user();

        if ($admin->role !== 'admin') {
            return response()->json(['message' => 'غير مصرح لك بالوصول'], 403);
        }

        $coach = User::where('role', 'coach')->where('status', 'rejected')->find($id);

        if (!$coach) {
            return response()->json(['message' => 'المدرب المرفوض غير موجود'], 404);
        }

        // إعادة تفعيل الحساب وتحديث حالته إلى active
        $coach->active_at = 1;
        $coach->status = 'active';
        $coach->status_reason = null; // مسح سبب الرفض القديم عند إعادة التفعيل
        $coach->save();

        return response()->json([
            'status' => 200,
            'message' => 'تم إعادة تفعيل حساب المدرب بنجاح',
            'data' => [
                'id'        => $coach->id,
                'full_name' => $coach->full_name,
                'status'    => $coach->status,
                'active_at' => $coach->active_at,
            ]
        ], 200);
    }

    // عرض جميع المتدربين مقسمين أو تابعين لكل كوتش من وجهة نظر الأدمن
    public function getTraineesByCoach(Request $request, $coachId)
    {
        $admin = auth()->user();

        if ($admin->role !== 'admin') {
            return response()->json(['message' => 'غير مصرح لك بالوصول، هذه الصلاحية للأدمن فقط'], 403);
        }

        $coach = User::where('role', 'coach')->find($coachId);
        if (!$coach) {
            return response()->json(['message' => 'المدرب غير موجود'], 404);
        }

        $trainees = User::where('role', 'trainee')
                        ->where('coach_id', $coach->id)
                        ->get();

        $formattedTrainees = $trainees->map(function ($trainee) {
            return [
                'id'                => $trainee->id,
                'full_name'         => $trainee->full_name,
                'membership_number' => $trainee->membership_number,
                'phone'             => $trainee->phone ?? 'غير متوفر',
            ];
        });

        return response()->json([
            'status' => 200,
            'message' => "تم جلب متدربي المدرب ({$coach->full_name}) بنجاح",
            'coach_info' => [
                'id'        => $coach->id,
                'full_name' => $coach->full_name,
                'email'     => $coach->email,
            ],
            'count' => $formattedTrainees->count(),
            'data'  => $formattedTrainees
        ], 200);
    }





}
