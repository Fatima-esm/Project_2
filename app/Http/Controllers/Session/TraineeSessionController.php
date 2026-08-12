<?php

namespace App\Http\Controllers\Session;

use App\Http\Controllers\Controller;
use App\Models\Session;
use App\Models\SessionBooking;
use Illuminate\Http\Request;
use Carbon\Carbon;

class TraineeSessionController extends Controller
{
    // الجلسات المتاحة
    public function available(Request $request)
    {
        $trainee = auth()->user();
        if ($trainee->role !== 'trainee') {
            return response()->json(['message' => 'غير مصرح'], 403);
        }

        $query = Session::with(['coach', 'hall'])
            ->where('status', 'scheduled')
            ->whereDate('session_date', now()->toDateString())
            ->where('coach_id', $trainee->coach_id); // تطبيق شرط الكوتش المشرف على جميع الجلسات (جماعية وفردية)

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        $sessions = $query->orderBy('session_date')->orderBy('start_time')->get()
            ->filter(fn($s) => $s->has_available_slots)
            ->values()
            ->map(fn($s) => [
                'id'           => $s->id,
                'type'         => $s->type,
                'type_label'   => $s->type === 'group' ? 'جماعية' : 'فردية',
                'title'        => $s->title,
                'description'  => $s->description,
                'session_date' => $s->session_date->format('Y-m-d'),
                'start_time'   => substr((string) $s->start_time, 0, 5),
                'end_time'     => substr((string) $s->end_time, 0, 5),
                'capacity'     => $s->capacity,
                'booked_count' => $s->booked_count,
                'hall_name'    => $s->hall->name,
                'coach_name'   => $s->coach->full_name,
            ]);

        return response()->json(['status' => 200, 'data' => $sessions]);
    }
    // حجز جلسة
    public function book($sessionId)
    {
        $trainee = auth()->user();

        if ($trainee->role !== 'trainee') {
            return response()->json(['message' => 'غير مصرح'], 403);
        }

        // التحقق من منع الحجز
        if ($trainee->booking_banned_until && now()->lt($trainee->booking_banned_until)) {
            return response()->json([
                'message'      => 'أنت ممنوع من الحجز حتى ' . $trainee->booking_banned_until->format('Y-m-d H:i'),
                'banned_until' => $trainee->booking_banned_until->format('Y-m-d H:i'),
            ], 403);
        }

        // انتهى المنع → امسحه
        if ($trainee->booking_banned_until && now()->gte($trainee->booking_banned_until)) {
            $trainee->update(['booking_banned_until' => null]);
        }

        $session = Session::with('hall')->find($sessionId);

        if (!$session || $session->status !== 'scheduled') {
            return response()->json(['message' => 'الجلسة غير متاحة'], 404);
        }

        if (!$session->has_available_slots) {
            return response()->json(['message' => 'لا توجد مقاعد متاحة'], 400);
        }

        // البحث عن أي حجز سابق (بما فيه الملغى)
        $existing = SessionBooking::where('session_id', $sessionId)
            ->where('user_id', $trainee->id)
            ->first();

        if ($existing) {
            // محجوز أو حضر مسبقاً
            if (in_array($existing->status, ['booked', 'attended'])) {
                return response()->json(['message' => 'أنت محجوز مسبقاً في هذه الجلسة'], 400);
            }

            // إعادة تفعيل حجز ملغى
            if ($existing->status === 'cancelled') {
                if ($session->type === 'individual') {
                    $limitError = $this->checkIndividualWeeklyLimit($trainee, $session);
                    if ($limitError) {
                        return $limitError;
                    }
                }

                $existing->update([
                    'status'       => 'booked',
                    'booked_at'    => now(),
                    'cancelled_at' => null,
                    'attended_at'  => null,
                ]);

                return response()->json([
                    'status'  => 200,
                    'message' => 'تم إعادة حجز الجلسة بنجاح',
                    'data'    => $existing->fresh()->load('session.hall'),
                ]);
            }
        }

        // قواعد الجلسة الفردية
        if ($session->type === 'individual') {
            if (!$trainee->coach_id || $session->coach_id !== $trainee->coach_id) {
                return response()->json(['message' => 'يمكنك حجز الجلسات الفردية لكوتشك فقط'], 403);
            }

            $limitError = $this->checkIndividualWeeklyLimit($trainee, $session);
            if ($limitError) {
                return $limitError;
            }
        }

        $booking = SessionBooking::create([
            'session_id' => $session->id,
            'user_id'    => $trainee->id,
            'status'     => 'booked',
            'booked_at'  => now(),
        ]);

        return response()->json([
            'status'  => 201,
            'message' => 'تم حجز الجلسة بنجاح',
            'data'    => $booking->load('session.hall'),
        ], 201);
    }

