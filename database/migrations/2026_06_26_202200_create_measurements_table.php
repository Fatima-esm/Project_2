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
        Schema::create('measurements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade')->onUpdate('cascade');
            $table->decimal('height', 5, 2)->nullable(); // الطول
            $table->decimal('weight', 5, 2)->nullable(); // الوزن
            $table->decimal('fat_percentage', 5, 2)->nullable(); // نسبة الدهون
            $table->decimal('muscle_mass', 5, 2)->nullable(); // كتلة العضلات
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('measurements');
    }
};
