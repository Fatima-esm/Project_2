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
}
