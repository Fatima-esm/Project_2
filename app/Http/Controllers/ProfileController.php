<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\RateLimiter; 
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use App\Models\Goal;
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
            'email'         => 'sometimes|email|unique:users,email,' . $user->id,
            'gender'        => 'sometimes|string|in:male,female,ذكر,أنثى,انثى,رجل,امرأة',
            'phone'         => 'sometimes|unique:users,phone,' . $user->id,
            'profile_image' => 'sometimes|image|mimes:jpeg,png,jpg|max:4096', // رفع صورة      ]);
        ]);
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
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
        $request->validate(['goal_id' => 'required|exists:goals,id']);

        $user = $request->user();
        $user->goal_id = $request->goal_id;
        $user->save();

        return response()->json([
            'status' => 201,
            'message' => 'تم حفظ هدفك الرياضي بنجاح',
            'user_id' => $user->id,
            'goal' => $request->user()->goal ,
            ]);
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
 
    //تعديل او اضافة قياسات
    public function addMeasurement(Request $request)
    {
        $request->validate([
            'height' => 'required|numeric|min:100|max:250',
            'weight' => 'required|numeric|min:30|max:300',
            'fat_percentage' => 'nullable|numeric|min:3|max:60',
            'muscle_mass' => 'nullable|numeric|min:15|max:80',
        ]);

        $measurement = $request->user()->measurements()->create([
            'height' => $request->height,
            'weight' => $request->weight,
            'fat_percentage' => $request->fat_percentage,
            'muscle_mass' => $request->muscle_mass,

        ]);

        return response()->json([
            'message' => 'تم حفظ القياسات بنجاح',
            'data' => $measurement
        ]);
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