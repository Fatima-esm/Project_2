<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WorkSchedule extends Model
{
    use HasFactory;

    protected $fillable = [ 'day', 'work_name', 'start_time', 'end_time'];


    public function coaches()
    {
        return $this->belongsToMany(User::class, 'coach_schedule', 'work_schedule_id', 'user_id');
    }
}
