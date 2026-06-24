<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contribution_scores', function (Blueprint $table) {
            $table->integer('commits')->default(0)->after('score');
            $table->integer('pull_requests')->default(0)->after('commits');
            $table->integer('forks')->default(0)->after('pull_requests');
            $table->integer('lines_added')->default(0)->after('forks');
            $table->integer('lines_deleted')->default(0)->after('lines_added');
            $table->integer('peer_review_score')->default(0)->after('lines_deleted');
            $table->integer('attendance_score')->default(0)->after('peer_review_score');
            $table->integer('working_hours_score')->default(0)->after('attendance_score');
        });
    }

    public function down(): void
    {
        Schema::table('contribution_scores', function (Blueprint $table) {
            $table->dropColumn([
                'commits',
                'pull_requests',
                'forks',
                'lines_added',
                'lines_deleted',
                'peer_review_score',
                'attendance_score',
                'working_hours_score'
            ]);
        });
    }
};