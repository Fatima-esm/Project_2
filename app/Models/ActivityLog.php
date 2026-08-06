<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\SoftDeletes;

class ActivityLog extends Model
{
    use HasApiTokens, HasFactory;

    protected $fillable = [
        'user_id',
        'action',
        'action_label',
        'subject_type',
        'subject_id',
        'details',
        'icon',
        'properties',
    ];

    protected $casts = [
        'properties' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // تسجيل نشاط بسهولة
    public static function log(int $userId, string $action, string $label, array $options = [])
    {
        return self::create([
            'user_id'      => $userId,
            'action'       => $action,
            'action_label' => $label,
            'subject_type' => $options['subject_type'] ?? null,
            'subject_id'   => $options['subject_id'] ?? null,
            'details'      => $options['details'] ?? null,
            'icon'         => $options['icon'] ?? 'default',
            'properties'   => $options['properties'] ?? null,
        ]);
    }
}