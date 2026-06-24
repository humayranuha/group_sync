<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contribution_scores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('group_id')->constrained('groups')->onDelete('cascade');
            $table->foreignId('assignment_id')->constrained('assignments')->onDelete('cascade');
            $table->decimal('score', 5, 2)->default(0);
            $table->enum('status', ['normal', 'warning', 'critical'])->default('normal');
            $table->json('breakdown')->nullable();
            
            // GitHub related fields
            $table->integer('commits')->default(0);
            $table->integer('pull_requests')->default(0);
            $table->integer('forks')->default(0);
            $table->integer('lines_added')->default(0);
            $table->integer('lines_deleted')->default(0);
            $table->integer('peer_review_score')->default(0);
            $table->integer('attendance_score')->default(0);
            $table->integer('working_hours_score')->default(0);
            
            $table->timestamp('calculated_at')->nullable();
            $table->timestamps();
            
            // Indexes
            $table->index(['student_id', 'assignment_id']);
            $table->index('group_id');
            $table->index('calculated_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contribution_scores');
    }
};