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
        Schema::create('salaries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete(); 
            $table->decimal('base_salary', 10, 2);       // الراتب الأساسي
            $table->decimal('bonus', 10, 2)->default(0); // المكافآت الإضافية
            $table->decimal('deduction', 10, 2)->default(0); // الخصومات
            $table->decimal('net_salary', 10, 2);      // الصافي (الأساسي + المكافآت - الخصومات)
            $table->string('month');                     // الشهر وسنة الراتب (مثلا: "2026-08")
            $table->text('notes')->nullable();           // سبب المكافأة أو الخصم أو ملاحظات الإدارة
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('salaries');
    }
};
