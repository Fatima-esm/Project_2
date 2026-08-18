<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;

use Illuminate\Database\Eloquent\Model;

class CoachRating extends Model
{
    use  HasFactory;

    protected $fillable = [
        'trainee_id',
        'coach_id',
        'rating',
        'comment',
    ];

    protected $casts = [
        'rating' => 'integer',
    ];

    public function trainee()
    {
        return $this->belongsTo(User::class, 'trainee_id');
    }

    public function coach()
    {
        return $this->belongsTo(User::class, 'coach_id');
    }
}