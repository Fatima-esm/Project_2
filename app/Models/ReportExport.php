<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReportExport extends Model
{
    use HasFactory;

    protected $fillable = [
        'generated_by', 'type', 'from_date', 'to_date', 'payload', 'file_path'
    ];

    protected $casts = [
        'payload'   => 'array',
        'from_date' => 'date',
        'to_date'   => 'date',
    ];

    public function generator()
    {
        return $this->belongsTo(User::class, 'generated_by');
    }


    
}