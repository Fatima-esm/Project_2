<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GymHall extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'type',
        'capacity',
        'status',
        'description',
    ];

    public function sessions()
    {
        return $this->hasMany(Session::class, 'hall_id');
    }

    public function isAvailable(): bool
    {
        return $this->status === 'available';
    }

}
