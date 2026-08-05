<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class Activity extends Model
{
    protected $guarded = [];

    // تحديد اسم الجدول بناءً على إعدادات الحزمة أو بشكل مباشر
    public function getTable()
    {
        return config('activitylog.table_name', 'activity_log');
    }

    // العلاقة لمعرفة من قام بالعملية (الموظف / الأدمن)
    public function causer()
    {
        return $this->morphTo();
    }

    // العلاقة للشيء الذي تم تنفيذ العملية عليه (مثل المتدرب أو المنتج)
    public function subject()
    {
        return $this->morphTo();
    }
}