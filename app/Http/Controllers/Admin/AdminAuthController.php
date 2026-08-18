<?php

namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Models\Admin;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\Session;
use App\Models\SessionBooking;
use App\Models\StaffAttendance;
use App\Models\Sale;
use App\Models\Subscription;
use App\Models\GymHall;
use Illuminate\Http\Request;
use App\Http\Controllers\Reception\ReceptionController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;


class AdminAuthController extends Controller
{
 
    public function login(Request $request)
    {
        $request->validate([
            'email'     => 'required|email',
            'password'  => 'required|string|min:6',
            'role'      => 'required|in:admin,reception',
        ]);

        $user = User::where('email', $request->email)->first();

        // 1. التحقق من بيانات الدخول
        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'status'  => 401,
                'message' => 'بيانات الدخول خاطئة.. قم بالتحقق من البريد الالكتيروني او كلمة المرور '
            ], 401);
        }

        // 2. التحقق من حالة الحساب
        if ($user->status !== 'active' || $user->active_at != 1) {
            return response()->json([
                'status'  => 403,
                'message' => 'حسابك غير مفعل'
            ], 403);
        }

        // 3. التحقق من الدور حسب عمود role في جدول users (الأساسي الآن)
        if ($user->role !== $request->role) {
            Log::warning('محاولة دخول إداري خاطئة - role column', [
                'email'          => $user->email,
                'requested_role' => $request->role,
                'db_role'        => $user->role,
            ]);

            return response()->json([
                'status'  => 403,
                'message' => 'غير مصرح لك الدخول بهذا الدور.. قم بالتحقق من المعلومات بشكل صحيح',
                'your_role' => $user->role
            ], 403);
        }

        
        // إنشاء توكن
        $token = $user->createToken('admin_auth_token')->plainTextToken;

        return response()->json([
            'status' => 200,
            'message' => 'تم تسجيل الدخول كإدارة بنجاح',
            'access_token' => $token,
            'token_type' => 'Bearer',
            'data' => [
                'user' => [
                    'id'            => $user->id,
                    'full_name'     => $user->full_name,
                    'email'         => $user->email,
                    'phone'         => $user->phone,
                    'role'          => $user->role,           // من جدول users
                    'profile_image' => $user->profile_image,
                ],
                'roles'       => $user->getRoleNames(),       // من Spatie
                'permissions' => $user->getAllPermissions()->pluck('name'),
            ]
        ]);
    }


    public function logout(Request $request) {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'Logged out successfully']);
    }
        

    //to activated users account:
    public function activateUser($id)
    {
        $user = User::findOrFail($id);
        $user->update(['active_at' => 1]); 

        return response()->json(['message' => 'تم تفعيل الحساب بنجاح']);
    }



    public function dashboard()
    {
        $admin = auth()->user();
        if (!$admin || $admin->role !== 'admin') {
            return response()->json(['message' => 'غير مصرح'], 403);
        }

        $today          = now()->toDateString();
        $thisMonthStart = now()->startOfMonth()->toDateTimeString();
        $thisMonthEnd   = now()->endOfMonth()->toDateTimeString();
        $in7Days        = now()->addDays(7)->toDateString();

        if (method_exists(Session::class, 'updateExpiredSessions')) {
            Session::updateExpiredSessions();
        }

        $userStats = User::selectRaw("
            COUNT(CASE WHEN role = 'trainee' THEN 1 END) as total_trainees,
            COUNT(CASE WHEN role = 'trainee' AND status = 'active' THEN 1 END) as active_trainees,
            COUNT(CASE WHEN role = 'trainee' AND status != 'active' THEN 1 END) as inactive_trainees,
            COUNT(CASE WHEN role = 'coach' THEN 1 END) as total_coaches,
            COUNT(CASE WHEN role = 'coach' AND status = 'active' AND active_at = 1 THEN 1 END) as active_coaches,
            COUNT(CASE WHEN role = 'coach' AND status = 'pending' THEN 1 END) as pending_coaches,
            COUNT(CASE WHEN role = 'coach' AND status = 'rejected' THEN 1 END) as rejected_coaches,
            COUNT(CASE WHEN role = 'reception' THEN 1 END) as total_receptionists,
            COUNT(CASE WHEN role = 'reception' AND status = 'active' THEN 1 END) as active_receptionists
        ")->first();

        $subStats = Subscription::selectRaw("
            COUNT(CASE WHEN status = 'paid' AND expires_at >= ? THEN 1 END) as active,
            COUNT(CASE WHEN status = 'paid' AND expires_at < ? THEN 1 END) as expired,
            COUNT(CASE WHEN status = 'paid' AND DATE(expires_at) BETWEEN ? AND ? THEN 1 END) as expiring_soon
        ", [$today, $today, $today, $in7Days])->first();

        $sessionStats = Session::whereDate('session_date', $today)
            ->selectRaw("
                COUNT(*) as total,
                COUNT(CASE WHEN status = 'scheduled' THEN 1 END) as scheduled,
                COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed,
                COUNT(CASE WHEN status = 'cancelled' THEN 1 END) as cancelled,
                COUNT(CASE WHEN status = 'ongoing' THEN 1 END) as ongoing,
                COUNT(CASE WHEN type = 'group' THEN 1 END) as group_count,
                COUNT(CASE WHEN type = 'individual' THEN 1 END) as individual_count
            ")->first();

        //المبيعات
        $saleStats = Sale::where('status', 'completed')
            ->selectRaw("
                COUNT(CASE WHEN DATE(created_at) = ? THEN 1 END) as count_today,
                COALESCE(SUM(CASE WHEN DATE(created_at) = ? THEN total_amount ELSE 0 END), 0) as amount_today,
                COUNT(CASE WHEN created_at BETWEEN ? AND ? THEN 1 END) as count_month,
                COALESCE(SUM(CASE WHEN created_at BETWEEN ? AND ? THEN total_amount ELSE 0 END), 0) as amount_month
            ", [$today, $today, $thisMonthStart, $thisMonthEnd, $thisMonthStart, $thisMonthEnd])
            ->first();

        //حجوزات اليوم
        $bookingStats = SessionBooking::whereHas('session', fn ($q) => $q->whereDate('session_date', $today))
            ->selectRaw("
                COUNT(*) as total,
                COUNT(CASE WHEN status = 'attended' THEN 1 END) as attended,
                COUNT(CASE WHEN status = 'no_show' THEN 1 END) as no_show,
                COUNT(CASE WHEN status = 'booked' THEN 1 END) as booked
            ")->first();

        $attendanceRate = ($bookingStats->total ?? 0) > 0
            ? round(($bookingStats->attended / $bookingStats->total) * 100)
            : 0;

        //حضور الكوتشات اليوم
        $coachCheckInsToday = StaffAttendance::whereDate('recorded_at', $today)
            ->where('type', 'check_in')
            ->whereHas('user', fn ($q) => $q->where('role', 'coach'))
            ->count();

        $coachCheckOutsToday = StaffAttendance::whereDate('recorded_at', $today)
            ->where('type', 'check_out')
            ->whereHas('user', fn ($q) => $q->where('role', 'coach'))
            ->count();

        // الصالات
// كل الصالات
$totalHalls = GymHall::count();

// صالات عليها جلسة اليوم (غير ملغاة) = محجوزة الآن/اليوم
$reservedHallIds = Session::whereDate('session_date', $today)
    ->where('status', '!=', 'cancelled')
    ->whereNotNull('hall_id')
    ->distinct()
    ->pluck('hall_id');

$reservedHalls  = $reservedHallIds->count();
$availableHalls = max(0, $totalHalls - $reservedHalls);

// (اختياري) تفاصيل الصالات المحجوزة اليوم
$reservedHallsList = GymHall::whereIn('id', $reservedHallIds)
    ->get(['id', 'name', 'type', 'capacity'])
    ->map(function ($hall) use ($today) {
        $sessions = Session::where('hall_id', $hall->id)
            ->whereDate('session_date', $today)
            ->where('status', '!=', 'cancelled')
            ->orderBy('start_time')
            ->get(['id', 'title', 'start_time', 'end_time', 'status', 'coach_id']);

        return [
            'id'       => $hall->id,
            'name'     => $hall->name,
            'type'     => $hall->type,
            'capacity' => $hall->capacity,
            'sessions' => $sessions->map(fn ($s) => [
                'id'         => $s->id,
                'title'      => $s->title,
                'start_time' => substr((string) $s->start_time, 0, 5),
                'end_time'   => substr((string) $s->end_time, 0, 5),
                'status'     => $s->status,
            ])->values(),
        ];
    });
            // آخر المبيعات
        $recentSales = Sale::with([
                'user:id,full_name,phone,membership_number',
                'seller:id,full_name', 
            ])
            ->latest('created_at')
            ->limit(10)
            ->get()
            ->map(function ($sale) {
                $customerName = $sale->customer_name
                    ?? $sale->user?->full_name
                    ?? 'زائر';

                $customerPhone = $sale->customer_phone
                    ?? $sale->user?->phone
                    ?? null;

                $soldByName = $sale->soldBy?->full_name
                    ?? $sale->seller?->full_name
                    ?? null;

                return [
                    'id'                   => $sale->id,
                    'user_id'              => $sale->user_id,
                    'customer_name'        => $customerName,
                    'customer_phone'       => $customerPhone,
                    'membership_number'    => $sale->user?->membership_number,
                    'is_member'            => (bool) $sale->user_id,
                    'total_amount'         => (float) $sale->total_amount,
                    'payment_method'       => $sale->payment_method,
                    'payment_method_label' => match ($sale->payment_method) {
                        'cash'     => 'كاش',
                        'online'   => 'أونلاين',
                        'card'     => 'بطاقة',
                        'transfer' => 'تحويل',
                        default    => $sale->payment_method,
                    },
                    'status'               => $sale->status,
                    'status_label'         => match ($sale->status) {
                        'completed' => 'مكتملة',
                        'cancelled' => 'ملغاة',
                        'refunded'  => 'مسترجعة',
                        default     => $sale->status,
                    },
                    'sold_by'              => $soldByName,
                    'created_at'           => $sale->created_at->format('Y-m-d H:i'),
                    'time'                 => $sale->created_at->diffForHumans(),
                ];
        });
        
        
        //لسات اليوم 
        $todaySessionsList = Session::with([
                'coach:id,full_name,profile_image',
                'hall:id,name',
            ])
            ->withCount(['bookings as booked_count' => function ($q) {
                $q->whereIn('status', ['booked', 'attended']);
            }])
            ->whereDate('session_date', $today)
            ->orderBy('start_time')
            ->limit(10)
            ->get()
            ->map(fn ($session) => [
                'id'           => $session->id,
                'title'        => $session->title,
                'type'         => $session->type,
                'type_label'   => $session->type === 'group' ? 'جماعية' : 'فردية',
                'start_time'   => substr((string) $session->start_time, 0, 5),
                'end_time'     => substr((string) $session->end_time, 0, 5),
                'status'       => $session->status,
                'status_label' => $session->status_label ?? $session->status,
                'coach'        => $session->coach?->full_name,
                'hall'         => $session->hall?->name,
                'booked_count' => $session->booked_count ?? 0,
                'capacity'     => $session->capacity,
            ]);

        // Response
        return response()->json([
            'status' => 200,
            'data'   => [
                'overview' => [
                    'trainees' => [
                        'total'    => (int) $userStats->total_trainees,
                        'active'   => (int) $userStats->active_trainees,
                        'inactive' => (int) $userStats->inactive_trainees,
                    ],
                    'coaches' => [
                        'total'  => (int) $userStats->total_coaches,
                        'active' => (int) $userStats->active_coaches,
                    ],
                    'receptionists' => [
                        'total'  => (int) $userStats->total_receptionists,
                        'active' => (int) $userStats->active_receptionists,
                    ],
                    'subscriptions' => [
                        'active'        => (int) $subStats->active,
                        'expired'       => (int) $subStats->expired,
                        'expiring_soon' => (int) $subStats->expiring_soon,
                    ],
                    'sessions_today' => [
                        'total'     => (int) $sessionStats->total,
                        'scheduled' => (int) $sessionStats->scheduled,
                        'ongoing'   => (int) ($sessionStats->ongoing ?? 0),
                        'completed' => (int) $sessionStats->completed,
                        'cancelled' => (int) $sessionStats->cancelled,
                    ],
                    'sales_today' => [
                        'count'  => (int) $saleStats->count_today,
                        'amount' => (float) $saleStats->amount_today,
                    ],
                    'sales_this_month' => [
                        'count'  => (int) $saleStats->count_month,
                        'amount' => (float) $saleStats->amount_month,
                    ],
                ],
                'sessions' => [
                    'group_today'          => (int) $sessionStats->group_count,
                    'individual_today'     => (int) $sessionStats->individual_count,
                    'total_bookings_today' => (int) $bookingStats->total,
                    'booked_today'         => (int) ($bookingStats->booked ?? 0),
                    'attended_today'       => (int) $bookingStats->attended,
                    'no_show_today'        => (int) $bookingStats->no_show,
                    'attendance_rate'      => $attendanceRate,
                ],
                'coach_attendance' => [
                    'check_ins_today'  => $coachCheckInsToday,
                    'check_outs_today' => $coachCheckOutsToday,
                ],
                'halls' => [
                    'total'     => $totalHalls,
                    'available' => $availableHalls,
                    'reserved'  => $reservedHalls,
                    'reserved_list' => $reservedHallsList, // اختياري للواجهة
                ],
                'coach_requests' => [
                    'pending'  => (int) $userStats->pending_coaches,
                    'rejected' => (int) $userStats->rejected_coaches,
                ],
                'latest_sales'   => $recentSales,
                'today_sessions' => $todaySessionsList,
            ],
        ]);
    }
 }
