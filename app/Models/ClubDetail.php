<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClubDetail extends Model
{
    use HasFactory;

    protected $fillable = [
    'name',
    'phone',
    'email',
    'location',
    'opening_time',
    'closing_time',
    'image',
    'description',
    'status', // تم استبدال is_open بـ status
    ];
    public function resolveCurrentStatus(): string
    {
        if (!$this->opening_time || !$this->closing_time) {
            return 'open';
        }

        $now = now();
        $open  = \Carbon\Carbon::parse($now->toDateString() . ' ' . $this->opening_time);
        $close = \Carbon\Carbon::parse($now->toDateString() . ' ' . $this->closing_time);

        // دوام يمتد بعد منتصف الليل (مثل 06:00 → 01:00)
        if ($close->lte($open)) {
            if ($now->gte($open)) {
                // بعد الفتح اليوم → الإغلاق غداً
                $close->addDay();
            } else {
                // بعد منتصف الليل وقبل الإغلاق (مثل 00:30)
                $open->subDay();
            }
        }

        return $now->between($open, $close) ? 'open' : 'closed';
    }

    public function getStatusLabelAttribute()
    {
        return match($this->status) {
            'open' => 'مفتوح',
            'closed' => 'مغلق',
            'holiday' => 'في إجازة',
            default => 'غير محدد',
        };
    }

    protected $appends = ['image_url'];

    public function getImageUrlAttribute()
    {
        if (!$this->image) {
            return null;
        }

        if (str_starts_with($this->image, 'http')) {
            return $this->image;
        }

        return asset('storage/' . $this->image);
    }

}
