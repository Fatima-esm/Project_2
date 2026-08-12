<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CoachProfile extends Model
{
    use HasFactory;

    protected $fillable = [
            'user_id',
            'cv_path',
            'years_of_experience',
            'id_card_image',
        ];


    public function user()
    {
        return $this->belongsTo(User::class);
    }
    protected $appends = ['cv_url'];

    public function getCvUrlAttribute()
    {
        if (!$this->cv_path) {
            return null;
        }

        if (str_starts_with($this->cv_path, 'http')) {
            return $this->cv_path;
        }

        return asset('storage/' . $this->cv_path);
    }



}
