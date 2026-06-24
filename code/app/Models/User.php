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
        'last_login_at'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
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
            'is_active' => 'boolean',
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
     * HELPER METHODS
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
}
