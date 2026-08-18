<?php

namespace App\Http\Controllers\Admin; // أو حسب مسار الـ Controllers لديك

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Salary;
use App\Models\Sale;
use App\Models\Session;
use App\Models\StaffAttendance;
use App\Models\Product;

use App\Models\Subscription;
use App\Models\Attendance;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use App\Services\ActivityService;
use App\Models\ActivityLog;

class AdminReceptionistController extends Controller
{
    public function staticsData()
    {
        if (auth()->user()->role !== 'admin') {
            return response()->json(['message' => 'غير مصرح'], 403);
        }

        $totalStaff     = User::role('reception')->count();
        $activated     = User::role('reception')->where('active_at', 1)->count(); // أو حسب الحضور
        $onLeave        = User::role('reception')->where('status', 'on_leave')->count();
        $pendingSalaries = Salary::whereHas('user', fn($q) => $q->role('reception'))
                                ->where('status', 'pending')->count();

        return response()->json([
            'status' => 200,
            'data' => [
                'total_staff'      => $totalStaff,
                'activated_receptions'=> $activated,
                'on_leave'         => $onLeave,
                'pending_salaries' => $pendingSalaries,
            ]
        ]);
    }

    // إضافة موظف استقبال جديد بواسطة الأدمن
    public function store(Request $request): \Illuminate\Http\JsonResponse
    {
        $admin = auth()->user();
        if (!$admin || !in_array($admin->role, ['admin'])) {
            return response()->json(['message' => 'غير مصرح لك، هذه الصلاحية للأدمن أو موظف الاستقبال فقط'], 403);
        }

        $validator = Validator::make($request->all(), [
            'full_name'  => ['required', 'string', 'max:50'],
            'email'     => ['required', 'email:rfc,dns', 'max:50', 'unique:users'],
            'phone'     => ['required', 'string', 'regex:/^963[0-9]{9}$/', 'unique:users'],
            'password'  => ['required', 'string', 'min:8', 'confirmed'],
            'salary'    => ['required', 'numeric', 'min:0'],
            'days'      => ['required', 'string'],
            'work_name' => ['required', 'string'],
            'start_time'=> ['required', 'date_format:H:i:s'],
            'end_time'  => ['required', 'date_format:H:i:s'],
        ],[
            'phone.regex'   => 'رقم الهاتف يجب أن يبدأ بـ 963 ويحتوي على 9 أرقام بعده.',
            'email.email'   => 'البريد الإلكتروني غير صالح او غير حقيقي.',
            'email.unique'  => 'البريد الإلكتروني مستخدم من قبل.',
            'phone.unique'  => 'رقم الهاتف مستخدم من قبل.',
            'salary.required' => 'حقل الراتب مطلوب.',
            'days.required' => 'أيام العمل مطلوبة.',
            'start_time.date_format' => 'صيغة وقت البدء يجب أن تكون H:i:s',
            'end_time.date_format'   => 'صيغة وقت الانتهاء يجب أن تكون H:i:s',
        ]);

        if ($validator->fails()) {
            $allErrors = collect($validator->errors()->all())->implode(' - ');
            return response()->json(['message' => $allErrors], 422);
        }

        $user = User::create([
            'full_name'         => $request->full_name,
            'email'             => $request->email,
            'phone'             => $request->phone,
            'password'          => Hash::make($request->password),
            'role'              => 'reception',
            'status'            => 'active',
            'active_at'         => 1,
            'membership_number' => 'REC-' . mt_rand(10000, 99999),
        ]);

        $user->salaries()->create([
            'base_salary' => $request->salary,
            'net_salary'  => $request->salary,
            'month'       => now()->format('Y-m'), 
        ]);

        $user->workSchedules()->create([
            'days'       => $request->days,
            'work_name'  => $request->work_name,
            'start_time' => $request->start_time,
            'end_time'   => $request->end_time,
        ]);

        if (method_exists($user, 'assignRole')) {
            $user->assignRole('reception');
        }

        $user->load(['workSchedules', 'salaries']);

        return response()->json([
            'status'    => 201,
            'message'   => 'تم إضافة موظف الاستقبال مع جدول العمل والراتب بنجاح',
            'user_id'   => $user->id,
            'data'      => $user,
        ], 201);
    } 
    
