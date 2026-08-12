<?php

namespace App\Http\Controllers\Session;

use App\Http\Controllers\Controller;
use App\Models\GymHall;
use App\Models\Session;
use App\Models\CoachSchedule;

use App\Models\SessionBooking;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class CoachSessionController extends Controller
{
    public function dashboard()
    {
        Session::updateExpiredSessions();

        $coach = auth()->user();
        if ($coach->role !== 'coach') {
            return response()->json(['message' => 'غير مصرح'], 403);
        }

        $today = now()->toDateString();
        $todayArabic = match (now()->format('l')) {
            'Saturday'  => 'السبت',
            'Sunday'    => 'الأحد',
            'Monday'    => 'الإثنين',
            'Tuesday'   => 'الثلاثاء',
            'Wednesday' => 'الأربعاء',
            'Thursday'  => 'الخميس',
            'Friday'    => 'الجمعة',
            default     => '',
        };

        $coach->load('workSchedules');
        $schedule = $coach->workSchedules->first();

        // هل اليوم ضمن أيام العمل؟
        $isWorkDay = false;
        if ($schedule && $schedule->days) {
            $isWorkDay = str_contains($schedule->days, $todayArabic);
        }

        $todaySessions = Session::where('coach_id', $coach->id)
            ->whereDate('session_date', $today)
            ->where('status', '!=', 'cancelled')
            ->count();

        $traineesCount = User::where('coach_id', $coach->id)->count();

        $upcomingGroup = Session::where('coach_id', $coach->id)
            ->where('type', 'group')
            ->whereDate('session_date', '>=', $today)
            ->where('status', 'scheduled')
            ->count();

        $upcomingIndividual = Session::where('coach_id', $coach->id)
            ->where('type', 'individual')
            ->whereDate('session_date', '>=', $today)
            ->where('status', 'scheduled')
            ->count();

        $totalBookings = SessionBooking::whereHas('session', fn($q) => $q->where('coach_id', $coach->id))->count();
        $attended = SessionBooking::whereHas('session', fn($q) => $q->where('coach_id', $coach->id))
            ->where('status', 'attended')->count();
        $attendanceRate = $totalBookings > 0 ? round(($attended / $totalBookings) * 100) : 0;

        return response()->json([
            'status' => 200,
            'data' => [
                'coach_info' => [
                    'name'        => $coach->full_name,
                    'image'       => $coach->profile_image_url,
                    'work_days'  => $schedule->days ?? null,           // الأحد-الخميس
                    'day_status' => $isWorkDay ? 'دوام' : 'إجازة',
                    'work_name'  => $schedule->work_name ?? null,      // صباحي / مسائي
                    'start_time' => $schedule
                        ? substr((string) $schedule->start_time, 0, 5)
                        : null,
                    'end_time'   => $schedule
                        ? substr((string) $schedule->end_time, 0, 5)
                        : null,
                ],
                'today_sessions'      => $todaySessions,
                'trainees_count'      => $traineesCount,
                'upcoming_group'      => $upcomingGroup,
                'upcoming_individual' => $upcomingIndividual,
                'attendance_rate'     => $attendanceRate,
            ],
        ]);
    }  

    // إنشاء جلسة
    public function store(Request $request)
    {
        Session::updateExpiredSessions();
        $coach = auth()->user();
        if ($coach->role !== 'coach') {
            return response()->json(['message' => 'غير مصرح'], 403);
        }

        $validator = Validator::make($request->all(), [
            'type'         => 'required|in:group,individual',
            'hall_id'      => 'required|exists:gym_halls,id',
            'title'        => 'required|string|max:255',
            'description'  => 'nullable|string', // هدف الجلسة (مهم للجماعية)
            'session_date' => 'required|date|after_or_equal:today',
            'start_time'   => 'required|date_format:H:i|',
            'end_time'     => 'required|date_format:H:i|after:start_time',
        ],[
            'type.required'              => 'نوع الجلسة مطلوب',
            'type.in'                    => 'نوع الجلسة يجب أن يكون جماعية أو فردية',
            'hall_id.required'           => 'يجب اختيار الصالة',
            'hall_id.integer'            => 'معرف الصالة غير صالح',
            'hall_id.exists'             => 'الصالة المحددة غير موجودة',
            'title.required'             => 'عنوان الجلسة مطلوب',
            'title.min'                  => 'عنوان الجلسة يجب ألا يقل عن 3 أحرف',
            'title.max'                  => 'عنوان الجلسة طويل جداً',
            'description.max'            => 'الوصف طويل جداً (الحد 1000 حرف)',
            'session_date.required'      => 'تاريخ الجلسة مطلوب',
            'session_date.date'          => 'صيغة التاريخ غير صحيحة (YYYY-MM-DD)',
            'session_date.after_or_equal'=> 'تاريخ الجلسة يجب أن يكون اليوم أو بعده',
            'start_time.required'        => 'وقت البداية مطلوب',
            'start_time.date_format'     => 'صيغة وقت البداية يجب أن تكون HH:MM مثل 09:00',
            'end_time.required'          => 'وقت النهاية مطلوب',
            'end_time.date_format'       => 'صيغة وقت النهاية يجب أن تكون HH:MM مثل 10:00',
            'end_time.after'             => 'وقت النهاية يجب أن يكون بعد وقت البداية',
        ]);

        if ($validator->fails()) {
            $allErrors = collect($validator->errors()->all())->implode(' - ');
            return response()->json(['message' => $allErrors], 422);
        }
        $sessionStart = \Carbon\Carbon::parse(
            $request->session_date . ' ' . $request->start_time
        );

        if ($sessionStart->lt(now())) {
            return response()->json([
                'message' => 'لا يمكن إنشاء جلسة في وقت قد مضى. اختر وقتاً صحيحا'
            ], 400);
        }

        $hall = GymHall::findOrFail($request->hall_id);

        if (!$hall->isAvailable()) {
            return response()->json(['message' => 'هذه الصالة غير متاحة حالياً'], 400);
        }

        // نوع الصالة = نوع الجلسة
        if ($hall->type !== $request->type) {
            return response()->json([
                'message' => 'نوع الصالة لا يطابق نوع الجلسة (جماعية / فردية)'
            ], 422);
        }

        // السعة من الصالة فقط (الإدارة)
        $capacity = $hall->capacity;

        // تعارض الصالة في نفس الوقت
        if (Session::hasHallConflict(
            $hall->id,
            $request->session_date,
            $request->start_time,
            $request->end_time
        )) {
            return response()->json([
                'message' => 'الصالة محجوزة في هذا الوقت. اختر وقتاً أو صالة أخرى'
            ], 400);
        }

        if (Session::hasCoachConflict(
            $coach->id,
            $request->session_date,
            $request->start_time,
            $request->end_time
        )) {
            return response()->json([
                'message' => 'لديك جلسة أخرى في نفس الوقت. لا يمكن حجز جلستين متزامنتين'
            ], 400);
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
            'capacity'     => $capacity, // من الإدارة عبر الصالة
            'status'       => 'scheduled',
        ]);

        // لا حجز متدرب هنا — حتى للفردية
        $session->load(['hall', 'coach']);

        return response()->json([
            'status'  => 201,
            'message' => 'تم إنشاء الجلسة بنجاح',
            'data'    => $this->formatSession($session),
        ], 201);
    }

    // تعديل بيانات جلسة بعد إنشائها
    public function update(Request $request, $id)
    {
        Session::updateExpiredSessions();
        $coach = auth()->user();

        $session = Session::find($id);

        if (!$session || $session->coach_id !== $coach->id) {
            return response()->json(['message' => 'الجلسة غير موجودة أو ليس لديك صلاحية عليها'], 404);
        }

        if (in_array($session->status, ['completed', 'cancelled'])) {
            return response()->json(['message' => 'لا يمكن تعديل جلسة منتهية أو ملغية مسبقاً'], 400);
        }

        $hasBookings = $session->bookings()->whereIn('status', ['booked', 'attended'])->exists();

        if ($hasBookings) {
            if (
                $request->has('session_date') || 
                $request->has('start_time') || 
                $request->has('end_time') || 
                $request->has('title') || 
                $request->has('description')
            ) {
                return response()->json([
                    'message' => 'لا يمكن تعديل وقت أو تاريخ أو تفاصيل الجلسة لأن هناك متدربين قاموا بحجزها بالفعل.'
                ], 400);
            }
        }


        $validator = Validator::make($request->all(), [
            'type'         => 'sometimes|required|in:group,individual',
            'hall_id'      => 'sometimes|required|exists:gym_halls,id',
            'title'        => 'sometimes|required|string|max:255',
            'description'  => 'nullable|string',
            'session_date' => 'sometimes|required|date|after_or_equal:today',
            'start_time'   => 'sometimes|required|date_format:H:i',
            'end_time'     => 'sometimes|required|date_format:H:i|after:start_time',
        ], [
            'type.in'             => 'نوع الجلسة يجب أن يكون جماعية أو فردية',
            'hall_id.exists'      => 'الصالة المحددة غير موجودة',
            'title.required'      => 'عنوان الجلسة مطلوب',
            'session_date.after_or_equal' => 'تاريخ الجلسة يجب أن يكون اليوم أو بعده',
            'end_time.after'      => 'وقت النهاية يجب أن يكون بعد وقت البداية',
        ]);

        if ($validator->fails()) {
            $allErrors = collect($validator->errors()->all())->implode(' - ');
            return response()->json(['message' => $allErrors], 422);
        }

        $hallId     = $request->filled('hall_id') ? $request->hall_id : $session->hall_id;
        $sessionDate = $request->filled('session_date') ? $request->session_date : $session->session_date->format('Y-m-d');
        $startTime  = $request->filled('start_time') ? $request->start_time : substr((string) $session->start_time, 0, 5);
        $endTime    = $request->filled('end_time') ? $request->end_time : substr((string) $session->end_time, 0, 5);
        $type       = $request->filled('type') ? $request->type : $session->type;

        $sessionStart = Carbon::parse($sessionDate . ' ' . $startTime);
        if ($sessionStart->lt(now())) {
            return response()->json(['message' => 'لا يمكن تعديل الجلسة لتكون في وقت قد مضى'], 400);
        }

        $hall = GymHall::findOrFail($hallId);

        if ($hall->type !== $type) {
            return response()->json(['message' => 'نوع الصالة لا يطابق نوع الجلسة (جماعية / فردية)'], 422);
        }

        if (Session::hasHallConflict($hall->id, $sessionDate, $startTime, $endTime, $session->id)) {
            return response()->json(['message' => 'الصالة محجوزة في هذا الوقت من قبل جلسة أخرى'], 400);
        }

        if (Session::hasCoachConflict($coach->id, $sessionDate, $startTime, $endTime, $session->id)) {
            return response()->json(['message' => 'لديك جلسة أخرى متعارضة في نفس الوقت'], 400);
        }

        // تنفيذ التحديث
        $session->update([
            'coach_id'     => $coach->id,
            'hall_id'      => $hall->id,
            'type'         => $type,
            'title'        => $request->filled('title') ? $request->title : $session->title,
            'description'  => $request->has('description') ? $request->description : $session->description,
            'session_date' => $sessionDate,
            'start_time'   => $startTime,
            'end_time'     => $endTime,
            'capacity'     => $hall->capacity, // تحديث السعة تلقائياً إذا تغيرت الصالة
        ]);

        $session->load(['hall', 'coach']);

        return response()->json([
            'status'  => 200,
            'message' => 'تم تعديل الجلسة بنجاح',
            'data'    => $this->formatSession($session),
        ]);
    }


    public function mySessions(Request $request)
    {
        Session::updateExpiredSessions();
        $coach = auth()->user();
        if ($coach->role !== 'coach') {
            return response()->json(['message' => 'غير مصرح'], 403);
        }

        $query = Session::where('coach_id', $coach->id)
            ->with(['hall', 'bookings.user'])
            ->orderBy('session_date', 'desc')
            ->orderBy('start_time', 'asc');

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('date')) {
            $date = strtolower(trim($request->date));

            if ($date === 'today') {
                $query->whereDate('session_date', now()->toDateString());
            } elseif ($date === 'tomorrow') {
                $query->whereDate('session_date', now()->addDay()->toDateString());
            }elseif ($date === 'past' || $date === 'before_today') {
                $query->whereDate('session_date', '<', now()->toDateString());
            } else {
                $query->whereDate('session_date', $date); // مثل 2026-08-12
            }
        }

        $sessions = $query->get()->map(fn($s) => $this->formatSession($s));

        return response()->json(['status' => 200,
         'data' => $sessions]);
    }

    public function show($id)
    {
        Session::updateExpiredSessions();
        $coach = auth()->user();
        $session = Session::with(['hall', 'bookings.user', 'coach'])->find($id);

        if (!$session || $session->coach_id !== $coach->id) {
            return response()->json(['message' => 'الجلسة غير موجودة'], 404);
        }

        return response()->json([
            'status' => 200,
            'data'   => $this->formatSession($session, true),
        ]);
    }

    public function markAttendance(Request $request, $sessionId)
    {
        $coach = auth()->user();

        // 1. التحقق أن المستخدم كوتش
        if ($coach->role !== 'coach') {
            return response()->json(['message' => 'غير مصرح لك بهذا الإجراء'], 403);
        }

        // 2. البحث عن الجلسة والتأكد أنها تخص هذا الكوتش
        $session = Session::where('id', $sessionId)
            ->where('coach_id', $coach->id)
            ->first();

        if (!$session) {
            return response()->json(['message' => 'الجلسة غير موجودة أو ليس لديك صلاحية عليها'], 404);
        }

        if ($session->status === 'cancelled') {
            return response()->json(['message' => 'لا يمكن إتمام جلسة تم إلغاؤها مسبقاً'], 400);
        }

        // 3. التحقق من صحة بيانات الحضور المرسلة
        $request->validate([
            'attendances'                 => 'nullable|array',
            'attendances.*.booking_id'    => 'required|exists:session_bookings,id',
            'attendances.*.status'        => 'required|in:attended,no_show',
        ]);

        // 4. تنفيذ تسجيل الحضور للمتدربين
        if ($request->has('attendances')) {
            foreach ($request->attendances as $item) {
                $booking = SessionBooking::where('id', $item['booking_id'])
                    ->where('session_id', $session->id)
                    ->first();

                if ($booking) {
                    $booking->update([
                        'status'      => $item['status'],
                        'attended_at' => $item['status'] === 'attended' ? now() : null,
                    ]);
                }
            }
        }

        SessionBooking::where('session_id', $session->id)
            ->where('status', 'booked')
            ->update(['status' => 'no_show']);

        $session->update([
            'status'             => 'completed',
            'coach_confirmed_at' => now(), 
        ]);

        return response()->json([
            'status'  => 200,
            'message' => 'تم تسجيل حضور المتدربين وإتمام الجلسة بنجاح، وتم تأكيدها للادارة',
            'data'    => [
                'session_id'         => $session->id,
                'status'             => $session->status,
                'status_label'       => $session->status_label,
                'coach_confirmed_at' => $session->coach_confirmed_at->format('Y-m-d H:i:s'),
            ]
        ]);
    }

    public function cancel($id)
    {
        $coach = auth()->user();
        $session = Session::find($id);

        if (!$session || $session->coach_id !== $coach->id) {
            return response()->json(['message' => 'الجلسة غير موجودة'], 404);
        }

        if ($session->status === 'cancelled') {
            return response()->json(['message' => 'الجلسة ملغية بالفعل'], 400);
        }

        if ($session->status === 'completed') {
            return response()->json(['message' => 'لا يمكن إلغاء جلسة مكتملة'], 400);
        }

        $dateOnly = \Carbon\Carbon::parse($session->session_date)->format('Y-m-d');
        $sessionDateTime = \Carbon\Carbon::parse($dateOnly . ' ' . $session->start_time);

        $minAllowedTime = now()->addHours(2);

        if ($sessionDateTime->isPast() || $sessionDateTime->lessThan($minAllowedTime)) {
            return response()->json([
                'message' => 'عذراً، لا يمكن إلغاء الجلسة لأنها بدأت بالفعل أو لم يتبقَ على بدئها ساعتان'
            ], 403);
        }

        $session->update(['status' => 'cancelled']);
        
        $session->bookings()->update(['status' => 'cancelled']);

        return response()->json(['status' => 200, 'message' => 'تم إلغاء الجلسة بنجاح']);
    }

    private function individualWeekLimitReached(int $traineeId, string $sessionDate): bool
    {
        $weekStart = Carbon::parse($sessionDate)->startOfWeek();
        $weekEnd   = Carbon::parse($sessionDate)->endOfWeek();

        $count = SessionBooking::where('user_id', $traineeId)
            ->whereIn('status', ['booked', 'attended'])
            ->whereHas('session', function ($q) use ($weekStart, $weekEnd) {
                $q->where('type', 'individual')
                  ->whereBetween('session_date', [$weekStart, $weekEnd])
                  ->where('status', '!=', 'cancelled');
            })
            ->count();

        return $count >= 2;
    }

    private function formatSession(Session $session, bool $detailed = false): array
    {
        $data = [
            'id'           => $session->id,
            'type'         => $session->type,
            'type_label'   => $session->type === 'group' ? 'جماعية' : 'فردية',
            'title'        => $session->title,
            'description'  => $session->description,
            'session_date' => $session->session_date->format('Y-m-d'),
            'start_time'   => substr((string) $session->start_time, 0, 5),
            'end_time'     => substr((string) $session->end_time, 0, 5),
            'capacity'     => $session->capacity,
            'booked_count' => $session->booked_count,
            'available'    => $session->has_available_slots,
            'status'       => $session->status,
            'status_label' => $session->status_label,
            'hall' => [
                'id'   => $session->hall->id,
                'name' => $session->hall->name,
                'type' => $session->hall->type,
            ],
            'coach_name'   => $session->coach->full_name ?? null,
        ];
        
        if ($session->type === 'individual') {
            $booking = $session->bookings->first(); // الجلسة الفردية غالباً لها حجز واحد نشط
            $data['trainee'] = $booking && $booking->user ? [
                'user_id' => $booking->user->id,
                'name'    => $booking->user->full_name,
                'email'   => $booking->user->email ?? null,
                'status'  => $booking->status,
            ] : null;
        }

        // إذا طُلب العرض التفصيلي (داخل دالة show)، يتم جلب قائمة المتدربين مع حالتهم ووقت الحضور
        if ($detailed) {
            $data['trainees'] = $session->bookings->map(fn($b) => [
                'booking_id'   => $b->id,
                'user_id'      => $b->user_id,
                'name'         => $b->user->full_name ?? 'مستخدم غير معروف',
                'email'        => $b->user->email ?? null,
                'status'       => $b->status,
                'status_label' => match ($b->status) {
                    'booked'    => 'محجوز',
                    'attended'  => 'حضر',
                    'cancelled' => 'ملغي',
                    'no_show'   => 'لم يحضر',
                    default     => $b->status,
                },
                'attended_at'  => $b->attended_at ? $b->attended_at->format('Y-m-d H:i:s') : null,
            ]);
        }

        return $data;
    }
    
    
    }