<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class ClubEvent extends Model
{
    use HasFactory;
    
    protected $guarded = [];
    protected $appends = ['image_url', 'status_label'];

    protected $casts = [
        'event_date' => 'date',
        'is_active'  => 'boolean',
    ];

    public function getImageUrlAttribute()
    {
        if (!$this->image) return null;
        if (str_starts_with($this->image, 'http')) return $this->image;
        return asset('storage/' . $this->image);
    }

    public function getDisplayStatusAttribute(): string
    {
        if (in_array($this->status, ['cancelled', 'unavailable'], true)) {
            return $this->status;
        }

        $start = Carbon::parse(
            $this->event_date->format('Y-m-d') . ' ' . $this->start_time
        );

        $endTime = $this->end_time ?: $this->start_time;
        $end = Carbon::parse(
            $this->event_date->format('Y-m-d') . ' ' . $endTime
        );

        // إذا لا توجد نهاية، اعتبر مدتها ساعة
        if (!$this->end_time) {
            $end = $start->copy()->addHour();
        }

        $now = now();

        if ($now->between($start, $end)) {
            return 'ongoing';
        }

        if ($now->lt($start)) {
            return 'available';
        }

        return 'completed';
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->display_status) {
            'available'   => 'متاحة',
            'ongoing'     => 'جارية',
            'unavailable' => 'غير متاحة',
            'cancelled'   => 'ملغاة',
            'completed'   => 'منتهية',
            default       => 'غير معروف',
        };
    }
    
    }
