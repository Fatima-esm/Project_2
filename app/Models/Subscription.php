<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Subscription extends Model
{
    use HasFactory;

    protected $fillable=[
        'user_id', 'plan_id', 'price', 'duration_days',
        'status', 'transaction_number', 'starts_at', 'expires_at'
    ];
    
    protected $casts = [
        'starts_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

     public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function plan()
    {
        return $this->belongsTo(Plan::class);
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    public function subscriptions()
    {
        return $this->hasMany(Subscription::class);
    }

    public function activeSubscription()
    {
        return $this->subscriptions()
                    ->where('status', 'paid')
                    ->where('expires_at', '>', now())
                    ->latest('expires_at')
                    ->first();
    }

}