    private function checkIndividualWeeklyLimit($trainee, $session)
    {
        $weekStart = Carbon::parse($session->session_date)->startOfWeek();
        $weekEnd   = Carbon::parse($session->session_date)->endOfWeek();

        $count = SessionBooking::where('user_id', $trainee->id)
            ->whereIn('status', ['booked', 'attended'])
            ->whereHas('session', function ($q) use ($weekStart, $weekEnd) {
                $q->where('type', 'individual')
                ->whereBetween('session_date', [$weekStart, $weekEnd])
                ->where('status', '!=', 'cancelled');
            })
            ->count();

        if ($count >= 2) {
            return response()->json([
                'message' => 'لا يمكنك حجز أكثر من جلستين فرديتين في نفس الأسبوع'
            ], 400);
        }

        return null;
    }

    public function cancel($bookingId)
    {
        $trainee = auth()->user();

        $booking = SessionBooking::where('id', $bookingId)
            ->where('user_id', $trainee->id)
            ->first();

        if (!$booking) {
            return response()->json(['message' => 'الحجز غير موجود'], 404);
        }

        if ($booking->status !== 'booked') {
            return response()->json(['message' => 'لا يمكن إلغاء هذا الحجز'], 400);
        }

        $session = $booking->session;

        $start = Carbon::parse(
            $session->session_date->format('Y-m-d') . ' ' . $session->start_time
        );

        // منع الإلغاء بعد بدء الجلسة
        if (now()->gte($start)) {
            return response()->json([
                'message' => 'لا يمكن إلغاء الحجز بعد بدء الجلسة'
            ], 400);
        }

        // منع الإلغاء قبل ساعتين من بداية الجلسة
        if (now()->diffInMinutes($start, false) <= 120) {
            return response()->json([
                'message' => 'لا يمكن إلغاء الحجز قبل أقل من ساعتين من موعد الجلسة'
            ], 400);
        }

        // تنفيذ الإلغاء
        $booking->update([
            'status'       => 'cancelled',
            'cancelled_at' => now(),
        ]);

        // زيادة عداد الإلغاءات (الطريقة الأولى)
        $trainee->increment('session_cancel_count');
        $cancelCount = $trainee->fresh()->session_cancel_count;

        $response = [
            'status'       => 200,
            'cancel_count' => $cancelCount,
        ];

        if ($cancelCount >= 3) {
            $banUntil = now()->addDays(3);
            $trainee->update([
                'booking_banned_until' => $banUntil,
                'session_cancel_count' => 0,
            ]);

            $response['message'] = 'تم إلغاء حجز الجلسة. تنبيه: لقد تم منعك من الحجز لمدة 3 أيام بسبب تكرار الإلغاء';
            $response['banned_until'] = $banUntil->format('Y-m-d H:i');
            $response['warning'] = true;
            } elseif ($cancelCount === 2) {
                $response['warning'] = true;
                $response['message'] = 'تم إلغاء الحجز. تحذير: إلغاء آخر سيمنعك من الحجز لمدة 3 أيام';
                $response['remaining_cancels'] = 1;
            } else {
                $response['warning'] = false;
                $response['remaining_cancels'] = 3 - $cancelCount;
                $response['message'] = 'تم إلغاء الحجز بنجاح. احذر من الغاء الحجز في المرات القادمة حتى لا يتم حظرك من حجز الجلسات' . (3 - $cancelCount) . ' إلغاء قبل المنع';
            }


        return response()->json($response);
    }

    public function myBookings(Request $request)
    {
        $trainee = auth()->user();

        $query = SessionBooking::with(['session.coach', 'session.hall'])
            ->where('user_id', $trainee->id)
            ->whereIn('status', ['booked', 'attended']);

        if ($request->get('filter') === 'upcoming') {
            $query->whereHas('session', fn($q) => $q->whereDate('session_date', '>=', now()));
        } elseif ($request->get('filter') === 'past') {
            $query->whereHas('session', fn($q) => $q->whereDate('session_date', '<', now()));
        }

        $bookings = $query->latest()->get()->map(fn($b) => [
            'booking_id' => $b->id,
            'status'     => $b->status,
            'session'    => [
                'id'           => $b->session->id,
                'title'        => $b->session->title,
                'type'         => $b->session->type,
                'type_label'   => $b->session->type === 'group' ? 'جماعية' : 'فردية',
                'session_date' => $b->session->session_date->format('Y-m-d'),
                'start_time'   => substr((string) $b->session->start_time, 0, 5),
                'end_time'     => substr((string) $b->session->end_time, 0, 5),
                'hall_name'    => $b->session->hall->name,
                'coach_name'   => $b->session->coach->full_name,
            ],
        ]);

        return response()->json(['status' => 200, 'data' => $bookings]);
    }
}