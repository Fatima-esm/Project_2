<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Measurement extends Model
{
    use HasFactory;
    protected $fillable = ['user_id','height','goal', 'weight', 'fat_percentage', 'muscle_mass'];

    public function user() {
        return $this->belongsTo(User::class);
    }
}
