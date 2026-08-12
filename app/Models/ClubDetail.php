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
