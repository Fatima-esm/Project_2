<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SessionBooking extends Model
{
    use HasFactory;

    protected $fillable = [
        'session_id', 'user_id', 'status', 'booked_at', 'attended_at',
    ];

    protected $casts = [
        'booked_at'   => 'datetime',
        'attended_at' => 'datetime',
    ];

    public function session()
    {
        return $this->belongsTo(Session::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
