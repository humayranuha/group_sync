<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Group extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'course_id',
        'invitation_code',
        'max_members',
        'created_by',
        'status',
        'github_repo_url',
        'github_repo_id'
    ];

    protected $casts = [
        'max_members' => 'integer',
    ];

    /**
     * ============================================
     * RELATIONSHIPS
     * ============================================
     */

    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    public function members()
    {
        return $this->belongsToMany(User::class, 'group_members', 'group_id', 'user_id')
                    ->withPivot('joined_at', 'role')
                    ->withTimestamps();
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function assignments()
    {
        return $this->belongsToMany(Assignment::class, 'group_assignment')
                    ->withPivot('submitted_at', 'status')
                    ->withTimestamps();
    }

    public function peerReviews()
    {
        return $this->hasMany(PeerReview::class);
    }

    public function contributionScores()
    {
        return $this->hasMany(ContributionScore::class);
    }

    /**
     * ============================================
     * HELPER METHODS
     * ============================================
     */

    /**
     * Get member count
     */
    public function getMemberCountAttribute(): int
    {
        return $this->members()->count();
    }

    /**
     * Check if group is full
     */
    public function isFull(): bool
    {
        return $this->max_members && $this->members()->count() >= $this->max_members;
    }

    /**
     * Check if user is a member
     */
    public function hasMember($userId): bool
    {
        return $this->members()->where('user_id', $userId)->exists();
    }

    /**
     * Get group leader
     */
    public function getLeader()
    {
        return $this->members()
            ->wherePivot('role', 'leader')
            ->first();
    }

    /**
     * Get average contribution score for the group
     */
    public function getAverageScoreAttribute(): float
    {
        $scores = $this->contributionScores()
            ->select('score')
            ->get()
            ->pluck('score')
            ->filter()
            ->toArray();

        return count($scores) > 0 ? array_sum($scores) / count($scores) : 0;
    }

    /**
     * Get total commits for the group
     */
    public function getTotalCommitsAttribute(): int
    {
        return $this->contributionScores()->sum('commits');
    }

    /**
     * Get GitHub repository URL
     */
    public function getGithubUrlAttribute(): ?string
    {
        return $this->github_repo_url;
    }

    /**
     * Check if group has GitHub repo
     */
    public function hasGithubRepo(): bool
    {
        return !empty($this->github_repo_url);
    }

    /**
     * ============================================
     * SCOPES
     * ============================================
     */

    /**
     * Scope for active groups
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope for groups with GitHub repo
     */
    public function scopeWithGithub($query)
    {
        return $query->whereNotNull('github_repo_url');
    }

    /**
     * Scope for groups without GitHub repo
     */
    public function scopeWithoutGithub($query)
    {
        return $query->whereNull('github_repo_url');
    }
}