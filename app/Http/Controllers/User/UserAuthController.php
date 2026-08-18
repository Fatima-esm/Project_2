<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\CoachProfile;
use App\Models\Subscription;
use App\Models\Plan;

use Spatie\Permission\Traits\HasRoles;  //for role and permission
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Hash;
use App\Mail\VerifyAccountMail; 
use Carbon\Carbon;
use App\Services\ActivityService;
use App\Models\ActivityLog;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter; 
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;

class UserAuthController extends Controller
{

    public function register(Request $request): \Illuminate\Http\JsonResponse
    {
        $isAdminOrReception = auth()->check() && in_array(auth()->user()->role, ['admin', 'reception']);

        $validator = Validator::make($request->all(), [
            'full_name' => ['required', 'string', 'max:50'],
            'email'     => ['required', 'email:rfc,dns', 'max:50', 'unique:users'],
            'phone'     => ['required', 'string', 'regex:/^963[0-9]{9}$/', 'unique:users'],
            'password'  => ['required', 'string', 'min:8', 'confirmed'],
            'role'      => ['required', 'in:coach,trainee' . ($isAdminOrReception ? ',admin,reception' : '')],
        ],[
            'phone.regex' => 'رقم الهاتف يجب أن يبدأ بـ 963 ويحتوي على 9 أرقام بعده.',
            'email.email' =>  ' البريد الإلكتروني غير صالح او غير حقيقي.',
            'email.unique' =>  ' البريد الإلكتروني مستخدم من قبل.',
            'phone.unique' =>  ' رقم الهاتف مستخدم من قبل.',
        ]);

        if ($validator->fails()) {
            $allErrors = collect($validator->errors()->all())->implode(' - ');
            return response()->json(['message' => $allErrors], 422);
        }

        // تحديد الحالة والنشاط بناءً على الدور
        $isCoach = $request->role === 'coach';
        $status = $isCoach ? 'pending' : 'active';
        $activeAt = $isCoach ? 0 : 1;

        // إنشاء المستخدم
        $user = User::create([
            'full_name'         => $request->full_name,
            'email'             => $request->email,
            'phone'             => $request->phone,
            'password'          => Hash::make($request->password),
            'role'              => $request->role,
            'status'            => $status,
            'active_at'         => $activeAt,
            'membership_number' => 'SG-' . mt_rand(10000, 99999),
            'added_by'  => auth()->id(),

        ]);

        if ($user->role === 'trainee') {
            $freePlan = Plan::where('name', 'Free Trial')->first();
            Subscription::create([
                'user_id'    => $user->id,
                'plan_id'    => $freePlan->id, 
                'price'      => 0,
                'status'     => 'paid', // 
                'starts_at'  => now(),
                'expires_at' => now()->addDays($freePlan->duration_days),
            ]);
        }

        $user->assignRole($request->role);

       $addedByName = auth()->check() ? auth()->user()->full_name : 'المستخدم نفسه';

        ActivityLog::log(
            auth()->id() ?? $user->id,
            'register',
            'تسجيل مستخدم جديد',
            [
                'subject_type' => User::class,
                'subject_id'   => $user->id,
                'details'      => 'اسم المتدرب:'.$user->full_name . ' | عضوية: ' . $user->membership_number,
                'icon'         => 'add trainee',
                'properties'   => [
                    'message' => 'تمت إضافة متدرب جديد: ' . $user->full_name .
                                ' | رقم العضوية: ' . $user->membership_number .
                                ' | بواسطة: ' . $addedByName
                ]
            ]
        );
        
        return response()->json([
            'status'    => 201 ,
            'message'   => "تم إنشاء الحساب بواسطة " . (auth()->check() ? 'الإدارة' : 'المستخدم.') . ($isCoach ? "قم برفع السيرة الذاتية وانتظر موافقة الإدارة" : ""),
            'user_id'   => $user->id,
            'data' => $user,
            'membership_number' => $user->membership_number,
        ], 201);
    }


    public function uploadCv(Request $request)
    {
            $request->validate([
            'user_id' => 'required|exists:users,id',
            'cv' => 'required|mimes:pdf|max:10000',
            ]);
              

        $user = User::find($request->user_id);

        if ($user->role !== 'coach') {
            return response()->json([
                'success' => false,
                'message' => 'هذه الخدمة متاحة للمدربين فقط',
            ], 403);
        }

        $path = $request->file('cv')->store('cvs', 'public'); // storage/app/public/cvs

        CoachProfile::create([
            'user_id' => $user->id,
            'cv_path' => $path
        ]);

        return response()->json(['message' => 'تم رفع السيرة الذاتية، انتظر موافقة الإدارة']);

    }


