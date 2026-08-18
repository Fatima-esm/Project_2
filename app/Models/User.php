<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Activitylog\LogOptions;
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
        'about_me',
        'session_cancel_count',
        'booking_banned_until',

    ];

    protected $appends = ['profile_image_url']; 

    public function getProfileImageUrlAttribute()
    {
        if (!$this->profile_image) {
            return null;
        }

        if (str_starts_with($this->profile_image, 'http')) {
            return $this->profile_image;
        }

        return asset('storage/' . $this->profile_image);
    }
    
    protected $hidden = [
        'password',
        'remember_token',
    ];


    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'booking_banned_until' => 'datetime',
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
        return $this->belongsTo(Goal::class);
    }

    public function workSchedules()
    {
        return $this->belongsToMany(WorkSchedule::class, 'coach_schedule', 'user_id', 'work_schedule_id');
    }

    public function coach() {
        return $this->belongsTo(User::class, 'coach_id');
    }

    public function isAvailableForTrainees(): bool
    {
        if ($this->role !== 'coach' || $this->status !== 'active') {
            return false;
        }

        $traineesCount = self::where('coach_id', $this->id)->count();

        return $traineesCount < 20;
    }

    public function trainees() {
        return $this->hasMany(User::class, 'coach_id');
    }

    public function workoutPlans()
    {
        return $this->hasMany(WorkoutPlan::class, 'trainee_id');        
    }
    
    public function schedules()
    {
        return $this->hasMany(CoachSchedule::class, 'user_id');
    }

    public function salaries()
    {
        return $this->hasMany(Salary::class, 'user_id');
    }

    // تحديد الخصائص التي تريد مراقبتها وتسيجلها
    // public function getActivitylogOptions(): LogOptions
    // {
    //     return LogOptions::defaults()
    //         ->logAll(['full_name','email', 'status']) // أو يمكنك تحديد حقول معينة لتخفيف الحجم ->logOnly(['full_name', 'status'])
    //         ->logOnlyDirty(); // تسجيل التغييرات الفعلية فقط
    // }

    public function coachedSessions()
    {
        return $this->hasMany(Session::class, 'coach_id');
    }

    public function sessionBookings()
    {
        return $this->hasMany(SessionBooking::class);
    }
}
