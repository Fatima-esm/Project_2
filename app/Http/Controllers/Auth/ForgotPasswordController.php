<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Mail;
use App\Mail\ResetPasswordOtpMail;
use Carbon\Carbon;

class ForgotPasswordController extends Controller
{
    /**
     * الخطوة الأولى: إرسال الرمز (مع تقييد الطلب مرتين كل 6 ساعات)
     */
    public function sendResetOtp(Request $request): \Illuminate\Http\JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => ['required', 'email:rfc,dns', 'exists:users,email'],
        ], [
            'email.required' => 'حقل البريد الإلكتروني مطلوب.',
            'email.email'    => 'صيغة البريد الإلكتروني غير صحيحة.',
            'email.exists'   => 'هذا البريد الإلكتروني غير مسجل لدينا.',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user = User::where('email', $request->email)->first();

        $now = Carbon::now();

        // التحقق من مرور 6 ساعات على آخر نافذة طلبات
        if ($user->otp_reset_at && $now->diffInHours($user->otp_reset_at) < 6) {
            // إذا لم تمر 6 ساعات، نتحقق من عدد المحاولات
            if ($user->otp_requests_count >= 3) {
                $remainingHours = 6 - $now->diffInHours($user->otp_reset_at);
                return response()->json([
                    'status'  => 429,
                    'message' => "لقد تجاوزت الحد المسموح بطلب الرمز (3 مرات). يرجى المحاولة بعد حوالي {$remainingHours} ساعة.",
                ], 429);
            }
            // زيادة عداد الطلبات ضمن نفس الـ 6 ساعات
            $user->increment('otp_requests_count');
        } else {
            // إذا مر أكثر من 6 ساعات، نبدأ نافذة جديدة ونجعل العداد 1
            $user->update([
                'otp_requests_count' => 1,
                'otp_reset_at'       => $now,
            ]);
        }

        // توليد رمز OTP مكون من 6 أرقام كما طلبت
        $otpCode = rand(100000, 999999);

        // حفظ الرمز في قاعدة البيانات
        $user->update([
            'otp' => $otpCode,
        ]);

        // إرسال الإيميل مع الشعار والقالب الاحترافي
        Mail::to($user->email)->send(new ResetPasswordOtpMail($otpCode));

        return response()->json([
            'status'  => 200,
            'message' => 'تم إرسال رمز التحقق (OTP) المكون من 6 أرقام إلى بريدك الإلكتروني بنجاح.',
        ], 200);
    }

    /**
     * الخطوة الثانية: التحقق من الرمز فقط (Verify OTP)
     */
    public function verifyOtp(Request $request): \Illuminate\Http\JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => ['required', 'email', 'exists:users,email'],
            'otp'   => ['required', 'string', 'size:6'],
        ], [
            'email.required' => 'البريد الإلكتروني مطلوب.',
            'email.exists'   => 'البريد الإلكتروني غير مسجل.',
            'otp.required'   => 'رمز التحقق مطلوب.',
            'otp.size'       => 'رمز التحقق يجب أن يتكون من 6 أرقام.',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user = User::where('email', $request->email)
                    ->where('otp', $request->otp)
                    ->first();

        if (!$user) {
            return response()->json([
                'status'  => 400,
                'message' => 'رمز التحقق (OTP) غير صحيح أو انتهت صلاحيته.',
            ], 400);
        }

        return response()->json([
            'status'  => 200,
            'message' => 'تم التحقق من الرمز بنجاح. يمكنك الآن الانتقال لإنشاء كلمة مرور جديدة.',
        ], 200);
    }

    /**
     * الخطوة الثالثة: تعيين كلمة المرور الجديدة وتأكيدها (باستخدام الإيميل فقط بعد نجاح التحقق)
     */
    public function resetPassword(Request $request): \Illuminate\Http\JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email'              => ['required', 'email', 'exists:users,email'],
            'password'           => ['required', 'string', 'min:8', 'confirmed'], // يتطلب password_confirmation
        ], [
            'email.required'     => 'البريد الإلكتروني مطلوب.',
            'password.required'  => 'كلمة المرور الجديدة مطلوبة.',
            'password.min'       => 'كلمة المرور يجب ألا تقل عن 8 أحرف.',
            'password.confirmed' => 'كلمة المرور غير متطابقة مع تأكيد كلمة المرور.',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // البحث عن المستخدم عبر البريد الإلكتروني فقط
        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return response()->json([
                'status'  => 404,
                'message' => 'المستخدم غير موجود.',
            ], 404);
        }

        // تحديث كلمة المرور وتصفير الـ OTP وعدادات الطلب لتبدأ دورة نظيفة جديدة
        $user->update([
            'password'           => Hash::make($request->password),
            'otp'                => null,
            'otp_requests_count' => 0,
            'otp_reset_at'       => null,
        ]);

        return response()->json([
            'status'  => 200,
            'message' => 'تم إنشاء كلمة المرور بنجاح. يمكنك تسجيل الدخول الآن.',
        ], 200);
    }
    
    
    }