    //logging for user
    public function login(Request $request): \Illuminate\Http\JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => ['required', 'email:rfc,dns'],
            'password' => ['required', 'string'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors()
            ], 422); 
        }

        if (!Auth::attempt($request->only('email', 'password'))) {
            return response()->json([
                'message' => "invalid email or password",
            ], 401);
        }
        $user = Auth::user(); // جلب المستخدم الذي تم التحقق منه

        if ($user->active_at == 0) {
        return response()->json([
            'message' => 'حسابك لا يزال قيد المراجعة، يرجى الانتظار'
            ], 403);
        }

        $token = $user->createToken("myAppToken")->plainTextToken;

        return response()->json([
            'status' => 200,
            'message' => "the user logging successfully",
            'token' => $token,
            'data' => [
                'user' => $user,
                'roles' => $user->getRoleNames(), 
                'permissions' => $user->getAllPermissions()->pluck('name'), 
            ],
        ], 200);
    }

    // user Logout
    public function logout(Request $request): \Illuminate\Http\JsonResponse
    {
        request()->user()->currentAccessToken()->delete();
        Auth::guard('web')->logout();
        return response()->json(['message' => 'Logged out successfully'], 200);
    }

    //عرض المتدربين عند الادارة مع فترة وبحث
    public function index(Request $request)
    {
        $query = User::with(['coach', 'activeSubscription.Plan'])
            ->whereNotIn('role', ['admin', 'reception', 'coach']);


        if ($request->has('status') && !empty($request->status)) {
            $query->where('status', $request->query('status'));
        }

        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('full_name', 'like', '%' . $search . '%')
                ->orWhere('phone', 'like', '%' . $search . '%')
                ->orWhere('membership_number',  $search );
            });
        }

        $users = $query->latest()->get();
        $formattedUsers = $users->map(function ($user) {
            $activeSub = $user->activeSubscription; 
            
            $endDate = null;
            if ($activeSub) {
                $endDate = $activeSub->end_date ?? $activeSub->expires_at ?? null;
            }
            
            // حساب الأيام المتبقية
            $remainingDays = 0;
            if ($endDate) {
                $remainingDays = Carbon::now()->diffInDays(Carbon::parse($endDate), false);
                $remainingDays = $remainingDays > 0 ? (int)$remainingDays : 0;
            }

            return [
                'id' => $user->id,
                'membership_number' => $user->membership_number, 
                'full_name' => $user->full_name, 
                'phone' => $user->phone, 
                'email' => $user->email, 
                'subscriPtion' => ($activeSub && $activeSub->plan) ? $activeSub->plan->name : 'بدون باقة',
                'coach_name' => $user->coach_id ? $user->coach->full_name:'بدون مدرب', 
                'end_date' => $endDate, 
                'remaining_days' => $remainingDays, 
                'account_status' => $user->status ?? 'نشط', 
                ''
            ];
        });

        return response()->json([
            'status' => 200,
            'message' => 'تم جلب البيانات بنجاح',
            'meta' => [
                'total' => $formattedUsers->count(),
                'applied_filters' => $request->only(['status', 'search'])
            ],
            'data' => $formattedUsers
        ]);
    }

    public function updateTrainee(Request $request, $id)
    {  
        if (!in_array(auth()->user()->role, ['admin', 'reception'])) {
            return response()->json(['message' => 'غير مصرح لك بالوصول'], 403);
        }

        $user = User::find($id);
        if (!$user) {
            return response()->json(['message' => 'المستخدم غير موجود'], 404);
        }
        $syrianPhoneRegex = '/^963[0-9]{9}$/';

        $validator = Validator::make($request->all(), [
            'full_name'         => 'sometimes|string|max:255',
            'email'             => 'sometimes|email:dns,rfc|unique:users,email,' . $id,
            'phone'             => ['sometimes', 'string', 'regex:' . $syrianPhoneRegex, 'unique:users,phone,' . $id],
            'membership_number' => 'sometimes|string|unique:users,membership_number,' . $id,
            'status'            => 'sometimes|in:pending,active,rejected,expired,banned',
            'coach_id'          => 'nullable|exists:users,id',
        ],[
            'email.email'       => 'يرجى إدخال بريد إلكتروني صالح وموجود.',
            'phone.regex'       => 'رقم الهاتف يجب أن يكون رقم سوري صحيح (مثال: +963912345678).',
            'email.unique'      => 'البريد الإلكتروني مستخدم بالفعل.',
            'phone.unique'      => 'رقم الهاتف مستخدم بالفعل.',
        ]);

        if ($validator->fails()) {
            $allErrors = collect($validator->errors()->all())->implode(' - ');
            return response()->json(['message' => $allErrors], 422);
        }

        if ($request->filled('coach_id')) {
            $coach = User::find($request->coach_id);
            if (!$coach || !$coach->role('coach')) {
                return response()->json([
                    'message' => 'المستخدم المحدد ليس كوتش',
                    'error'   => 'invalid_coach'
                ], 422);
            }
        }

        $updateData = $request->only([
            'full_name',
            'email',
            'phone',
            'membership_number',
            'status',
            'coach_id',
        ]);

        if (isset($updateData['status'])) {
            $updateData['active_at'] = ($updateData['status'] === 'active') ? 1 : 0;
        }

        $user->update($updateData);

        $user->load(['coach', 'goal']);

        $addedByName = auth()->user()->full_name;

        ActivityLog::log(
            auth()->id(),
            'update_trainee',
            'تعديل بيانات متدرب',
            [
                'subject_type' => User::class,
                'subject_id'   => $user->id,
                'details'      => 'اسم المتدرب: ' . $user->full_name . ' | عضوية: ' . $user->membership_number,
                'icon'         => 'edit_trainee',
                'properties'   => [
                    'message' => 'تم تعديل بيانات المتدرب: ' . $user->full_name .
                                ' | رقم العضوية: ' . $user->membership_number .
                                ' | بواسطة: ' . $addedByName
                ]
            ]
        );
        return response()->json([
            'status' => 200,
            'message' => 'تم تعديل بيانات المستخدم بنجاح',
            'data' => $user
        ]);
    }


}
