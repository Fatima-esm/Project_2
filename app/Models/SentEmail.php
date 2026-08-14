<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SentEmail extends Model
{
    protected $fillable = [
        'sent_by',
        'coach_id',
        'coach_name',
        'to_email',
        'subject',
        'body',
        'type',
    ];

    public function sender()
    {
        return $this->belongsTo(User::class, 'sent_by');
    }

    public function coach()
    {
        return $this->belongsTo(User::class, 'coach_id');
    }
}