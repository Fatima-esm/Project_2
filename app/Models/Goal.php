<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Goal extends Model
{
    use HasFactory;
    
    protected $guarded = [];

    public function users()
    {
        // الهدف الواحد يمكن أن يختاره أكثر من مستخدم
        return $this->hasMany(User::class);
    }
}
