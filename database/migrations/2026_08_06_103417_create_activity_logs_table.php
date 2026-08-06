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
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); // الموظف
            $table->string('action');          // مثل: create_subscription, sale, check_in...
            $table->string('action_label');    // النص العربي: إنشاء اشتراك جديد
            $table->string('subject_type')->nullable(); // App\Models\Subscription
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->text('details')->nullable(); // تفاصيل إضافية
            $table->string('icon')->nullable();  // subscription, payment, login...
            $table->json('properties')->nullable(); // بيانات إضافية إن احتجت

            $table->index(['user_id', 'created_at']);
            
            $table->timestamps();
            
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
    }
};
