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

    public function sendEmailToCoach(Request $request, $id)
    {
        if (auth()->user()->role !== 'admin') {
            return response()->json(['message' => 'غير مصرح'], 403);
        }

        $request->validate([
            'subject' => 'required|string|max:255',
            'message' => 'required|string|max:5000',
        ]);

        $coach = User::where('role', 'coach')->find($id);

        if (!$coach) {
            return response()->json(['message' => 'الكوتش غير موجود'], 404);
        }

        if (!$coach->email) {
            return response()->json(['message' => 'لا يوجد بريد إلكتروني لهذا الكوتش'], 400);
        }

        try {
            Mail::to($coach->email)
                ->bcc('solaimanesmaeel334@gmail.com') // إيميلك
                ->send(new CoachApplicationMail(
                    $request->subject,
                    $request->message,
                    $coach->full_name
                ));

            return response()->json([
                'status'  => 200,
                'message' => 'تم إرسال الإيميل بنجاح',
                'data'    => [
                    'coach_id'    => $coach->id,
                    'coach_name'  => $coach->full_name,
                    'coach_email' => $coach->email,
                    'subject'     => $request->subject,
                    'message'     => $request->message,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status'  => 500,
                'message' => 'فشل إرسال الإيميل',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

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



}
