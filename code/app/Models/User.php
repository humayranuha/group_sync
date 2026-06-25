<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'password',
        'role',
        'department',
        'profile_picture',
        'is_active',
        'last_login_at',
        'student_id',
        // GitHub fields
        'github_token',
        'github_username',
        'github_repo_owner',
        'github_repo_name',
        'github_repo_url',
        'github_connected_at',
        'total_commits',
        'weekly_commit_data',
        'last_github_sync',
        'classification',
        'overall_score',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'github_token', // Hide GitHub token for security
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'last_login_at' => 'datetime',
            'github_connected_at' => 'datetime',
            'last_github_sync' => 'datetime',
            'is_active' => 'boolean',
            'weekly_commit_data' => 'array',
        ];
    }

    /**
     * ============================================
     * RELATIONSHIPS
     * ============================================
     */

    /**
     * Get the full name of the user
     */
    public function getFullNameAttribute(): string
    {
        return $this->first_name . ' ' . $this->last_name;
    }

    /**
     * Get the courses taught by this teacher
     */
    public function taughtCourses()
    {
        return $this->hasMany(Course::class, 'teacher_id');
    }

    /**
     * Get the courses this student is enrolled in
     */
    public function courses()
    {
        return $this->belongsToMany(Course::class, 'course_student', 'student_id', 'course_id')
                    ->withPivot('enrolled_at')
                    ->withTimestamps();
    }

    /**
     * Get the groups this student belongs to
     */
    public function groups()
    {
        return $this->belongsToMany(Group::class, 'group_members', 'user_id', 'group_id')
                    ->withPivot('joined_at', 'role')
                    ->withTimestamps();
    }

    /**
     * Get the groups this student has created
     */
    public function createdGroups()
    {
        return $this->hasMany(Group::class, 'created_by');
    }

    /**
     * Get the peer reviews submitted by this student
     */
    public function submittedPeerReviews()
    {
        return $this->hasMany(PeerReview::class, 'reviewer_id');
    }

    /**
     * Get the peer reviews received by this student
     */
    public function receivedPeerReviews()
    {
        return $this->hasMany(PeerReview::class, 'reviewee_id');
    }

    public function peerReviewsReceived()
    {
        return $this->receivedPeerReviews();
    }

    /**
     * Get the contribution scores for this student
     */
    public function contributionScores()
    {
        return $this->hasMany(ContributionScore::class, 'student_id');
    }

    /**
     * Get the latest contribution score for this student
     */
    public function getLatestContributionScore()
    {
        return $this->contributionScores()->latest()->first();
    }

    /**
     * Get the assignments created by this teacher
     */
    public function createdAssignments()
    {
        return $this->hasMany(Assignment::class, 'created_by');
    }

    /**
     * Get the notifications for this user
     */
    public function notifications()
    {
        return $this->hasMany(Notification::class);
    }

    /**
     * Get the audit logs for this user
     */
    public function auditLogs()
    {
        return $this->hasMany(AuditLog::class, 'user_id');
    }

    /**
     * ============================================
     * GITHUB METHODS
     * ============================================
     */

    /**
     * Check if user has GitHub connected
     */
    public function hasGithubConnected(): bool
    {
        return !empty($this->github_repo_url) && !empty($this->github_token);
    }

    /**
     * Get GitHub repository path (owner/repo)
     */
    public function getGithubRepoPathAttribute(): ?string
    {
        if (!$this->github_repo_url) {
            return null;
        }
        
        preg_match('/github\.com\/([^\/]+\/[^\/]+)/', $this->github_repo_url, $matches);
        return $matches[1] ?? null;
    }

    /**
     * Get the user's avatar URL
     */
    public function getAvatarUrlAttribute(): string
    {
        // If user has GitHub username, try to get GitHub avatar
        if ($this->github_username) {
            return "https://github.com/{$this->github_username}.png";
        }
        
        // If profile picture exists in database
        if ($this->profile_picture) {
            return asset('storage/profile-pictures/' . $this->profile_picture);
        }
        
        // Fallback to UI Avatars
        $name = urlencode($this->getFullNameAttribute());
        return "https://ui-avatars.com/api/?name={$name}&background=3AAFA9&color=fff&size=120&bold=true";
    }

    /**
     * Get GitHub API token
     */
    public function getGithubToken(): ?string
    {
        return $this->github_token;
    }

    /**
     * Check if GitHub token is valid (not expired)
     */
    public function hasValidGithubToken(): bool
    {
        // If connected_at is more than 30 days ago, token might be expired
        if ($this->github_connected_at) {
            return $this->github_connected_at->diffInDays(now()) < 30;
        }
        return false;
    }

    /**
     * Disconnect GitHub
     */
    public function disconnectGithub(): void
    {
        $this->github_token = null;
        $this->github_username = null;
        $this->github_repo_owner = null;
        $this->github_repo_name = null;
        $this->github_repo_url = null;
        $this->github_connected_at = null;
        $this->total_commits = 0;
        $this->weekly_commit_data = null;
        $this->last_github_sync = null;
        $this->classification = 'Moderate';
        $this->overall_score = 0;
        $this->save();
    }

    /**
     * ============================================
     * ROLE METHODS
     * ============================================
     */

    /**
     * Check if user is a student
     */
    public function isStudent(): bool
    {
        return $this->role === 'student';
    }

    /**
     * Check if user is a professor
     */
    public function isProfessor(): bool
    {
        return $this->role === 'professor';
    }

    /**
     * Check if user is an admin
     */
    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    /**
     * Check if user has a specific role
     */
    public function hasRole(string $role): bool
    {
        return $this->role === $role;
    }

    /**
     * ============================================
     * GROUP METHODS
     * ============================================
     */

    /**
     * Get the user's current group for a specific course
     */
    public function getGroupForCourse($courseId)
    {
        return $this->groups()
            ->whereHas('course', function($query) use ($courseId) {
                $query->where('course_id', $courseId);
            })
            ->first();
    }

    /**
     * Check if user is in a group for a specific course
     */
    public function hasGroupForCourse($courseId): bool
    {
        return $this->groups()
            ->whereHas('course', function($query) use ($courseId) {
                $query->where('course_id', $courseId);
            })
            ->exists();
    }

    /**
     * ============================================
     * SCOPES
     * ============================================
     */

    /**
     * Scope a query to only include users with GitHub connected.
     */
    public function scopeWithGithub($query)
    {
        return $query->whereNotNull('github_repo_url')
                     ->whereNotNull('github_token');
    }

    /**
     * Scope a query to only include users without GitHub connected.
     */
    public function scopeWithoutGithub($query)
    {
        return $query->whereNull('github_repo_url')
                     ->orWhereNull('github_token');
    }

    /**
     * Scope a query to only include active users.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope a query to only include users with a specific role.
     */
    public function scopeWithRole($query, string $role)
    {
        return $query->where('role', $role);
    }
}