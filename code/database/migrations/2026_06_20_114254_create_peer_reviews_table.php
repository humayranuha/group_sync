<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('peer_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reviewer_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('reviewee_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('group_id')->constrained('groups')->onDelete('cascade');
            $table->foreignId('assignment_id')->constrained('assignments')->onDelete('cascade');
            $table->integer('communication_rating');
            $table->integer('reliability_rating');
            $table->integer('task_participation_rating');
            $table->integer('overall_rating')->nullable();
            $table->text('comments')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->boolean('is_anonymous')->default(true);
            $table->timestamps();
            
            // Indexes
            $table->index(['reviewer_id', 'reviewee_id', 'assignment_id']);
            $table->index('submitted_at');
            $table->index('group_id');
            $table->index('assignment_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('peer_reviews');
    }
};