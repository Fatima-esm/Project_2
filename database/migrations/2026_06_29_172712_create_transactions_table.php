<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            
            // رقم العملية
            $table->string('transaction_number')->unique();
            // المبلغ
            $table->decimal('amount', 10, 2);   
            // هاتف الشركة
            $table->string('company_phone')->nullable();
            // هاتف المرسل
            $table->string('sender_phone')->nullable();
            // اسم المرسل
            $table->string('sender_name')->nullable();
            
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('subscription_id')->nullable()->constrained()->onDelete('set null');
            
            // نوع الدفع
            $table->enum('payment_method', ['cash', 'bank', 'online', 'card'])
                  ->default('bank');
            
            // حالة المعاملة
            $table->enum('status', ['pending', 'verified', 'failed', 'cancelled'])
                  ->default('pending');
            
            // ملاحظات إضافية (اختياري)
            $table->text('notes')->nullable();
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};