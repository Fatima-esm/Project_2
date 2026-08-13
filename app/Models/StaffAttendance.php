<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StaffAttendance extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'recorded_by', 'type', 'recorded_at', 'note',
    ];

    protected $casts = [
        'recorded_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function recorder()
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }



    
}
