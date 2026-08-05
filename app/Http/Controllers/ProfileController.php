<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\RateLimiter; 
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use App\Models\Goal;
use App\Models\User;
use App\Models\Measurement;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use BaconQrCode\Renderer\Image\GdImageBackEnd; // أضف هذا الـ Use
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;

class ProfileController extends Controller
{
    //show profile.............................................
    public function show(Request $request)
    {
        return response()->json([
            'status' => 200,
            'data' => $request->user() 
        ]);
    }
    
    //update profile.............................................
    public function update(Request $request)
    {
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'full_name'     => 'sometimes|string|max:50',
            'age'           => 'sometimes|integer|min:12|max:90',
            'email'         => 'sometimes|email:rfc,dns|unique:users,email,' . $user->id,
            'gender'        => 'sometimes|string|in:male,female,ذكر,أنثى,انثى,رجل,امرأة',
            'profile_image' => 'sometimes|image|mimes:jpeg,png,jpg|max:4096', // رفع صورة      ]);
        ], [
            'email.email' => 'البريد الإلكتروني غير صالح',
            'email.unique' => 'البريد الإلكتروني مستخدم من قبل',
            'profile_image.image' => 'يجب أن تكون الصورة بصيغة صحيحة',
            'profile_image.mimes' => 'يجب أن تكون الصورة بصيغة jpeg أو png أو jpg',
            'profile_image.max' => 'حجم الصورة يجب ألا يتجاوز 4 ميغابايت',
        ],[
            'phone' => [
                'required',
                'string',
                'unique:users,phone,' . $user->id,
                'regex:/^9639[0-9]{8}$/' // تمرير الـ regex داخل مصفوفة يمنع حدوث خطأ الـ modifiers تماماً
            ],], [
                'phone.regex'  => 'رقم الهاتف يجب أن يكون بصيغة الرقم السوري الصحيحة ويبدأ بـ 9639 ويتكون من 12 رقماً.',
                'phone.unique' => 'رقم الهاتف مستخدم من قبل شخص آخر.',
        ]);
        
        if ($validator->fails()) {
            $allErrors = collect($validator->errors()->all())->implode(' - ');
            return response()->json(['message' => $allErrors], 422);
        }

        if ($request->hasFile('profile_image')) {
            if ($user->profile_image) {
                \Storage::disk('public')->delete($user->profile_image);
            }
            $path = $request->file('profile_image')->store('profiles', 'public');
            $user->profile_image = $path;
        }

        $user->update($request->only([
            'full_name', 
            'age',
            'email',
            'gender',
            'phone'
        ]));

        return response()->json([
            'status' => 200,
            'message' => 'تم تحديث البروفايل بنجاح',
            'data' => $user
        ]);
    }

    //goals ......................................................
    public function allGoals()
    {
        $goals = Goal::all();
        return response()->json(['status' => 200, 'data' => $goals]);
    }

    public function selectGoal(Request $request)
    {
        {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id',
            'goal_id' => 'required|exists:goals,id',
        ],[
            'goal_id.required' => 'الهدف مطلوب',
            'goal_id.exists' => 'الهدف غير موجود',
        ]);

        if ($validator->fails()) {
            $allErrors = collect($validator->errors()->all())->implode(' - ');
            return response()->json(['message' => $allErrors], 422);
        }

        $user = User::find($request->user_id);

        // حماية بسيطة: لا تسمح بتعديل حسابات الأدمن أو الكوتش بهذه الطريقة
        if (in_array($user->role, ['admin', 'reception'])) {
            return response()->json([
                'message' => 'غير مسموح'
            ], 403);
        }

        $user->goal_id = $request->goal_id;
        $user->save();

        $user->load('goal');

        return response()->json([
            'status'  => 200,
            'message' => 'تم حفظ الهدف بنجاح',
            'data' => [
                'user_id' => $user->id,
                'goal'    => $user->goal,
                'has_goal' => true,
                'has_measurements' => $user->measurements()->exists()
            ]
        ]);
        }
     }

    //..............................................................تم
    //عرض القياسات
    public function getMeasurements(Request $request)
    {
        $measurements = $request->user()->measurements()->first();

        return response()->json([
            'status' => 200,
            'message' => 'تم استرجاع القياسات بنجاح',
            'data' => $measurements
        ], 200);
    }
 
//تعديل او اضافة قياسات وتحديث العمر والجنس للمستخدم
    public function addMeasurement(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id'          => 'required|exists:users,id',
            'height'           => 'required|numeric|min:100|max:250',
            'weight'           => 'required|numeric|min:30|max:300',
            'fat_percentage'   => 'nullable|numeric|min:3|max:60',
            'muscle_mass'      => 'nullable|numeric|min:10|max:120',
            'age'              => 'required|integer|min:12|max:90',
            'gender'           => 'required|string|in:male,female,ذكر,أنثى,انثى,رجل,امرأة',
        ], [
            'age.required'     => 'العمر مطلوب',
            'age.integer'      => 'العمر يجب أن يكون رقماً صحيحاً',
            'gender.required'  => 'الجنس مطلوب',
            'gender.in'        => 'قيمة الجنس المدخلة غير صحيحة',
        ]);

        if ($validator->fails()) {
            // دمج جميع رسائل الأخطاء في نص واحد يفصل بينها مسافة أو فاصلة
            $allErrors = collect($validator->errors()->all())->implode(' - ');
            return response()->json(['message' => $allErrors], 422);
        }

        $user = User::find($request->user_id);

        if (in_array($user->role, ['admin', 'reception'])) {
            return response()->json(['message' => 'غير مسموح'], 403);
        }

        // 1. تحديث العمر والجنس في جدول الـ users
        $user->update([
            'age'    => $request->age,
            'gender' => $request->gender,
        ]);

        // 2. إنشاء سجل القياسات الجديد في جدول الـ measurements
        $measurement = Measurement::create([
            'user_id'           => $user->id,
            'height'            => $request->height,
            'weight'            => $request->weight,
            'fat_percentage'    => $request->fat_percentage,
            'muscle_mass'       => $request->muscle_mass,
            'measured_at'       => now(),
        ]);

        return response()->json([
            'status'  => 201,
            'message' => 'تم حفظ القياسات وتحديث بيانات المستخدم بنجاح',
            'data' => [
                'user_id' => $user->id,
                'user_info' => [
                    'age' => $user->age,
                    'gender' => $user->gender,
                ],
                'measurement' => $measurement,
                'has_goal' => !is_null($user->goal_id),
                'has_measurements' => true,
                'profile_completed' => !is_null($user->goal_id)
            ]
        ], 201);
    }


    //تاريخ القياسات بدءا من الاحدث لاجل الرسم البياني
    public function getHistory(Request $request)
    {
        $history = $request->user()->measurements()->orderBy('created_at', 'desc')->get();
        
        return response()->json([
            'history' => $history
        ]);
    }

    //generate QR code.............................................
    public function generateQR(Request $request)
{
    $user = $request->user();
    $qrData = "Member: " . $user->full_name . " | ID: " . $user->id;

    // توليد QR بصيغة SVG (نصي) لا يحتاج مكتبات صور
    $svg = QrCode::format('svg')->size(300)->generate($qrData);
    
    // تحويل الـ SVG إلى Base64
    $qrBase64 = base64_encode($svg);
                        
    return response()->json([
        'status' => 200,
        // تغيير الصيغة إلى svg+xml
        'qr_code' => 'data:image/svg+xml;base64,' . $qrBase64,
        'member_info' => [
            'name' => $user->full_name,
            'membership_id' => $user->id
        ]
    ]);
}
}