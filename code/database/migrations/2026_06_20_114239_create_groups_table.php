<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('groups', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->foreignId('course_id')->constrained('courses')->onDelete('cascade');
            $table->string('invitation_code')->unique();
            $table->integer('max_members')->default(5);
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->enum('status', ['active', 'archived'])->default('active');
            $table->string('github_repo_url')->nullable();
            $table->string('github_repo_id')->nullable();
            $table->timestamps();
            
            // Indexes
            $table->index('course_id');
            $table->index('created_by');
            $table->index('status');
            $table->index('invitation_code');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('groups');
    }
};