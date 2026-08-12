<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('club_details', function (Blueprint $table) {
            $table->id();
            $table->string('name')->default('SMART GYM'); // اسم النادي
            $table->string('phone')->nullable(); // رقم التواصل
            $table->string('image')->nullable();
            $table->string('email')->nullable(); // رقم التواصل
            $table->string('location')->nullable(); // المكان/الموقع
            $table->time('opening_time')->nullable(); // وقت فتح النادي (مثلاً: 06:00:00)
            $table->time('closing_time')->nullable(); // وقت إغلاق النادي (مثلاً: 01:00:00) 
            $table->enum('status', ['open', 'closed', 'holiday'])->default('open');
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('club_details');
    }
};