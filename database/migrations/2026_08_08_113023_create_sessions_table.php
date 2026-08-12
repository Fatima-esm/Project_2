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
        Schema::create('sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('coach_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('hall_id')->constrained('gym_halls')->restrictOnDelete();
            $table->enum('type', ['group', 'individual']);
            $table->string('title');
            $table->text('description')->nullable(); // هدف الجلسة
            $table->date('session_date');
            $table->time('start_time');
            $table->time('end_time');
            $table->unsignedInteger('capacity'); // يُنسخ من الصالة أو يحدده الكوتش (≤ سعة الصالة)
            $table->enum('status', ['scheduled', 'ongoing', 'completed', 'cancelled', 'missed'])->default('scheduled');
            $table->timestamp('coach_confirmed_at')->nullable();
            $table->timestamps();

            $table->index(['coach_id', 'session_date']);
            $table->index(['hall_id', 'session_date']);
            $table->index(['type', 'session_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sessions');
    }
};
