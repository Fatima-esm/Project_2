<?php

namespace App\Http\Controllers\ClubPage;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ClubDetail;
use App\Models\ClubService;
use App\Models\ClubEvent;
use Illuminate\Support\Facades\Storage;

class ClubActivityController extends Controller
{
    private function ensureAdmin()
    {
        if (auth()->user()->role !== 'admin') {
            abort(response()->json(['message' => 'غير مصرح'], 403));
        }
    }

    private function ensurerecep()
    {
        if (!in_array($reception->role, ['admin', 'reception'])) {
            return response()->json(['message' => 'غير مصرح'], 403);
        }
    }
    
    // club profile for all users
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
                'image'        => $details->image,
                'image_url'    => $details->image_url,
                'phone'        => $details->phone,
                'email'        => $details->email,
                'location'     => $details->location,
                'opening_time' => $details->opening_time
                    ? date('h:i A', strtotime($details->opening_time))
                    : null,
                'closing_time' => $details->closing_time
                    ? date('h:i A', strtotime($details->closing_time))
                    : null,
                'status'       => $details->resolveCurrentStatus(),
            ]
        ], 200);

    }

    // services for all users
    public function listServices()
    {
        $services = ClubService::where('is_active', true)
            ->where('status', 'available')
            ->orderBy('sort_order')
            ->get()
            ->map(fn($s) => [
                'id'           => $s->id,
                'name'         => $s->name,
                'icon'         => $s->icon,
                'status'       => $s->status,
                'status_label' => $s->status === 'available' ? 'متوفرة' : 'غير متوفرة',
            ]);

        return response()->json([
            'status' => 200,
            'count'  => $services->count(),
            'data'   => $services,
        ]);
    }

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
    
    // for all users
    public function servicesIndex()
    {
        return response()->json([
            'status' => 200,
            'data'   => ClubService::orderBy('sort_order')->get(),
        ]);
    }

    public function servicesStore(Request $request)
    {
        $this->ensureAdmin();

        $data = $request->validate([
            'name'       => 'required|string|max:100',
            'icon'       => 'nullable|string|max:50',
            'status'     => 'nullable|in:available,unavailable',
            'sort_order' => 'nullable|integer|min:0',
            'is_active'  => 'nullable|boolean',
        ]);

        $service = ClubService::create($data);

        return response()->json([
            'status'  => 201,
            'message' => 'تم إضافة الخدمة بنجاح',
            'data'    => $service,
        ], 201);
    }

    public function servicesUpdate(Request $request, $id)
    {
        $this->ensureAdmin();
        $service = ClubService::findOrFail($id);

        $data = $request->validate([
            'name'       => 'sometimes|string|max:100',
            'icon'       => 'nullable|string|max:50',
            'status'     => 'sometimes|in:available,unavailable',
            'sort_order' => 'nullable|integer|min:0',
            'is_active'  => 'nullable|boolean',
        ]);

        $service->update($data);

        return response()->json([
            'status'  => 200,
            'message' => 'تم تحديث الخدمة بنجاح',
            'data'    => $service,
        ]);
    }

    public function servicesDestroy($id)
    {
        $this->ensureAdmin();
        ClubService::findOrFail($id)->delete();

        return response()->json([
            'status'  => 200,
            'message' => 'تم حذف الخدمة بنجاح',
        ]);
    }



    
    
}
