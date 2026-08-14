<?php

namespace App\Http\Controllers\ClubPage;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ClubDetail;
use App\Models\ClubService;
use App\Models\ClubEvent;
use Illuminate\Support\Facades\Storage;


class EventController extends Controller
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

    public function eventsIndex()
    {
        return response()->json([
            'status' => 200,
            'data'   => ClubEvent::orderByDesc('event_date')->get(),
        ]);
    }

    public function eventsStore(Request $request)
    {
        $this->ensureAdmin();

        $data = $request->validate([
            'title'       => 'required|string|max:255',
            'description' => 'nullable|string',
            'image'       => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            'event_date'  => 'required|date|after_or_equal:today',
            'start_time'  => 'required|date_format:H:i',
            'end_time'    => 'nullable|date_format:H:i|after:start_time',
            'status'      => 'nullable|in:available,unavailable,cancelled',
            'is_active'   => 'nullable|boolean',
        ], [
            'event_date.after_or_equal' => 'لا يمكن إضافة فعالية بتاريخ قبل اليوم',
            'end_time.after'            => 'وقت النهاية يجب أن يكون بعد وقت البداية',
        ]);

        // إذا التاريخ اليوم → وقت البداية يجب أن يكون بعد ساعة من الآن على الأقل
        $eventStart = \Carbon\Carbon::parse(
            $request->event_date . ' ' . $request->start_time
        );

        $minStart = now()->addHour();

        if ($eventStart->lt($minStart)) {
            return response()->json([
                'message' => 'وقت بداية الفعالية يجب أن يكون بعد ساعة على الأقل من الوقت الحالي',
                'errors'  => [
                    'start_time' => [
                        'الحد الأدنى لوقت البداية: ' . $minStart->format('Y-m-d H:i'),
                    ],
                ],
            ], 422);
        }

        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('events', 'public');
        }

        if (isset($data['is_active'])) {
            $data['is_active'] = filter_var($data['is_active'], FILTER_VALIDATE_BOOLEAN);
        }

        $event = ClubEvent::create($data);

        return response()->json([
            'status'  => 201,
            'message' => 'تم إضافة الفعالية بنجاح',
            'data'    => $event,
        ], 201);
    }

    public function eventsUpdate(Request $request, $id)
    {
        $this->ensureAdmin();
        $event = ClubEvent::findOrFail($id);

        $data = $request->validate([
            'title'       => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'image'       => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            'event_date'  => 'sometimes|date',
            'start_time'  => 'sometimes|date_format:H:i',
            'end_time'    => 'nullable|date_format:H:i',
            'status'      => 'sometimes|in:available,unavailable,cancelled',
            'is_active'   => 'nullable|boolean',
        ]);

        if ($request->hasFile('image')) {
            if ($event->image && !str_starts_with($event->image, 'http')) {
                Storage::disk('public')->delete($event->image);
            }
            $data['image'] = $request->file('image')->store('events', 'public');
        }

        $event->update($data);

        return response()->json([
            'status'  => 200,
            'message' => 'تم تحديث الفعالية بنجاح',
            'data'    => $event->fresh(),
        ]);
    }

    public function eventsDestroy($id)
    {
        $this->ensureAdmin();
        $event = ClubEvent::findOrFail($id);

        if ($event->image && !str_starts_with($event->image, 'http')) {
            Storage::disk('public')->delete($event->image);
        }

        $event->delete();

        return response()->json([
            'status'  => 200,
            'message' => 'تم حذف الفعالية بنجاح',
        ]);
    }

    // الفعاليات القادمة للمستخدمين (بدون اشتراك)
    public function listEvents()
    {
        $events = ClubEvent::where('is_active', true)
            ->whereNotIn('status', ['cancelled','unavailable'])
            ->whereDate('event_date', '>=', today())
            ->orderBy('event_date')
            ->orderBy('start_time')
            ->get()
            ->filter(function ($e) {
                // استبعاد المنتهية من القائمة
                return $e->display_status !== 'completed';
            })
            ->values()
            ->map(fn($e) => [
                'id'             => $e->id,
                'title'          => $e->title,
                'description'    => $e->description,
                'image_url'      => $e->image_url,
                'event_date'     => $e->event_date->format('Y-m-d'),
                'date_label'     => $e->event_date->translatedFormat('d M'),
                'start_time'     => substr((string) $e->start_time, 0, 5),
                'end_time'       => $e->end_time ? substr((string) $e->end_time, 0, 5) : null,
                'time_range'     => substr((string) $e->start_time, 0, 5) .
                                    ($e->end_time ? ' - ' . substr((string) $e->end_time, 0, 5) : ''),
                'status'         => $e->display_status, // available | ongoing | unavailable
                'status_label'   => $e->status_label,   // متاحة | جارية | غير متاحة
            ]);

        return response()->json([
            'status' => 200,
            'count'  => $events->count(),
            'data'   => $events,
        ]);
    }

    // تفاصيل فعالية واحدة
    public function showEvent($id)
    {
        $event = ClubEvent::where('is_active', true)->find($id);

        if (!$event) {
            return response()->json(['message' => 'الفعالية غير موجودة'], 404);
        }

        return response()->json([
            'status' => 200,
            'data'   => [
                'id'           => $event->id,
                'title'        => $event->title,
                'description'  => $event->description,
                'image_url'    => $event->image_url,
                'event_date'   => $event->event_date->format('Y-m-d'),
                'date_label'   => $event->event_date->translatedFormat('d M Y'),
                'start_time'   => substr((string) $event->start_time, 0, 5),
                'end_time'     => $event->end_time ? substr((string) $event->end_time, 0, 5) : null,
                'time_range'   => substr((string) $event->start_time, 0, 5) .
                                ($event->end_time ? ' - ' . substr((string) $event->end_time, 0, 5) : ''),
                'status'       => $event->display_status,
                'status_label' => $event->status_label,
            ],
        ]);
    }



}
