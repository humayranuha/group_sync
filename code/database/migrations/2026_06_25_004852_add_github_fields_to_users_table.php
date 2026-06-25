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
        Schema::table('users', function (Blueprint $table) {
            // Check if columns exist before adding
            if (!Schema::hasColumn('users', 'github_repo_owner')) {
                $table->string('github_repo_owner')->nullable()->after('github_repo_url');
            }
            
            if (!Schema::hasColumn('users', 'github_repo_name')) {
                $table->string('github_repo_name')->nullable()->after('github_repo_owner');
            }
            
            if (!Schema::hasColumn('users', 'total_commits')) {
                $table->integer('total_commits')->default(0)->after('github_repo_name');
            }
            
            if (!Schema::hasColumn('users', 'weekly_commit_data')) {
                $table->json('weekly_commit_data')->nullable()->after('total_commits');
            }
            
            if (!Schema::hasColumn('users', 'last_github_sync')) {
                $table->timestamp('last_github_sync')->nullable()->after('weekly_commit_data');
            }
            
            if (!Schema::hasColumn('users', 'classification')) {
                $table->string('classification')->default('Moderate')->after('last_github_sync');
            }
            
            if (!Schema::hasColumn('users', 'overall_score')) {
                $table->integer('overall_score')->default(0)->after('classification');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $columns = [
                'github_repo_owner',
                'github_repo_name',
                'total_commits',
                'weekly_commit_data',
                'last_github_sync',
                'classification',
                'overall_score'
            ];
            
            foreach ($columns as $column) {
                if (Schema::hasColumn('users', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};