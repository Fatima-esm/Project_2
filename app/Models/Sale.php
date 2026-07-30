<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Sale extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'sold_by', 'customer_name', 'customer_phone',
        'total_amount', 'payment_method', 'status', 'notes'
    ];

    public function user() { 
        return $this->belongsTo(User::class); 
    }
    
    public function seller() {
         return $this->belongsTo(User::class, 'sold_by'); 
    }

    public function items() {
         return $this->hasMany(SaleItem::class); 
    }
}
