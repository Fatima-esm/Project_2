<?php

namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Models\Admin;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
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



 }
