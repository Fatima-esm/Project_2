<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WorkoutPlan extends Model
{
    use HasFactory;

    protected $fillable = [
        'coach_id',
        'trainee_id',
        'exercise_id',
        'sets',
        'reps',
        'rest_time',
        'plan_date',
        'notes',
    ];

    // علاقة مع التمرين المختار
    public function exercise()
    {
        return $this->belongsTo(Exercise::class);
    }

    // علاقة مع المتدرب
    public function trainee()
    {
        return $this->belongsTo(User::class, 'trainee_id');
    }

    // علاقة مع الكوتش
    public function coach()
    {
        return $this->belongsTo(User::class, 'coach_id');
    }
    
}
