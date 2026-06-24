<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->enum('role', ['student', 'professor', 'admin'])->default('student');
            $table->string('department')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_login_at')->nullable();
            $table->string('profile_picture')->nullable();
            
            // GitHub fields
            $table->string('github_token')->nullable();
            $table->string('github_username')->nullable();
            $table->string('github_repo_url')->nullable();
            $table->timestamp('github_connected_at')->nullable();
            
            $table->rememberToken();
            $table->timestamps();
            
            // Indexes
            $table->index('email');
            $table->index('role');
            $table->index('github_username');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};