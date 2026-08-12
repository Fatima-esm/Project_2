<?php

namespace App\Http\Controllers\ClubPage;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ClubDetail;

class ClubActivityController extends Controller
{
    public function getClubDetails()
    {
        $details = ClubDetail::first();

        if (!$details) {
            return response()->json([
                'status'  => 404,
                'message' => 'تفاصيل النادي غير موجودة',
            ], 404);
        }

        return response()->json([
            'status'  => 200,
            'message' => 'تم جلب تفاصيل النادي بنجاح',
            'data'    => [
                'name'         => $details->name,
                'description'  => $details->description,
                'image'        => $details->image,        // مسار نسبي
                'image_url'    => $details->image_url,    // رابط كامل للفرونت
                'phone'        => $details->phone,
                'email'        => $details->email,
                'location'     => $details->location,
                'opening_time' => $details->opening_time
                    ? date('h:i A', strtotime($details->opening_time))
                    : null,
                'closing_time' => $details->closing_time
                    ? date('h:i A', strtotime($details->closing_time))
                    : null,
                'status'       => $details->status,
            ]
        ], 200);
    }
    /**
     * تعديل وإدارة تفاصيل النادي من قبل الإدارة (شاملة الوصف والصورة).
     */
    public function updateClubDetails(Request $request)
    {
        $request->validate([
            'name'         => 'sometimes|string|max:255',
            'description'  => 'sometimes|nullable|string',
            'image'        => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            'phone'        => 'sometimes|string|max:50',
            'email'        => 'sometimes|email|max:255',
            'location'     => 'sometimes|string|max:255',
            'opening_time' => 'sometimes|date_format:H:i:s',
            'closing_time' => 'sometimes|date_format:H:i:s',
            'status'       => 'sometimes|in:open,closed,holiday',
        ]);

        $details = ClubDetail::firstOrCreate(['id' => 1]);

        if ($request->hasFile('image')) {
            // حذف القديمة إن كانت مساراً نسبياً
            if ($details->image && !str_starts_with($details->image, 'http')) {
                if (\Storage::disk('public')->exists($details->image)) {
                    \Storage::disk('public')->delete($details->image);
                }
            }

            $details->image = $request->file('image')->store('club', 'public');
        }

        $details->fill($request->except('image'));
        $details->save();

        return response()->json([
            'status'  => 200,
            'message' => 'تم تحديث تفاصيل النادي بنجاح',
            'data'    => [
                'name'         => $details->name,
                'description'  => $details->description,
                'image'        => $details->image,
                'image_url'    => $details->image_url,
                'phone'        => $details->phone,
                'email'        => $details->email,
                'location'     => $details->location,
                'opening_time' => $details->opening_time,
                'closing_time' => $details->closing_time,
                'status'       => $details->status,
            ]
        ], 200);
    }    
    
    
}
