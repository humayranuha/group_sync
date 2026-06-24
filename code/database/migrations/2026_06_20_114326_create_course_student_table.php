<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('course_student', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained('courses')->onDelete('cascade');
            $table->foreignId('student_id')->constrained('users')->onDelete('cascade');
            $table->timestamp('enrolled_at')->nullable();
            $table->timestamps();
            
            // Indexes
            $table->index(['course_id', 'student_id']);
            $table->index('enrolled_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('course_student');
    }
};