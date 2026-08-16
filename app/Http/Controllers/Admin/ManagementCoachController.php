<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\ActivityLog;
use App\Models\StaffAttendance;
use App\Mail\CoachApplicationMail;
use Illuminate\Support\Facades\Mail;
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
                'profile_image_url' => $coach->profile_image
                    ? asset('storage/' . $coach->profile_image)
                    : null,

                'cv_url' => $coach->coachProfile && $coach->coachProfile->cv_path
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

    // قبول أو رفض طلبات الكوتش   
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

        $coach = User::where('role', 'coach')->with('coachProfile')->find($id);

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
                'id'               => $coach->id,
                'full_name'        => $coach->full_name,
                'email'            => $coach->email,
                'phone'            => $coach->phone ?? 'غير متوفر',
                'status'           => $coach->status,
                'active_at'        => $coach->active_at,
                'rejection_reason' => $coach->status_reason,
                'profile_image_url' => $coach->profile_image
                    ? asset('storage/' . $coach->profile_image)
                    : null,

                'cv_url' => $coach->coachProfile && $coach->coachProfile->cv_path
                    ? asset('storage/' . $coach->coachProfile->cv_path)
                    : null,
                'updated_at'       => $coach->updated_at->format('Y-m-d H:i'),
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
                'profile_image_url' => $coach->profile_image
                    ? asset('storage/' . $coach->profile_image)
                    : null,

                'cv_url' => $coach->coachProfile && $coach->coachProfile->cv_path
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

        $coach = User::where('role', 'coach')
            ->where('status', 'rejected')
            ->with('coachProfile')
            ->find($id);

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
                'id'               => $coach->id,
                'full_name'        => $coach->full_name,
                'email'            => $coach->email,
                'phone'            => $coach->phone ?? 'غير متوفر',
                'status'           => $coach->status,
                'active_at'        => $coach->active_at,
                'rejection_reason' => $coach->status_reason, // ستكون null نظراً لأنه تم مسحها عند إعادة التفعيل
                'profile_image_url' => $coach->profile_image
                    ? asset('storage/' . $coach->profile_image)
                    : null,

                'cv_url' => $coach->coachProfile && $coach->coachProfile->cv_path
                    ? asset('storage/' . $coach->coachProfile->cv_path)
                    : null,
                'updated_at'       => $coach->updated_at->format('Y-m-d H:i'),
            ]
        ], 200);
    }

    //all coach
    public function index()
    {
        $coaches = User::where('role', 'coach')
            ->with(['workSchedules', 'coachProfile']) 
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
                    'profile_image_url' => $coach->profile_image
                        ? asset('storage/' . $coach->profile_image)
                        : null,

                    'cv_url' => $coach->coachProfile && $coach->coachProfile->cv_path
                        ? asset('storage/' . $coach->coachProfile->cv_path)
                        : null,
            
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
                'days' => $schedule->days,
                'work_name' => $schedule->work_name,
                'start_time' => $schedule->start_time,
                'end_time' => $schedule->end_time,
            ];
        });

        // جلب أحدث راتب مسجل للكوتش من جدول salaries
        $latestSalary = $coach->salaries->sortByDesc('created_at')->first();

        return response()->json([
            'status' => 200,
            'message' => 'تم جلب تفاصيل الكوتش بنجاح',
            'data' => [
                'id' => $coach->id,
                'membership_number' => $coach->membership_number,
                'full_name' => $coach->full_name,
                'phone' => $coach->phone,
                'email' => $coach->email,
                'status' => $coach->status,
                'salary' => $latestSalary ? $latestSalary->net_salary : 0, // صافي الراتب أو base_salary حسب الحاجة
                'trainees_count' => $traineesCount,
                'profile_image_url' => $coach->profile_image
                    ? asset('storage/' . $coach->profile_image)
                    : null,

                'cv_url' => $coach->coachProfile && $coach->coachProfile->cv_path
                    ? asset('storage/' . $coach->coachProfile->cv_path)
                    : null,
                'work_schedules' => $schedules,
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

    public function coachCheckIn(Request $request)
    {
        $reception = auth()->user();

        if (!in_array($reception->role, ['admin', 'reception'])) {
            return response()->json(['message' => 'غير مصرح'], 403);
        }

        $request->validate([
            'membership_number' => 'required|string',
            'type'              => 'nullable|in:check_in,check_out',
            'note'              => 'nullable|string|max:500',
        ]);

        $coach = User::where('membership_number', $request->membership_number)
            ->where('role', 'coach')
            ->first(['id', 'full_name', 'membership_number', 'status', 'profile_image', 'role']);


        if (!$coach) {
            return response()->json(['message' => 'لم يتم العثور على كوتش بهذا الرقم'], 404);
        }

        if ($coach->status !== 'active') {
            return response()->json(['message' => 'حساب الكوتش غير مفعّل'], 400);
        }

        $type = $request->type ?? 'check_in';

        // آخر حركة اليوم
        $last = StaffAttendance::where('user_id', $coach->id)
            ->whereDate('recorded_at', today())
            ->latest('recorded_at')
            ->first(['id', 'type', 'recorded_at']); 

        // تسجيل دخول
        if ($type === 'check_in') {
            if ($last && $last->type === 'check_in') {
                return response()->json([
                    'message'       => 'تم تسجيل دخول هذا الكوتش مسبقاً اليوم. لا يمكن تسجيل دخول مرة ثانية قبل تسجيل الخروج.',
                    'last_check_in' => $last->recorded_at->format('H:i'),
                ], 400);
            }
        }
                // workSchedules فقط عند الخروج
        if ($type === 'check_out') {
            $coach->load('workSchedules:id,days,work_name,start_time,end_time');
        }


        // تسجيل خروج
        if ($type === 'check_out') {
            if (!$last || $last->type !== 'check_in') {
                return response()->json([
                    'message' => 'لا يمكن تسجيل الخروج. الكوتش ليس لديه تسجيل دخول نشط اليوم.',
                ], 400);
            }
        }

        // ملاحظة الخروج المبكر مع حساب المدة
        $note = $request->note;

        if ($type === 'check_out') {
            $coach->load('workSchedules');
            $schedule = $coach->workSchedules->first();

            if ($schedule && $schedule->end_time) {
                $endTimeStr = substr((string) $schedule->end_time, 0, 5); // 00:00 أو 16:00

                // إذا النهاية 00:00 → نهاية اليوم = بداية الغد
                if ($endTimeStr === '00:00') {
                    $workEnd = \Carbon\Carbon::parse(now()->toDateString() . ' 00:00:00')->addDay();
                } else {
                    $workEnd = \Carbon\Carbon::parse(now()->toDateString() . ' ' . $schedule->end_time);

                    // إذا نهاية الدوام أصغر من بدايته (دوام ليلي عادي)
                    if ($schedule->start_time && $schedule->end_time < $schedule->start_time) {
                        $workEnd->addDay();
                    }
                }

                if (now()->lt($workEnd)) {
                    $diff  = now()->diff($workEnd);
                    $parts = [];

                    if ($diff->h > 0) {
                        $parts[] = $diff->h . ' ساعة';
                    }
                    if ($diff->i > 0) {
                        $parts[] = $diff->i . ' دقيقة';
                    }
                    if (empty($parts)) {
                        $parts[] = 'أقل من دقيقة';
                    }

                    $earlyNote = 'تسجيل خروج قبل انتهاء موعد العمل بـ ' . implode(' و ', $parts) .
                                ' (نهاية الدوام: ' . $endTimeStr . ')';

                    $note = $note ? ($note . ' | ' . $earlyNote) : $earlyNote;
                }
            }
        }

        $attendance = StaffAttendance::create([
            'user_id'     => $coach->id,
            'recorded_by' => $reception->id,
            'type'        => $type,
            'recorded_at' => now(),
            'note'        => $note,
        ]);

        // تسجيل نشاط الموظف
        $addedByName = $reception->full_name;
        $typeLabel   = $type === 'check_in' ? 'دخول' : 'خروج';
        $action      = $type === 'check_in' ? 'coach_check_in' : 'coach_check_out';
        $actionLabel = $type === 'check_in' ? 'تسجيل دخول كوتش' : 'تسجيل خروج كوتش';

        ActivityLog::log(
            auth()->id(),
            $action,
            $actionLabel,
            [
                'subject_type' => User::class,
                'subject_id'   => $coach->id,
                'details'      => 'اسم الكوتش: ' . $coach->full_name . ' | عضوية: ' . $coach->membership_number,
                'icon'         => $type === 'check_in' ? 'check_in' : 'check_out',
                'properties'   => [
                    'message' => 'تم تسجيل ' . $typeLabel . ' الكوتش: ' . $coach->full_name .
                                ' | رقم العضوية: ' . $coach->membership_number .
                                ' | الوقت: ' . $attendance->recorded_at->format('H:i') .
                                ($note ? ' | ملاحظة: ' . $note : '') .
                                ' | بواسطة: ' . $addedByName,
                ],
            ]
        );

        return response()->json([
            'status'  => 200,
            'message' => $type === 'check_in'
                ? 'تم تسجيل دخول الكوتش بنجاح'
                : 'تم تسجيل خروج الكوتش بنجاح',
            'data' => [
                'coach' => [
                    'id'                => $coach->id,
                    'full_name'         => $coach->full_name,
                    'membership_number' => $coach->membership_number,
                    'image_url'         => $coach->profile_image_url
                        ?? ($coach->profile_image ? asset('storage/' . $coach->profile_image) : null),
                ],
                'type'        => $type,
                'type_label'  => $typeLabel,
                'recorded_at' => $attendance->recorded_at->format('Y-m-d H:i'),
                'note'        => $attendance->note,
                'recorded_by' => $reception->full_name,
            ],
        ]);
    }

    public function employeeAttendanceRecords(Request $request, $userId)
    {
        $admin = auth()->user();

        if (!in_array($admin->role, ['admin', 'reception'])) {
            return response()->json(['message' => 'غير مصرح'], 403);
        }

        $employee = User::whereIn('role', ['coach', 'reception'])
            ->find($userId, ['id', 'full_name', 'role', 'membership_number']);

        if (!$employee) {
            return response()->json(['message' => 'الموظف غير موجود'], 404);
        }

        // نطاق الشهر مرة واحدة
        $month = $request->month ?? now()->format('Y-m');
        $start = $month . '-01 00:00:00';
        $end   = \Carbon\Carbon::parse($start)->endOfMonth()->format('Y-m-d 23:59:59');

        $query = StaffAttendance::where('user_id', $userId)
            ->with(['recorder:id,full_name'])
            ->select(['id', 'user_id', 'recorded_by', 'type', 'recorded_at', 'note'])
            ->orderBy('recorded_at');

        if ($request->filled('date')) {
            $query->whereDate('recorded_at', $request->date);
        } else {
            // أسرع من LIKE
            $query->whereBetween('recorded_at', [$start, $end]);
        }

        $allRecords = $query->get();

        // بناء الأزواج (كما هو)
        $sessions = [];
        $current  = null;

        foreach ($allRecords as $row) {
            if ($row->type === 'check_in') {
                if ($current !== null) {
                    $sessions[] = $this->finalizeOpenSession($current);
                }

                $current = [
                    'date'         => $row->recorded_at->format('Y-m-d'),
                    'check_in'     => $row->recorded_at->format('H:i'),
                    'check_out'    => null,
                    'note'         => $row->note,
                    'recorded_by'  => $row->recorder?->full_name,
                ];
            } elseif ($row->type === 'check_out') {
                if ($current !== null && $current['check_out'] === null) {
                    $current['check_out']    = $row->recorded_at->format('H:i');
                    $current['status']       = 'مكتمل';
                    $current['status_label'] = 'تم تسجيل دخول وخروج الكوتش';

                    if ($row->note) {
                        $current['note'] = trim(($current['note'] ? $current['note'] . ' | ' : '') . $row->note);
                    }

                    $sessions[] = $current;
                    $current = null;
                } else {
                    $sessions[] = [
                        'date'         => $row->recorded_at->format('Y-m-d'),
                        'check_in'     => null,
                        'check_out'    => $row->recorded_at->format('H:i'),
                        'note'         => $row->note,
                        'recorded_by'  => $row->recorder?->full_name,
                        'status'       => 'خروج_فقط',
                        'status_label' => 'تسجيل خروج بدون دخول',
                    ];
                }
            }
        }

        if ($current !== null) {
            $sessions[] = $this->finalizeOpenSession($current);
        }

        $sessions = array_reverse($sessions);

        // حالة اليوم من نفس النتائج (بدون استعلام إضافي)
        $todayStr     = today()->toDateString();
        $todaySession = collect($sessions)->firstWhere('date', $todayStr);

        if (!$todaySession) {
            $todayStatus = 'لم يسجّل اليوم';
            $todayCheckIn = $todayCheckOut = null;
        } elseif ($todaySession['check_out'] === null && ($todaySession['status'] ?? '') === 'موجود') {
            $todayStatus   = 'موجود الآن';
            $todayCheckIn  = $todaySession['check_in'];
            $todayCheckOut = null;
        } elseif ($todaySession['check_out'] !== null) {
            $todayStatus   = 'انتهى دوامه';
            $todayCheckIn  = $todaySession['check_in'];
            $todayCheckOut = $todaySession['check_out'];
        } else {
            $todayStatus   = 'لم يسجّل اليوم';
            $todayCheckIn  = $todayCheckOut = null;
        }

        return response()->json([
            'status'   => 200,
            'message'  => 'تم جلب سجلات الحضور بنجاح',
            'employee' => [
                'id'                => $employee->id,
                'full_name'         => $employee->full_name,
                'role'              => $employee->role,
                'membership_number' => $employee->membership_number,
            ],
            'today' => [
                'check_in'  => $todayCheckIn,
                'check_out' => $todayCheckOut,
                'status'    => $todayStatus,
            ],
            'count' => count($sessions),
            'data'  => array_values($sessions),
        ]);
    }

    // dayly
    // public function allCoachesAttendance(Request $request)
    // {
    //     if (!in_array(auth()->user()->role, ['admin', 'reception'])) {
    //         return response()->json(['message' => 'غير مصرح'], 403);
    //     }

    //     $date    = $request->date ?? today()->toDateString();
    //     $dayName = $this->arabicDayName($date);

    //     $coaches = User::where('role', 'coach')
    //         ->where('active_at', 1)
    //         ->when($request->filled('search'), function ($q) use ($request) {
    //             $s = $request->search;
    //             $q->where(fn($q) => $q->where('full_name', 'like', "%{$s}%")
    //                 ->orWhere('membership_number', 'like', "%{$s}%"));
    //         })
    //         ->when($request->filled('coach_id'), fn($q) => $q->where('id', $request->coach_id))
    //         ->with('workSchedules:id,days,work_name,start_time,end_time')
    //         ->get(['id', 'full_name', 'membership_number', 'profile_image', 'status']);

    //     $records = \App\Models\StaffAttendance::whereIn('user_id', $coaches->pluck('id'))
    //         ->whereDate('recorded_at', $date)
    //         ->orderBy('recorded_at')
    //         ->get(['user_id', 'type', 'recorded_at', 'note']);

    //     $byUser = $records->groupBy('user_id');

    //     $data = $coaches->map(function ($coach, $index) use ($byUser, $date, $dayName) {
    //         $isWorkDay = $this->isWorkDay($coach->workSchedules, $dayName);
    //         $rows      = $byUser->get($coach->id, collect());
    //         $checkIn   = $rows->firstWhere('type', 'check_in');
    //         $checkOut  = $rows->where('type', 'check_out')->last();

    //         $inTime  = $checkIn?->recorded_at;
    //         $outTime = $checkOut?->recorded_at;

    //         [$status, $statusKey] = $this->resolveStatus($isWorkDay, $inTime, $outTime, $date);

    //         $durationLabel = '—';
    //         if ($inTime && $outTime) {
    //             $diff = $inTime->diff($outTime);
    //             $durationLabel = sprintf('%02d:%02d', $diff->h, $diff->i) . ' ساعة';
    //         }

    //         $schedule = $coach->workSchedules->first(fn($s) => $this->dayInSchedule($dayName, $s->days ?? ''));

    //         return [
    //             '#'                   => $index + 1,
    //             'id'                  => $coach->id,
    //             'full_name'           => $coach->full_name,
    //             'title'               => 'كوتش',
    //             'membership_number'   => $coach->membership_number,
    //             'image_url'           => $coach->profile_image_url
    //                 ?? ($coach->profile_image ? asset('storage/' . $coach->profile_image) : null),
    //             'is_work_day'         => $isWorkDay,
    //             'work_name'           => $schedule->work_name ?? null,
    //             'scheduled_time'      => $schedule
    //                 ? substr((string)$schedule->start_time, 0, 5) . '-' . substr((string)$schedule->end_time, 0, 5)
    //                 : null,
    //             'last_check_in'       => $inTime ? $inTime->format('h:i A') : null,
    //             'last_check_in_label' => $inTime ? 'تم الدخول' : ($isWorkDay ? 'لم يسجل دخول' : '—'),
    //             'last_check_out'      => $outTime ? $outTime->format('h:i A') : null,
    //             'last_check_out_label'=> $outTime ? 'تم الخروج' : ($inTime ? 'لم يسجل خروج' : '—'),
    //             'work_duration_label' => $durationLabel,
    //             'status'              => $status,
    //             'status_key'          => $statusKey,
    //             'date'                => $date,
    //             'note'                => $checkOut?->note ?? $checkIn?->note,
    //         ];
    //     });

    //     if ($request->filled('status_filter') && $request->status_filter !== 'all') {
    //         $map = [
    //             'completed' => 'مكتمل',
    //             'present'   => 'متواجد الآن',
    //             'absent'    => 'غائب',
    //             'leave'     => 'إجازة',
    //         ];
    //         $label = $map[$request->status_filter] ?? $request->status_filter;
    //         $data = $data->where('status', $label)->values();
    //     } else {
    //         $data = $data->values();
    //     }

    //     return response()->json([
    //         'status'  => 200,
    //         'date'    => $date,
    //         'day'     => $dayName,
    //         'summary' => [
    //             'total'     => $data->count(),
    //             'completed' => $data->where('status_key', 'completed')->count(),
    //             'present'   => $data->where('status_key', 'present')->count(),
    //             'absent'    => $data->where('status_key', 'absent')->count(),
    //             'leave'     => $data->where('status_key', 'leave')->count(),
    //         ],
    //         'data' => $data,
    //     ]);
    // }

    public function allCoachesAttendance(Request $request)
    {
        if (!in_array(auth()->user()->role, ['admin', 'reception'])) {
            return response()->json(['message' => 'غير مصرح'], 403);
        }

        // ===== تحديد الفترة الزمنية (بداية ونهاية) =====
        if ($request->filled('month') && $request->filled('year')) {
            // 1. الأولوية للشهر والسنة (حتى لو كان حقل التاريخ مرسلاً بالخطأ من الواجهة)
            $start = \Carbon\Carbon::create($request->year, $request->month, 1)->startOfMonth();
            $end   = \Carbon\Carbon::create($request->year, $request->month, 1)->endOfMonth();
        } 
        elseif ($request->filled('year') && !$request->filled('month')) {
            // 2. إذا تم اختيار السنة وحدها
            $start = \Carbon\Carbon::create($request->year, 1, 1)->startOfYear();
            $end   = \Carbon\Carbon::create($request->year, 12, 31)->endOfYear();
        } 
        elseif ($request->filled('date')) {
            // 3. إذا تم اختيار يوم محدد فقط (ولم يتم اختيار شهر أو سنة)
            $start = \Carbon\Carbon::parse($request->date)->startOfDay();
            $end   = \Carbon\Carbon::parse($request->date)->endOfDay();
        } 
        else {
            // 4. القيمة الافتراضية (الشهر الحالي كاملاً)
            $start = now()->startOfMonth();
            $end   = now()->endOfMonth();
        }      
        // ===== الكوتشات المفعّلون =====
        $coaches = User::where('role', 'coach')
            ->where('active_at', 1)
            ->when($request->filled('search'), function ($q) use ($request) {
                $s = $request->search;
                $q->where(function ($q) use ($s) {
                    $q->where('full_name', 'like', "%{$s}%")
                    ->orWhere('membership_number', 'like', "%{$s}%");
                });
            })
            ->when($request->filled('coach_id'), fn($q) => $q->where('id', $request->coach_id))
            ->with('workSchedules:id,days,work_name,start_time,end_time')
            ->get(['id', 'full_name', 'membership_number', 'profile_image']);

        if ($coaches->isEmpty()) {
            return response()->json([
                'status'  => 200,
                'from'    => $start->toDateString(),
                'to'      => $end->toDateString(),
                'total'   => 0,
                'summary' => ['present' => 0, 'completed' => 0, 'absent' => 0, 'leave' => 0],
                'data'    => [],
            ]);
        }

        $coachIds = $coaches->pluck('id');

        // ===== كل سجلات الحضور في الفترة =====
        $records = \App\Models\StaffAttendance::whereIn('user_id', $coachIds)
            ->whereBetween('recorded_at', [$start->copy()->startOfDay(), $end->copy()->endOfDay()])
            ->with('recorder:id,full_name')
            ->orderBy('recorded_at')
            ->get(['id', 'user_id', 'type', 'recorded_at', 'note', 'recorded_by']);

        $byUserDate = $records->groupBy(fn($r) => $r->user_id . '_' . \Carbon\Carbon::parse($r->recorded_at)->format('Y-m-d'));

        $data = collect();

        foreach ($coaches as $coach) {
            for ($d = $start->copy(); $d->lte($end); $d->addDay()) {
                $dateStr = $d->toDateString();
                $dayName = $this->arabicDayName($dateStr);
                $isWork  = $this->isWorkDay($coach->workSchedules, $dayName);

                $dayRows  = $byUserDate->get($coach->id . '_' . $dateStr, collect());
                $checkIn  = $dayRows->firstWhere('type', 'check_in');
                $checkOut = $dayRows->where('type', 'check_out')->last();

                $inTime  = $checkIn?->recorded_at ? \Carbon\Carbon::parse($checkIn->recorded_at) : null;
                $outTime = $checkOut?->recorded_at ? \Carbon\Carbon::parse($checkOut->recorded_at) : null;

                // لا تعرض أيام إجازة بدون أي سجل (إلا إذا طُلب)
                if (!$isWork && $dayRows->isEmpty() && !$request->boolean('include_leave')) {
                    continue;
                }

                [$status, $statusKey] = $this->resolveStatus($isWork, $inTime, $outTime, $dateStr);

                $durationLabel = '—';
                if ($inTime && $outTime) {
                    $diff = $inTime->diff($outTime);
                    $durationLabel = sprintf('%02d:%02d', $diff->h, $diff->i) . ' ساعة';
                }

                $data->push([
                    'coach_id'             => $coach->id,
                    'full_name'            => $coach->full_name,
                    'title'                => 'كوتش',
                    'membership_number'    => $coach->membership_number,
                    'image_url'            => $coach->profile_image_url
                        ?? ($coach->profile_image ? asset('storage/' . $coach->profile_image) : null),
                    'date'                 => $dateStr,
                    'day_name'             => $dayName,
                    'is_work_day'          => $isWork,
                    'last_check_in'        => $inTime ? $inTime->format('h:i A') : null,
                    'last_check_in_label'  => $inTime ? 'تم الدخول' : ($isWork ? 'لم يسجل دخول' : '—'),
                    'last_check_out'       => $outTime ? $outTime->format('h:i A') : null,
                    'last_check_out_label' => $outTime ? 'تم الخروج' : ($inTime ? 'لم يسجل خروج' : '—'),
                    'time_range'           => ($inTime && $outTime)
                        ? $inTime->format('H:i') . '-' . $outTime->format('H:i')
                        : ($inTime ? $inTime->format('H:i') . '-—' : null),
                    'work_duration_label'  => $durationLabel,
                    'status'               => $status,      // متواجد الآن | مكتمل | غائب | إجازة
                    'status_key'           => $statusKey,   // present | completed | absent | leave
                    'recorded_by'          => $checkOut?->recorder?->full_name
                        ?? $checkIn?->recorder?->full_name,
                    'note'                 => $checkOut?->note ?? $checkIn?->note,
                ]);
            }
        }

        // الأحدث أولاً
        $data = $data->sortByDesc('date')->values();

        // ===== فلترة الحالة =====
        if ($request->filled('status_filter') && $request->status_filter !== 'all') {
            $map = [
                'present'   => 'present',
                'completed' => 'completed',
                'absent'    => 'absent',
                'leave'     => 'leave',
                'متواجد'    => 'present',
                'مكتمل'     => 'completed',
                'غائب'      => 'absent',
                'إجازة'     => 'leave',
            ];
            $key = $map[$request->status_filter] ?? $request->status_filter;
            $data = $data->where('status_key', $key)->values();
        }

        $summary = [
            'present'   => $data->where('status_key', 'present')->count(),
            'completed' => $data->where('status_key', 'completed')->count(),
            'absent'    => $data->where('status_key', 'absent')->count(),
            'leave'     => $data->where('status_key', 'leave')->count(),
        ];

        return response()->json([
            'status'  => 200,
            'from'    => $start->toDateString(),
            'to'      => $end->toDateString(),
            'total'   => $data->count(),
            'summary' => $summary,
            'data'    => $data,
        ]);
    } 

    public function monthlyCoachesAttendance(Request $request)
    {
        if (!in_array(auth()->user()->role, ['admin', 'reception'])) {
            return response()->json(['message' => 'غير مصرح'], 403);
        }

        // تحديد الشهر والسنة (افتراضياً الشهر الحالي)
        $month = $request->month ?? now()->month;
        $year  = $request->year ?? now()->year;

        $startDate = \Carbon\Carbon::create($year, $month, 1)->startOfMonth();
        $endDate   = \Carbon\Carbon::create($year, $month, 1)->endOfMonth();

        // جلب الكوتشات المفعلين فقط
$coaches = User::where('role', 'coach')
    ->where('status', 'active')
    ->when($request->filled('search'), function ($q) use ($request) {
        $s = $request->search;
        $q->where(fn($q) => $q->where('full_name', 'like', "%{$s}%")
            ->orWhere('membership_number', 'like', "%{$s}%"));
    })
    ->when($request->filled('coach_id'), fn($q) => $q->where('id', $request->coach_id))
    ->with('workSchedules:id,days,work_name,start_time,end_time') // <-- تم حذف user_id من هنا
    ->get(['id', 'full_name', 'membership_number', 'profile_image', 'status']);
        // جلب سجلات الحضور الخاصة بالكوتشات خلال الشهر المحدد بالكامل
        $records = \App\Models\StaffAttendance::whereIn('user_id', $coaches->pluck('id'))
            ->whereBetween('recorded_at', [$startDate, $endDate])
            ->orderBy('recorded_at')
            ->get(['user_id', 'type', 'recorded_at', 'note']);

        // تجميع السجلات حسب الكوتش ثم حسب التاريخ
        $recordsByUser = $records->groupBy('user_id');

        $data = $coaches->map(function ($coach, $index) use ($recordsByUser, $startDate, $endDate) {
            $coachRecords = $recordsByUser->get($coach->id, collect());
            
            // تجميع السجلات الخاصة بهذا الكوتش حسب الأيام داخل الشهر
            $groupedByDate = $coachRecords->groupBy(fn($item) => \Carbon\Carbon::parse($item->recorded_at)->toDateString());

            $totalPresentDays = 0;
            $totalCompletedDays = 0;
            $daysSummary = [];

            // المرور على كل يوم من أيام الشهر لحساب الملخص
            $currentDate = $startDate->copy();
            while ($currentDate <= $endDate) {
                $dateStr = $currentDate->toDateString();
                $dayName = $this->arabicDayName($dateStr);
                $isWorkDay = $this->isWorkDay($coach->workSchedules, $dayName);

                $dayRows = $groupedByDate->get($dateStr, collect());
                $checkIn  = $dayRows->firstWhere('type', 'check_in');
                $checkOut = $dayRows->where('type', 'check_out')->last();

                $inTime  = $checkIn?->recorded_at;
                $outTime = $checkOut?->recorded_at;

                [$status, $statusKey] = $this->resolveStatus($isWorkDay, $inTime, $outTime, $dateStr);

                if ($statusKey === 'present') {
                    $totalPresentDays++;
                } elseif ($statusKey === 'completed') {
                    $totalCompletedDays++;
                }

                $currentDate->addDay();
            }

            return [
                '#'                   => $index + 1,
                'id'                  => $coach->id,
                'full_name'           => $coach->full_name,
                'title'               => 'كوتش',
                'membership_number'   => $coach->membership_number,
                'image_url'           => $coach->profile_image_url
                    ?? ($coach->profile_image ? asset('storage/' . $coach->profile_image) : null),
                'total_present_days'  => $totalPresentDays,
                'total_completed_days'=> $totalCompletedDays,
                'attendance_records'  => $coachRecords->map(fn($record) => [
                    'type'        => $record->type,
                    'recorded_at' => $record->recorded_at->toDateTimeString(),
                    'note'        => $record->note,
                ]),
            ];
        })->values();

        return response()->json([
            'status'  => 200,
            'year'    => $year,
            'month'   => $month,
            'summary' => [
                'total_coaches' => $data->count(),
            ],
            'data'    => $data,
        ]);
    }

    private function finalizeOpenSession(array $session): array
    {
        $isToday = $session['date'] === now()->toDateString();

        if ($isToday) {
            $session['status']       = 'موجود';
            $session['status_label'] = 'تم تسجيل حضور الكوتش';
        } else {
            $session['status']       = 'دخول_فقط';
            $session['status_label'] = 'تسجيل دخول فقط';
        }

        return $session;
    }

    private function arabicDayName($date): string
    {
        $d = \Carbon\Carbon::parse($date);
        return match ((int) $d->dayOfWeek) {
            0 => 'الأحد',
            1 => 'الاثنين',
            2 => 'الثلاثاء',
            3 => 'الأربعاء',
            4 => 'الخميس',
            5 => 'الجمعة',
            6 => 'السبت',
            default => '',
        };
    }

    private function isWorkDay($schedules, string $dayName): bool
    {
        foreach ($schedules as $schedule) {
            if ($this->dayInSchedule($dayName, $schedule->days ?? '')) {
                return true;
            }
        }
        return false;
    }

    private function dayInSchedule(string $dayName, string $daysField): bool
    {
        $daysField = str_replace(['–', '—', 'الاحد'], ['-', '-', 'الأحد'], $daysField);
        $dayName   = str_replace('الاحد', 'الأحد', $dayName);

        if (str_contains($daysField, ',')) {
            return in_array($dayName, array_map('trim', explode(',', $daysField)), true);
        }

        if (str_contains($daysField, '-')) {
            $order = ['السبت', 'الأحد', 'الاثنين', 'الثلاثاء', 'الأربعاء', 'الخميس', 'الجمعة'];
            [$from, $to] = array_map('trim', explode('-', $daysField, 2));
            $fromIdx = array_search($from, $order, true);
            $toIdx   = array_search($to, $order, true);
            $dayIdx  = array_search($dayName, $order, true);

            if ($fromIdx === false || $toIdx === false || $dayIdx === false) {
                return str_contains($daysField, $dayName);
            }
            if ($fromIdx <= $toIdx) {
                return $dayIdx >= $fromIdx && $dayIdx <= $toIdx;
            }
            return $dayIdx >= $fromIdx || $dayIdx <= $toIdx;
        }

        return str_contains($daysField, $dayName);
    }

    private function resolveStatus(bool $isWorkDay, $checkIn, $checkOut, string $date): array
    {
        if (!$isWorkDay) {
            return ['إجازة', 'leave'];
        }
        if ($checkIn && $checkOut) {
            return ['مكتمل', 'completed'];
        }
        if ($checkIn && !$checkOut) {
            // يوم سابق بدون خروج → نعتبره غير مكتمل / غياب اختياري
            if ($date < today()->toDateString()) {
                return ['غائب', 'absent']; // أو 'دخول فقط'
            }
            return ['متواجد الآن', 'present'];
        }
        return ['غائب', 'absent'];
    }


}
