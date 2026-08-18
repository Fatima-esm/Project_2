<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Session extends Model
{
    
    use HasFactory;

    protected $fillable = [
        'coach_id', 'hall_id', 'type', 'title', 'description',
        'session_date', 'start_time', 'end_time', 'capacity', 'status', 'coach_confirmed_at'
    ];

    protected $casts = [
        'session_date' => 'date',
    ];

    protected $appends = ['status_label', 'booked_count', 'has_available_slots', 'is_attendance_done',];

    public function getIsAttendanceDoneAttribute(): bool
    {
        return !is_null($this->coach_confirmed_at);
    }

    public function coach()
    {
        return $this->belongsTo(User::class, 'coach_id');
    }

    public function hall()
    {
        return $this->belongsTo(GymHall::class, 'hall_id');
    }

    public function bookings()
    {
        return $this->hasMany(SessionBooking::class);
    }
    //.........................................................................................
    //عدد المحجوزين (بدون الملغيين)
    public function getBookedCountAttribute(): int
    {
        return $this->bookings()->whereIn('status', ['booked', 'attended'])->count();
    }
    //--------------------------------------------------------
    public function getHasAvailableSlotsAttribute(): bool
    {
        if ($this->status !== 'scheduled') {
            return false;
        }
        $activeBookingsCount = $this->bookings()->whereIn('status', ['booked', 'attended'])->count();
        return $activeBookingsCount < $this->capacity;
    }

    // تعارض في نفس الصالة ونفس الوقت
    public static function hasHallConflict(int $hallId, string $date, string $start, string $end, ?int $excludeId = null): bool
    {
        return self::where('hall_id', $hallId)
            ->whereDate('session_date', $date)
            ->where('status', '!=', 'cancelled')
            ->when($excludeId, fn($q) => $q->where('id', '!=', $excludeId))
            ->where(function ($q) use ($start, $end) {
                $q->where('start_time', '<', $end)
                  ->where('end_time', '>', $start);
            })
            ->exists();
    }

    // منع حجز جلستين متزامننتين للكوتش نفسه
    public static function hasCoachConflict(
        int $coachId,
        string $date,
        string $startTime,
        string $endTime,
        ?int $excludeSessionId = null
    ): bool {
        $query = self::where('coach_id', $coachId)
            ->whereDate('session_date', $date)
            ->where('status', '!=', 'cancelled')
            ->where(function ($q) use ($startTime, $endTime) {
                // تداخل الأوقات: البداية < نهاية الموجودة  و  النهاية > بداية الموجودة
                $q->where('start_time', '<', $endTime)
                ->where('end_time', '>', $startTime);
            });

        if ($excludeSessionId) {
            $query->where('id', '!=', $excludeSessionId);
        }

        return $query->exists();
    }

    // session status getStatusAttribute
    public static function updateExpiredSessions(): void
    {
        $now = now();
        $today = $now->toDateString();

        $sessions = self::whereIn('status', ['scheduled', 'ongoing'])
            ->whereDate('session_date', '<=', $today)
            ->get();

        foreach ($sessions as $session) {
            $sessionDate = $session->session_date instanceof Carbon 
                ? $session->session_date->format('Y-m-d') 
                : $session->session_date;

            $start = Carbon::parse("{$sessionDate} {$session->start_time}");
            $end   = Carbon::parse("{$sessionDate} {$session->end_time}");

            // إذا انتهى الوقت، تتحول لحالة completed في الداتابيز
            if ($now->gt($end)) {
                if ($session->status !== 'completed' && $session->status !== 'cancelled') {
                    $session->update(['status' => 'completed']);
                }
            } 
            // إذا بدأ الوقت، تتحول لحالة ongoing في الداتابيز
            elseif ($now->between($start, $end)) {
                if ($session->status === 'scheduled') {
                    $session->update(['status' => 'ongoing']);
                }
            }
        }
    }

    // تسمية الحالة بالعربية للقراءة المباشرة
    public function getStatusLabelAttribute()
    {
        return match ($this->status) {
            'scheduled' => 'مجدولة',
            'ongoing'   => 'جارية',
            'completed' => 'مكتملة',
            'cancelled' => 'ملغية',
            default     => 'منتهية',
        };
    }

}