<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Assignment extends Model
{
    use HasFactory;

    protected $fillable = [
        'course_id',
        'title',
        'description',
        'weightage',
        'deadline',
        'peer_review_deadline',
        'created_by',
        'status'
    ];

    protected $casts = [
        'deadline' => 'datetime',
        'peer_review_deadline' => 'datetime',
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

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function groups()
    {
        return $this->belongsToMany(Group::class, 'group_assignment')
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

    public function workingHours()
    {
        return $this->hasMany(WorkingHour::class);
    }

    /**
     * ============================================
     * ACCESSORS & MUTATORS
     * ============================================
     */

    public function getWeightageAttribute($value)
    {
        return json_decode($value, true);
    }

    public function setWeightageAttribute($value)
    {
        $this->attributes['weightage'] = is_array($value) ? json_encode($value) : $value;
    }

    /**
     * ============================================
     * HELPER METHODS
     * ============================================
     */

    /**
     * Check if assignment is active (deadline not passed)
     */
    public function isActive(): bool
    {
        return $this->deadline && $this->deadline->isFuture();
    }

    /**
     * Check if peer review is active
     */
    public function isPeerReviewActive(): bool
    {
        return $this->peer_review_deadline && $this->peer_review_deadline->isFuture();
    }

    /**
     * Get submission status for a specific group
     */
    public function getSubmissionStatus($groupId)
    {
        $groupAssignment = $this->groups()
            ->where('group_id', $groupId)
            ->first();

        return $groupAssignment ? $groupAssignment->pivot->status : 'not_submitted';
    }

    /**
     * Get submissions count
     */
    public function getSubmissionsCount(): int
    {
        return $this->groups()
            ->wherePivot('status', 'submitted')
            ->count();
    }

    /**
     * Get total groups count
     */
    public function getTotalGroupsCount(): int
    {
        return $this->groups()->count();
    }

    /**
     * Scope for active assignments
     */
    public function scopeActive($query)
    {
        return $query->where('deadline', '>=', now());
    }

    /**
     * Scope for expired assignments
     */
    public function scopeExpired($query)
    {
        return $query->where('deadline', '<', now());
    }

    /**
     * Scope for peer review active
     */
    public function scopePeerReviewActive($query)
    {
        return $query->where('peer_review_deadline', '>=', now());
    }
}