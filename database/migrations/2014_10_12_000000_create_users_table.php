<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('full_name');
            $table->integer('age')->nullable(); 
             $table->string('profile_image')->nullable();  
            $table->string('gender')->nullable();  
            $table->boolean('active_at')->default(0);  
            $table->string('email')->unique();
            $table->string('phone')->unique();
            $table->string('password');
            $table->string('membership_number')->unique();
            $table->string('about_me')->nullable();


            $table->enum('role', ['admin', 'reception', 'coach', 'trainee']);
            $table->foreignId('coach_id')->nullable()->constrained('users')->onDelete('set null');
            
            $table->string('otp')->nullable(); 
            $table->timestamp('email_verified_at')->nullable();

            $table->enum('status', ['pending', 'active', 'rejected', 'expired', 'banned', 'on_leave'])->default('pending'); 
            $table->string('status_reason')->nullable();

            $table->foreignId('goal_id')->nullable()->constrained('goals')->onDelete('set null');
            
            $table->integer('session_cancel_count')->default(0);
            $table->timestamp('booking_banned_until')->nullable();

            $table->rememberToken();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
        $table->dropColumn('otp');
        });
        
        Schema::dropIfExists('users');
    }
};
