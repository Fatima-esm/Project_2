<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClubService extends Model
{
    use HasFactory;
    protected $guarded = [];

    public function getStatusLabelAttribute()
    {
        return $this->status === 'available' ? 'متوفرة' : 'غير متوفرة';
    }
    
}