    public function update(Request $request, $id): \Illuminate\Http\JsonResponse
     {
        $admin = auth()->user();
        if (!$admin || !in_array($admin->role, ['admin'])) {
            return response()->json(['message' => 'غير مصرح لك، هذه الصلاحية للأدمن أو موظف الاستقبال فقط'], 403);
        }

        $user = User::where('role', 'reception')->find($id);
        if (!$user) {
            return response()->json(['message' => 'موظف الاستقبال غير موجود'], 404);
        }

        $validator = Validator::make($request->all(), [
            'full_name'  => ['sometimes', 'required', 'string', 'max:50'],
            'email'     => ['sometimes', 'required', 'email:rfc,dns', 'max:50', 'unique:users,email,' . $id],
            'phone'     => ['sometimes', 'required', 'string', 'regex:/^963[0-9]{9}$/', 'unique:users,phone,' . $id],
            'status'    => ['sometimes', 'required', 'in:active,rejected,banned,on_leave'],
            'password'  => ['nullable', 'string', 'min:8', 'confirmed'],
            'salary'    => ['sometimes', 'required', 'numeric', 'min:0'],
            'days'      => ['sometimes', 'required', 'string'],
            'work_name' => ['sometimes', 'required', 'string'],
            'start_time'=> ['sometimes', 'required', 'date_format:H:i:s'],
            'end_time'  => ['sometimes', 'required', 'date_format:H:i:s'],
        ],[
            'phone.regex'   => 'رقم الهاتف يجب أن يبدأ بـ 963 ويحتوي على 9 أرقام بعده.',
            'email.email'   => 'البريد الإلكتروني غير صالح او غير حقيقي.',
            'email.unique'  => 'البريد الإلكتروني مستخدم من قبل.',
            'phone.unique'  => 'رقم الهاتف مستخدم من قبل.',
            'salary.required' => 'حقل الراتب مطلوب.',
            'days.required' => 'أيام العمل مطلوبة.',
            'start_time.date_format' => 'صيغة وقت البدء يجب أن تكون H:i:s',
            'end_time.date_format'   => 'صيغة وقت الانتهاء يجب أن تكون H:i:s',
        ]);

        if ($validator->fails()) {
            $allErrors = collect($validator->errors()->all())->implode(' - ');
            return response()->json(['message' => $allErrors], 422);
        }

        $updateData = [];
        if ($request->has('full_name')) $updateData['full_name'] = $request->full_name;
        if ($request->has('email')) $updateData['email'] = $request->email;
        if ($request->has('phone')) $updateData['phone'] = $request->phone;
        if ($request->has('status')) {
            $updateData['status'] = $request->status;
            $updateData['active_at'] = ($request->status === 'active') ? 1 : 0;}
        if ($request->filled('password')) $updateData['password'] = Hash::make($request->password);

        if (!empty($updateData)) {
            $user->update($updateData);
        }

        if ($request->has('salary')) {
            $latestSalary = $user->salaries()->latest()->first();
            if ($latestSalary) {
                $latestSalary->update([
                    'base_salary' => $request->salary,
                    'net_salary'  => $request->salary,
                ]);
            } else {
                $user->salaries()->create([
                    'base_salary' => $request->salary,
                    'net_salary'  => $request->salary,
                    'month'       => now()->format('Y-m'),
                ]);
            }
        }

        if ($request->hasAny(['days', 'work_name', 'start_time', 'end_time'])) {
            $schedule = $user->workSchedules()->first();
            $scheduleData = [
                'days'       => $request->input('days', $schedule?->days),
                'work_name'  => $request->input('work_name', $schedule?->work_name),
                'start_time' => $request->input('start_time', $schedule?->start_time),
                'end_time'   => $request->input('end_time', $schedule?->end_time),
            ];

            if ($schedule) {
                $schedule->update($scheduleData);
            } else {
                $user->workSchedules()->create($scheduleData);
            }
        }

        $user->load(['workSchedules', 'salaries']);

        return response()->json([
            'status'    => 200,
            'message'   => 'تم تحديث بيانات موظف الاستقبال بنجاح',
            'user_id'   => $user->id,
            'data'      => $user,
        ], 200);
    }

