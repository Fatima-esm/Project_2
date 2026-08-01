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
        Schema::create('workout_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('coach_id')->constrained('users')->cascadeOnDelete();   // الكوتش المنشئ للخطة
            $table->foreignId('trainee_id')->constrained('users')->cascadeOnDelete(); // المتدرب المستهدف
            $table->foreignId('exercise_id')->constrained('exercises')->cascadeOnDelete(); 
            
            $table->integer('sets')->default(1);          // عدد الجولات
            $table->integer('reps')->default(12);          // عدد التكرارات لكل جولة
            $table->string('rest_time')->nullable(); // وقت الراحة بين الجولات 60 ثانية
            $table->date('plan_date')->nullable(); // تاريخ الخطة     
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('workout_plans');
    }
};
