<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

use Laravel\Sanctum\HasApiTokens;

use Spatie\Permission\Traits\HasRoles;  //for role and permission

class User extends Authenticatable
{
    use HasRoles;
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;
    
    protected $guard_name = 'web';
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $guarded=[];

    protected $fillable = [
        'full_name',
        'email',
        'phone',
        'password',
        'role',
        'otp',
        'status',
        'active_at',
        'age',
        'profile_image',
        'gender',
        'membership_number', 
        'coach_id',
        'about_me'

    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    public function coachProfile() {
    return $this->hasOne(CoachProfile::class);
    }

    public function measurements() {
        return $this->hasMany(Measurement::class)->latest(); // latest لجلب آخر قياس أولاً
    }

    public function subscriptions()
    {
        return $this->hasMany(Subscription::class);
    }

    public function activeSubscription()
    {

        return $this->hasOne(Subscription::class)->where('status','paid')->where('expires_at','>',now());

    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    public function goal()
    {
        // المستخدم يتبع هدفاً واحداً
        return $this->belongsTo(Goal::class);
    }

    public function workSchedules()
    {
        return $this->belongsToMany(WorkSchedule::class, 'coach_schedule', 'user_id', 'work_schedule_id');
    }

    //  المدرب الخاص بالمستخدم
    public function coach() {
        return $this->belongsTo(User::class, 'coach_id');
    }

    //  قائمة المتدربين التابعين للمدرب
    public function trainees() {
        return $this->hasMany(User::class, 'coach_id');
    }

    
    
}
