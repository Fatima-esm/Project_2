<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SentEmail extends Model
{
    protected $fillable = [
        'sent_by',
        'user_id',
        'user_name',
        'to_email',
        'subject',
        'body',
        'type',
    ];

    public function sender()
    {
        return $this->belongsTo(User::class, 'sent_by');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}