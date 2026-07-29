<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    use HasFactory;

     protected $fillable=[
        'user_id',
        'subscription_id',
        'transaction_number',
        'amount',
        'payment_method',
        'status',
        'company_phone',
        'sender_phone', // تأكد من إضافتها
        'sender_name',
        'notes'
        ];


        
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function subscription()
    {
        return $this->belongsTo(Subscription::class);
    }

    public function plan()
    {
        return $this->hasOneThrough(Plan::class, Subscription::class);
    }
    
}
