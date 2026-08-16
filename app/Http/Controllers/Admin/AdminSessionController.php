<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Session;
use App\Models\GymHall;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class AdminSessionController extends Controller
{

    public function statistics()
    {
        $totalSessions     = Session::count();
        $todaySessions     = Session::whereDate('session_date', now()->toDateString())->count();
        $completedSessions = Session::where('status', 'completed')->count();
        $cancelledSessions = Session::where('status', 'cancelled')->count();

        return response()->json([
            'status' => 200,
            'data'   => [
                'total_sessions'     => $totalSessions,
                'today_sessions'     => $todaySessions,
                'completed_sessions' => $completedSessions,
                'cancelled_sessions' => $cancelledSessions,
            ]
        ]);
    }

    // 1. عرض جلسات النادي مع إمكانية الفلترة (للأدمن)
    public function indexSessions(Request $request)
    {
        if (auth()->user()->role !== 'admin') {
            return response()->json(['message' => 'غير مصرح لك بالوصول، هذه الصلاحية للأدمن فقط'], 403);
        }

        Session::updateExpiredSessions();

        $query = Session::with(['coach:id,full_name,email', 'hall:id,name,type,capacity', 'bookings.user:id,full_name'])
                        ->latest();

        // فلاتر البحث
        if ($request->filled('date')) {
            $query->whereDate('session_date', $request->date);
        }

        if ($request->filled('coach_id')) {
            $query->where('coach_id', $request->coach_id);
        }

        if ($request->filled('hall_id')) {
            $query->where('hall_id', $request->hall_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        $sessions = $query->paginate(20);

        return response()->json([
            'status'  => 200,
            'message' => 'تم جلب الجلسات بنجاح',
            'count'   => $sessions->total(),
            'data'    => $sessions->items(),
            'pagination' => [
                'current_page' => $sessions->currentPage(),
                'last_page'    => $sessions->lastPage(),
                'per_page'     => $sessions->perPage(),
            ]
        ], 200);
    }

    // 2. إضافة جلسة جديدة من قبل الأدمن
    public function storeSession(Request $request)
    {
        if (auth()->user()->role !== 'admin') {
            return response()->json(['message' => 'غير مصرح لك بالوصول، هذه الصلاحية للأدمن فقط'], 403);
        }

        $validator = Validator::make($request->all(), [
            'coach_id'     => 'required|exists:users,id',
            'type'         => 'required|in:group,individual',
            'hall_id'      => 'required|exists:gym_halls,id',
            'title'        => 'required|string|max:255',
            'description'  => 'nullable|string',
            'session_date' => 'required|date|after_or_equal:today',
            'start_time'   => 'required|date_format:H:i',
            'end_time'     => 'required|date_format:H:i|after:start_time',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            $coach = User::where('id', $request->coach_id)->where('role', 'coach')->first();
            if (!$coach) {
                return response()->json(['message' => 'المستخدم المختار ليس مدرباً معتمداً'], 422);
            }

            $hall = GymHall::findOrFail($request->hall_id);

            if ($hall->type !== $request->type) {
                return response()->json(['message' => 'نوع الصالة لا يطابق نوع الجلسة'], 422);
            }

            // التحقق من التعارضات
            if (Session::hasHallConflict($hall->id, $request->session_date, $request->start_time, $request->end_time)) {
                return response()->json(['message' => 'الصالة محجوزة مسبقاً في هذا الوقت'], 400);
            }

            if (Session::hasCoachConflict($coach->id, $request->session_date, $request->start_time, $request->end_time)) {
                return response()->json(['message' => 'المدرب لديه جلسة أخرى متعارضة في نفس الوقت'], 400);
            }

            $session = Session::create([
                'coach_id'     => $coach->id,
                'hall_id'      => $hall->id,
                'type'         => $request->type,
                'title'        => $request->title,
                'description'  => $request->description,
                'session_date' => $request->session_date,
                'start_time'   => $request->start_time,
                'end_time'     => $request->end_time,
                'capacity'     => $hall->capacity,
                'status'       => 'scheduled',
            ]);

            return response()->json([
                'status'  => 201,
                'message' => 'تم إضافة الجلسة بنجاح',
                'data'    => $session->load(['coach', 'hall'])
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'status'  => 500,
                'message' => 'حدث خطأ أثناء إضافة الجلسة',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    // 3. تعديل بيانات جلسة بواسطة الأدمن
    public function updateSession(Request $request, $id)
    {
        if (auth()->user()->role !== 'admin') {
            return response()->json(['message' => 'غير مصرح لك بالوصول، هذه الصلاحية للأدمن فقط'], 403);
        }

        $session = Session::find($id);
        if (!$session) {
            return response()->json(['message' => 'الجلسة غير موجودة'], 404);
        }

        $validator = Validator::make($request->all(), [
            'coach_id'     => 'sometimes|required|exists:users,id',
            'type'         => 'sometimes|required|in:group,individual',
            'hall_id'      => 'sometimes|required|exists:gym_halls,id',
            'title'        => 'sometimes|required|string|max:255',
            'description'  => 'nullable|string',
            'session_date' => 'sometimes|required|date',
            'start_time'   => 'sometimes|required|date_format:H:i',
            'end_time'     => 'sometimes|required|date_format:H:i|after:start_time',
            'status'       => 'sometimes|required|in:scheduled,completed,cancelled,expired',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            $coachId     = $request->filled('coach_id') ? $request->coach_id : $session->coach_id;
            $hallId      = $request->filled('hall_id') ? $request->hall_id : $session->hall_id;
            $sessionDate = $request->filled('session_date') ? $request->session_date : $session->session_date->format('Y-m-d');
            $startTime   = $request->filled('start_time') ? $request->start_time : substr((string) $session->start_time, 0, 5);
            $endTime     = $request->filled('end_time') ? $request->end_time : substr((string) $session->end_time, 0, 5);

            $hall = GymHall::findOrFail($hallId);

            // التحقق من التعارضات مع استثناء الجلسة الحالية
            if (Session::hasHallConflict($hall->id, $sessionDate, $startTime, $endTime, $session->id)) {
                return response()->json(['message' => 'الصالة محجوزة في هذا الوقت من قبل جلسة أخرى'], 400);
            }

            if (Session::hasCoachConflict($coachId, $sessionDate, $startTime, $endTime, $session->id)) {
                return response()->json(['message' => 'المدرب لديه تعارض في المواعيد'], 400);
            }

            $session->update($request->only([
                'coach_id',
                'hall_id',
                'type',
                'title',
                'description',
                'session_date',
                'start_time',
                'end_time',
                'status'
            ]));

            // تحديث السعة إن تغيرت الصالة
            if ($request->filled('hall_id')) {
                $session->update(['capacity' => $hall->capacity]);
            }

            return response()->json([
                'status'  => 200,
                'message' => 'تم تعديل بيانات الجلسة بنجاح',
                'data'    => $session->fresh()->load(['coach', 'hall'])
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status'  => 500,
                'message' => 'حدث خطأ أثناء تعديل الجلسة',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    // 4. حذف جلسة بواسطة الأدمن
    public function cancelSession($id)
    {
        $session = Session::find($id);
        if (!$session) {
            return response()->json(['message' => 'الجلسة غير موجودة'], 404);
        }

        if ($session->status === 'cancelled') {
            return response()->json(['message' => 'الجلسة ملغية بالفعل'], 400);
        }

        $session->update(['status' => 'cancelled']);
        
        // إلغاء جميع حجوزات المتدربين التابعين لهذه الجلسة
        $session->bookings()->update(['status' => 'cancelled']);

        return response()->json([
            'status'  => 200,
            'message' => 'تم إلغاء الجلسة وإلغاء حجوزاتها إدارياً بنجاح'
        ]);
    }

    // 5. عرض تفاصيل جلسة معينّة
    public function showSession($id)
    {
        if (!in_array(auth()->user()->role, ['admin', 'reception', 'trainee', 'coach'])) {
            return response()->json(['message' => 'غير مصرح لك بالوصول'], 403);
        }

        $session = Session::with(['coach:id,full_name,email', 'hall', 'bookings.user'])->find($id);

        if (!$session) {
            return response()->json(['message' => 'الجلسة غير موجودة'], 404);
        }

        return response()->json([
            'status'  => 200,
            'message' => 'تم جلب تفاصيل الجلسة بنجاح',
            'data'    => $session
        ], 200);
    }
}