    public function destroy($id): \Illuminate\Http\JsonResponse
    {
        $admin = auth()->user();
        if (!$admin || !in_array($admin->role, ['admin', 'reception'])) {
            return response()->json(['message' => 'غير مصرح لك، هذه الصلاحية للأدمن أو موظف الاستقبال فقط'], 403);
        }

        $user = User::where('role', 'reception')->find($id);
        if (!$user) {
            return response()->json(['message' => 'موظف الاستقبال غير موجود'], 404);
        }

        $user->salaries()->delete();
        $user->workSchedules()->delete();

        $user->update([
            'email' => 'deleted_' . $user->id . '_' . $user->email,
            'phone' => 'deleted_' . $user->id . '_' . $user->phone,
        ]);

        $user->delete(); 

        return response()->json([
            'status'  => 200,
            'message' => 'تم حذف موظف الاستقبال وإتاحة بريده ورقمه للتسجيل الجديد بنجاح',
        ], 200);
    }      

    public function index(Request $request)
    {
        $admin = auth()->user();
        if ($admin->role !== 'admin') {
            return response()->json(['message' => 'غير مصرح لك، هذه الصلاحية للأدمن فقط'], 403);
        }

        $query = User::where('role', 'reception')
            ->with(['workSchedules', 'salaries']);

        if ($request->has('search') && !empty($request->input('search'))) {
            $search = $request->input('search');
            $query->where(function($q) use ($search) {
                $q->where('full_name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhere('membership_number', 'like', "%{$search}%"); 
            });
        }

        $receptions = $query->get()->map(function ($reception) {
            $schedules = $reception->workSchedules->map(function ($schedule) {
                return [
                    'days' => $schedule->days,
                    'work_name' => $schedule->work_name,
                    'start_time' => $schedule->start_time,
                    'end_time' => $schedule->end_time,
                ];
            });

            $latestSalary = $reception->salaries()->latest()->first();
            $salaryValue = $latestSalary ? $latestSalary->net_salary : 0.00;

            return [
                'id' => $reception->id,
                'membership_number' => $reception->membership_number, 
                'full_name' => $reception->full_name,                
                'phone' => $reception->phone,
                'email' => $reception->email,
                'status' => $reception->status,
                'salary' => $salaryValue,
                'work_schedules' => $schedules,                          
            ];
        });

        $message = $receptions->isEmpty() 
            ? 'لا توجد نتائج مطابقة للبحث أو لا يوجد موظفون' 
            : 'تم جلب قائمة موظفي الاستقبال بنجاح';

        return response()->json([
            'status' => 200,
            'message' => $message,
            'data' => $receptions
        ]);
    }

    public function show($id)
    {
        $admin = auth()->user();
        if ($admin->role !== 'admin') {
            return response()->json(['message' => 'غير مصرح لك، هذه الصلاحية للأدمن فقط'], 403);
        }

        $receptionist = User::where('role', 'reception')
            ->with(['workSchedules', 'salaries'])
            ->find($id);

        if (!$receptionist) {
            return response()->json(['message' => 'موظف الاستقبال غير موجود'], 404);
        }

        $schedules = $receptionist->workSchedules->map(function ($schedule) {
            return [
                'days' => $schedule->days,
                'work_name' => $schedule->work_name,
                'start_time' => $schedule->start_time,
                'end_time' => $schedule->end_time,
            ];
        });

        $latestSalary = $receptionist->salaries()->latest()->first();
        $salaryValue = $latestSalary ? $latestSalary->net_salary : 0.00;

        return response()->json([
            'status' => 200,
            'message' => 'تم جلب تفاصيل موظف الاستقبال بنجاح',
            'data' => [
                'id' => $receptionist->id,
                'membership_number' => $receptionist->membership_number,
                'full_name' => $receptionist->full_name,
                'email' => $receptionist->email,
                'phone' => $receptionist->phone,
                'gender' => $receptionist->gender,
                'age' => $receptionist->age,
                'status' => $receptionist->status,
                'salary' => $salaryValue,
                'work_schedules' => $schedules,
            ]
        ], 200);
    }

    // 6. تغيير حالة الحساب (تفعيل / حظر أو تعليق)
    public function toggleStatus(Request $request, $id)
    {
        $admin = auth()->user();
        if ($admin->role !== 'admin') {
            return response()->json(['message' => 'غير مصرح لك، هذه الصلاحية للأدمن فقط'], 403);
        }

        $receptionist = User::where('role', 'reception')->find($id);

        if (!$receptionist) {
            return response()->json(['message' => 'موظف الاستقبال غير موجود'], 404);
        }

        $validator = Validator::make($request->all(), [
            'status' => ['required', 'in:active,rejected,banned,on_leave'],
        ], [
            'status.required' => 'حقل الحالة مطلوب.',
            'status.in'       => 'حقل الحالة يجب أن يكون إحدى القيم التالية: active, rejected, banned.',
        ]);

        if ($validator->fails()) {
            $allErrors = collect($validator->errors()->all())->implode(' - ');
            return response()->json(['message' => $allErrors], 422);
        }

        $receptionist->status = $request->status;
        
        $receptionist->active_at = ($request->status === 'active') ? 1 : 0;
        $receptionist->save();

        return response()->json([
            'status'  => 200,
            'message' => 'تم تحديث حالة الحساب بنجاح',
            'data'    => $receptionist
        ], 200);
    }

    public function activity($id, Request $request)
    {
        if (auth()->user()->role !== 'admin') {
            return response()->json(['message' => 'غير مصرح'], 403);
        }

        $user = User::role('reception')->find($id);
        if (!$user) {
            return response()->json(['message' => 'الموظف غير موجود'], 404);
        }

        $query = ActivityLog::where('user_id', $id)->latest();

        if ($request->filled('date')) {
            $query->whereDate('created_at', $request->date);
        }

        $activities = $query->take(50)->get()->map(function ($log) {
            return [
                'id'      => $log->id,
                'time'    => $log->created_at->format('H:i'),
                'date'    => $log->created_at->format('Y-m-d'),
                'action'  => $log->action_label,
                'details' => $log->details,
                'icon'    => $log->icon,
                'properties' => $log->properties,
            ];
        });

        return response()->json([
            'status' => 200,
            'data'   => $activities
        ]);
    }

    public function receptionistSubscriptions(Request $request, $id): \Illuminate\Http\JsonResponse
     {
        $admin = auth()->user();
        if (!$admin || $admin->role !== 'admin') {
            return response()->json(['message' => 'غير مصرح لك، هذه الصلاحية للأدمن فقط'], 403);
        }

        $receptionist = User::where('role', 'reception')->find($id);
        if (!$receptionist) {
            return response()->json(['message' => 'موظف الاستقبال غير موجود'], 404);
        }

        $subscriptionIds = ActivityLog::where('user_id', $id)
            ->where('subject_type', Subscription::class)
            ->pluck('subject_id')
            ->unique();

        $query = Subscription::whereIn('id', $subscriptionIds);

        if ($request->filled('from_date') && $request->filled('to_date')) {
            $query->whereBetween('created_at', [$request->from_date, $request->to_date]);
        }

        $totalNewSubscriptions = (clone $query)->count();
        $totalSales = (clone $query)->sum('price'); 

        $subscriptions = $query->latest()->paginate(10);

        $formattedSubscriptions = collect($subscriptions->items())->map(function ($sub) {
            return [
                'id' => $sub->id,
                'subscriber_name' => $sub->user->full_name ?? 'غير معروف',
                'subscription_type' => $sub->plan->name ?? 'اشتراك',
                'price' => $sub->price,
                'date' => $sub->created_at->format('Y-m-d'),
                'status' => $sub->status, 
            ];
        });

        return response()->json([
            'status' => 200,
            'message' => 'تم جلب اشتراكات موظف الاستقبال بنجاح',
            'statistics' => [
                'new_subscriptions' => $totalNewSubscriptions,
                'total_sales' => $totalSales,
            ],
            'subscriptions' => [
                'current_page' => $subscriptions->currentPage(),
                'data' => $formattedSubscriptions,
                'last_page' => $subscriptions->lastPage(),
                'total' => $subscriptions->total(),
            ]
        ], 200);
    }

    public function receptionistSummary(Request $request, $id): \Illuminate\Http\JsonResponse
     {
        $admin = auth()->user();
        if (!$admin || $admin->role !== 'admin') {
            return response()->json(['message' => 'غير مصرح لك، هذه الصلاحية للأدمن فقط'], 403);
        }

        $receptionist = User::where('role', 'reception')->find($id);
        if (!$receptionist) {
            return response()->json(['message' => 'موظف الاستقبال غير موجود'], 404);
        }

        $fromDate = $request->input('from_date', now()->startOfMonth());
        $toDate = $request->input('to_date', now()->endOfMonth());

        $subscriptionIds = ActivityLog::where('user_id', $id)
            ->where('subject_type', Subscription::class)
            ->pluck('subject_id')
            ->unique();

        $subQuery = Subscription::whereIn('id', $subscriptionIds)
            ->whereBetween('created_at', [$fromDate, $toDate]);

        $totalSales = (clone $subQuery)->sum('price');
        $newSubscriptionsCount = (clone $subQuery)->count();

        $lastLogin = ActivityLog::where('user_id', $id)
            ->where(function($q) {
                $q->where('action', 'like', '%login%')
                  ->orWhere('action_label', 'like', '%دخول%');
            })
            ->latest()
            ->value('created_at');

        if (!$lastLogin) {
            $lastLogin = ActivityLog::where('user_id', $id)->latest()->value('created_at');
        }

        return response()->json([
            'status' => 200,
            'message' => 'تم جلب ملخص نشاطات الموظف بنجاح',
            'data' => [
                'employee_name' => $receptionist->full_name,
                'last_login' => $lastLogin ? \Carbon\Carbon::parse($lastLogin)->format('Y-m-d h:i A') : 'لا يوجد دخول سابق',
                'summary_cards' => [
                    [
                        'title' => 'آخر دخول',
                        'value' => $lastLogin ? \Carbon\Carbon::parse($lastLogin)->format('h:i A') : '-',
                        'icon' => 'clock'
                    ],
                    [
                        'title' => 'الاشتراكات الجديدة / المجددة',
                        'value' => $newSubscriptionsCount,
                        'icon' => 'user-plus'
                    ],
                    [
                        'title' => 'إجمالي المبيعات',
                        'value' => $totalSales . ' $',
                        'icon' => 'dollar'
                    ]
                ]
            ]
        ], 200);
    }

    //delete
    public function activityLog($id)
    {
        $admin = auth()->user();
        if (!$admin || !in_array($admin->role, ['admin'])) {
            return response()->json(['message' => 'غير مصرح لك، هذه الصلاحية للأدمن أو موظف الاستقبال فقط'], 403);
        }

        $receptionist = User::where('role', 'reception')->find($id);

        if (!$receptionist) {
            return response()->json(['message' => 'موظف الاستقبال غير موجود'], 404);
        }

        $activities = \Spatie\Activitylog\Models\Activity::causedBy($receptionist)
            ->latest()
            ->paginate(10);

        return response()->json([
            'status' => 200,
            'message' => 'تم جلب سجل نشاطات موظف الاستقبال بنجاح',
            'receptionist_name' => $receptionist->full_name,
            'activities' => $activities
        ], 200);
    }

    public function receptionistActivity($id): \Illuminate\Http\JsonResponse
    {
        $admin = auth()->user();
        if (!$admin || !in_array($admin->role, ['admin'])) {
            return response()->json(['message' => 'غير مصرح لك'], 403);
        }

        $receptionist = User::where('role', 'reception')->find($id);
        if (!$receptionist) {
            return response()->json(['message' => 'موظف الاستقبال غير موجود'], 404);
        }

        $activities = \App\Models\Activity::where('causer_type', User::class)
            ->where('causer_id', $receptionist->id)
            ->latest()
            ->get()
            ->map(function ($activity) {
                $properties = is_string($activity->properties) 
                    ? json_decode($activity->properties, true) 
                    : $activity->properties;

                $rawAttributes = $properties['attributes'] ?? $properties;

                $targetName = $rawAttributes['full_name'] ?? $rawAttributes['name'] ?? null;
                $targetEmail = $rawAttributes['email'] ?? null;

                $title = match ($activity->description) {
                    'created' => 'إضافة سجل جديد',
                    'updated' => 'تعديل بيانات',
                    'deleted' => 'حذف سجل',
                    default   => $activity->description, 
                };

                return [
                    'id'          => $activity->id,
                    'title'       => $title,                            
                    'log_name'    => $activity->log_name ?? 'general',  
                    'target_summary' => [                               
                        'name'  => $targetName,
                        'email' => $targetEmail,
                    ],
                    'details'     => $rawAttributes,                   
                    'date'        => $activity->created_at->format('Y-m-d H:i:s'),
                ];
            });

        return response()->json([
            'status'            => 200,
            'message'           => 'تم جلب سجل النشاطات بنجاح',
            'receptionist_name' => $receptionist->full_name,
            'total_activities'  => $activities->count(),
            'activities'        => $activities
        ], 200);
    }

    public function dashboard()
    {
        $reception = auth()->user();

        if (!in_array($reception->role, ['admin', 'reception'])) {
            return response()->json([
                'status'  => 403,
                'message' => 'غير مصرح',
            ], 403);
        }

        $today = now()->toDateString();

        if (method_exists(Session::class, 'updateExpiredSessions')) {
            Session::updateExpiredSessions();
        }

        $membersCount = User::where('role', 'trainee')
            ->where('status', 'active')
            ->count();

        $todaySessionsCount = Session::whereDate('session_date', $today)
            ->where('status', '!=', 'cancelled')
            ->count();

        $upcomingSessionsCount = Session::whereDate('session_date', $today)
            ->where('status', 'scheduled')
            ->count();

        $expiredSubscriptionsCount = Subscription::where('status', 'paid')
            ->whereDate('expires_at', '<', $today)
            ->count();

        $expiringSubscriptionsCount = Subscription::where('status', 'paid')
            ->whereDate('expires_at', '>=', $today)
            ->whereDate('expires_at', '<=', now()->addDays(7)->toDateString())
            ->count();

            //sales in today
            $todaySalesData = Sale::whereDate('created_at', $today)
            ->selectRaw('COALESCE(SUM(total_amount), 0) as total_sum, COUNT(id) as total_count')
            ->first();

        $todaySales      = (float) ($todaySalesData->total_sum ?? 0);
        $todaySalesCount = (int) ($todaySalesData->total_count ?? 0);

        $coaches = User::where('role', 'coach')
            ->where('status', 'active')
            ->where('active_at', 1)
            ->get(['id', 'full_name', 'membership_number', 'profile_image']);

        $coachesTotal = $coaches->count();

        $latestAttendances = StaffAttendance::whereIn('user_id', $coaches->pluck('id'))
            ->whereDate('recorded_at', $today)
            ->orderByDesc('recorded_at')
            ->get()
            ->unique('user_id')
            ->keyBy('user_id');

        $coachesPresent = 0;

        $coachAttendance = $coaches->map(function ($coach) use ($latestAttendances, &$coachesPresent) {
            $last = $latestAttendances->get($coach->id);
            $isPresent = $last && $last->type === 'check_in';

            if ($isPresent) {
                $coachesPresent++;
            }

            return [
                'coach_id'           => $coach->id,
                'name'               => $coach->full_name,
                'membership_number'  => $coach->membership_number,
                'image'              => $coach->profile_image_url
                    ?? ($coach->profile_image ? asset('storage/' . $coach->profile_image) : null),
                'status'             => $isPresent ? 'present' : 'absent',
                'status_label'       => $isPresent ? 'حاضر' : 'غير حاضر',
                'last_action'        => $last?->type,
                'last_action_label'  => $last
                    ? ($last->type === 'check_in' ? 'دخول' : 'خروج')
                    : null,
                'last_time'          => $last?->recorded_at?->format('H:i'),
                'note'               => $last?->note,
            ];
        })->values();

        $todaySessions = Session::with([
                'coach:id,full_name,profile_image',
                'hall:id,name,type',
            ])
            ->withCount(['bookings as booked_count' => function ($q) {
                $q->whereIn('status', ['booked', 'attended']);
            }])
            ->whereDate('session_date', $today)
            ->where('status', '!=', 'cancelled')
            ->orderBy('start_time')
            ->get()
            ->map(function ($session) {
                return [
                    'id'            => $session->id,
                    'title'         => $session->title,
                    'type'          => $session->type,
                    'type_label'    => $session->type === 'group' ? 'جماعية' : 'فردية',
                    'start_time'    => substr((string) $session->start_time, 0, 5),
                    'end_time'      => substr((string) $session->end_time, 0, 5),
                    'status'        => $session->status,
                    'status_label'  => $session->status_label ?? $session->status,
                    'capacity'      => $session->capacity,
                    'booked_count'  => $session->booked_count ?? 0,
                    'available'     => ($session->capacity ?? 0) > ($session->booked_count ?? 0),
                    'coach'         => $session->coach ? [
                        'id'    => $session->coach->id,
                        'name'  => $session->coach->full_name,
                        'image' => $session->coach->profile_image_url
                            ?? ($session->coach->profile_image
                                ? asset('storage/' . $session->coach->profile_image)
                                : null),
                    ] : null,
                    'hall'          => $session->hall ? [
                        'id'   => $session->hall->id,
                        'name' => $session->hall->name,
                        'type' => $session->hall->type,
                    ] : null,
                ];
            });

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

        $mapSubscription = function ($subscription, bool $isExpired) {
            $endDate = \Carbon\Carbon::parse($subscription->expires_at);

            $item = [
                'id'         => $subscription->id,
                'user'       => $subscription->user ? [
                    'id'                => $subscription->user->id,
                    'name'              => $subscription->user->full_name,
                    'membership_number' => $subscription->user->membership_number,
                    'image'             => $subscription->user->profile_image_url
                        ?? ($subscription->user->profile_image
                            ? asset('storage/' . $subscription->user->profile_image)
                            : null),
                ] : null,
                'end_date'   => $endDate->format('Y-m-d'),
                'status'     => $isExpired ? 'expired' : 'expiring',
                'status_label' => $isExpired ? 'منتهي' : 'ينتهي قريباً',
            ];

            if ($isExpired) {
                $item['days_expired'] = $endDate->diffInDays(now());
            } else {
                $item['days_remaining'] = now()->startOfDay()->diffInDays($endDate->copy()->startOfDay());
            }

            return $item;
        };

        $expiredSubscriptions = Subscription::with('user:id,full_name,membership_number,profile_image')
            ->where('status', 'paid')
            ->whereDate('expires_at', '<', $today)
            ->orderByDesc('expires_at')
            ->limit(10)
            ->get()
            ->map(fn ($sub) => $mapSubscription($sub, true));

        $expiringSubscriptions = Subscription::with('user:id,full_name,membership_number,profile_image')
            ->where('status', 'paid')
            ->whereDate('expires_at', '>=', $today)
            ->whereDate('expires_at', '<=', now()->addDays(7)->toDateString())
            ->orderBy('expires_at')
            ->limit(5)
            ->get()
            ->map(fn ($sub) => $mapSubscription($sub, false));

        $lowStockProducts = Product::where('stock_quantity', '<=', 5)
            ->orderBy('stock_quantity')
            ->limit(10)
            ->get(['id', 'name', 'stock_quantity', 'price', 'image'])
            ->map(fn ($product) => [
                'id'          => $product->id,
                'name'        => $product->name,
                'stock'       => $product->stock_quantity,
                'price'       => (float) $product->price,
                'image'       => $product->image_url
                    ?? ($product->image ? asset('storage/' . $product->image) : null),
                'level'       => $product->stock_quantity <= 2 ? 'critical' : 'low',
                'level_label' => $product->stock_quantity <= 2 ? 'مخزون حرج' : 'مخزون منخفض',
            ]);

        $labels = [
            'coach_check_in'       => 'تسجيل دخول كوتش',
            'coach_check_out'      => 'تسجيل خروج كوتش',
            'sell_product'         => 'بيع منتج',
            'product_sale'         => 'بيع منتج',
            'renew_subscription'   => 'تجديد اشتراك',
            'create_subscription'  => 'إنشاء اشتراك',
            'register'             => 'تسجيل مستخدم',
            'update_trainee'       => 'تعديل متدرب',
            'create_user'          => 'إضافة عضو',
            'update_user'          => 'تعديل بيانات عضو',
            'payment'              => 'تسجيل دفعة',
        ];

        $recentActivity = ActivityLog::where('user_id', $reception->id)
            ->latest('created_at')
            ->limit(10)
            ->get()
            ->map(fn ($activity) => [
                'id'           => $activity->id,
                'action'       => $activity->action,
                'action_label' => $labels[$activity->action] ?? $activity->action_label ?? 'نشاط',
                'details'      => $activity->details,
                'icon'         => $activity->icon ?? 'activity',
                'created_at'   => $activity->created_at->format('Y-m-d H:i'),
                'time'         => $activity->created_at->diffForHumans(),
            ]);

        $alerts = [];

        if ($expiredSubscriptionsCount > 0) {
            $alerts[] = [
                'type'    => 'danger',
                'icon'    => 'subscription',
                'title'   => 'اشتراكات منتهية',
                'message' => "يوجد {$expiredSubscriptionsCount} اشتراك منتهي يحتاج إلى تجديد",
            ];
        }

        if ($expiringSubscriptionsCount > 0) {
            $alerts[] = [
                'type'    => 'warning',
                'icon'    => 'warning',
                'title'   => 'اشتراكات تنتهي قريباً',
                'message' => "يوجد {$expiringSubscriptionsCount} اشتراك سينتهي خلال 7 أيام",
            ];
        }

        if ($lowStockProducts->count() > 0) {
            $alerts[] = [
                'type'    => 'warning',
                'icon'    => 'product',
                'title'   => 'مخزون منخفض',
                'message' => 'يوجد ' . $lowStockProducts->count() . ' منتجات تحتاج إلى إعادة تخزين',
            ];
        }

        return response()->json([
            'status' => 200,
            'data'   => [
                'employee' => [
                    'id'    => $reception->id,
                    'name'  => $reception->full_name,
                    'role'  => $reception->role,
                    'image' => $reception->profile_image_url
                        ?? ($reception->profile_image
                            ? asset('storage/' . $reception->profile_image)
                            : null),
                ],
                'statistics' => [
                    'members_count'               => $membersCount,
                    'today_sales'                 => $todaySales,
                    'today_sales_count'           => $todaySalesCount,
                    'today_sessions'              => $todaySessionsCount,
                    'upcoming_sessions'           => $upcomingSessionsCount,
                    'expired_subscriptions'       => $expiredSubscriptionsCount,
                    'expiring_subscriptions'      => $expiringSubscriptionsCount,
                    'coaches_present'             => $coachesPresent,
                    'coaches_total'               => $coachesTotal,
                    'coach_attendance_percentage' => $coachesTotal > 0
                        ? round(($coachesPresent / $coachesTotal) * 100)
                        : 0,
                ],
                'today_sessions'          => $todaySessions,
                'coach_attendance'        => $coachAttendance,
                'recent_sales'            => $recentSales,
                'expired_subscriptions'   => $expiredSubscriptions,
                'expiring_subscriptions'  => $expiringSubscriptions,
                'low_stock_products'      => $lowStockProducts,
                'alerts'                  => $alerts,
                'recent_activity'         => $recentActivity,
            ],
        ]);
    }
    
    }