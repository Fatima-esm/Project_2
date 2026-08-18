<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;
    
    protected $guarded=[];

    public function getStatusAttribute()
    {
        if ($this->stock_quantity <= 0) {
            return 'out_of_stock';      // غير متوفر
        }

        if ($this->stock_quantity <= 5) {  
            return 'low_stock';         // كمية محدودة
        }

        return 'in_stock';              // متوفر
    }

    public function getStatusLabelAttribute()
    {
        return match($this->status) {
            'in_stock'     => 'متوفر',
            'low_stock'    => 'كمية محدودة',
            'out_of_stock' => 'غير متوفر',
            default        => 'غير معروف',
        };
    }
    
    protected $appends = ['status_label', 'image_url'];

    public function getImageUrlAttribute()
    {
        if (!$this->image) {
            return null;
        }

        if (str_starts_with($this->image, 'http')) {
            return $this->image;
        }

        return asset('storage/' . $this->image);
    }
    
    }
