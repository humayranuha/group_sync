<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ContributionScore extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id',
        'group_id',
        'assignment_id',
        'score',
        'status',
        'breakdown',
        'calculated_at',
        // GitHub related fields
        'commits',
        'pull_requests',
        'forks',
        'lines_added',
        'lines_deleted',
        'peer_review_score',
        'attendance_score',
        'working_hours_score',
    ];

    protected $casts = [
        'calculated_at' => 'datetime',
        'commits' => 'integer',
        'pull_requests' => 'integer',
        'forks' => 'integer',
        'lines_added' => 'integer',
        'lines_deleted' => 'integer',
        'peer_review_score' => 'integer',
        'attendance_score' => 'integer',
        'working_hours_score' => 'integer',
    ];

    /**
     * ============================================
     * RELATIONSHIPS
     * ============================================
     */

    public function student()
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function group()
    {
        return $this->belongsTo(Group::class);
    }

    public function assignment()
    {
        return $this->belongsTo(Assignment::class);
    }

    /**
     * ============================================
     * ACCESSORS & MUTATORS
     * ============================================
     */

    public function getBreakdownAttribute($value)
    {
        return json_decode($value, true);
    }

    public function setBreakdownAttribute($value)
    {
        $this->attributes['breakdown'] = is_array($value) ? json_encode($value) : $value;
    }

    /**
     * ============================================
     * HELPER METHODS
     * ============================================
     */

    /**
     * Get the classification based on score.
     */
    public function getClassificationAttribute(): string
    {
        if ($this->score >= 80) {
            return 'Active';
        } elseif ($this->score >= 60) {
            return 'Moderate';
        } elseif ($this->score >= 40) {
            return 'Passive';
        } else {
            return 'Free Rider';
        }
    }

    /**
     * Get the color class for the classification.
     */
    public function getClassificationColorAttribute(): string
    {
        return match($this->classification) {
            'Active' => 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300',
            'Moderate' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-300',
            'Passive' => 'bg-orange-100 text-orange-800 dark:bg-orange-900/30 dark:text-orange-300',
            'Free Rider' => 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300',
            default => 'bg-gray-100 text-gray-600 dark:bg-gray-700/30 dark:text-gray-400'
        };
    }

    /**
     * Get the badge HTML for the classification.
     */
    public function getClassificationBadgeAttribute(): string
    {
        return sprintf(
            '<span class="px-2 py-1 rounded-full text-xs font-medium %s">%s</span>',
            $this->classification_color,
            $this->classification
        );
    }

    /**
     * Calculate total contributions.
     */
    public function getTotalContributionsAttribute(): int
    {
        return ($this->commits ?? 0) + 
               ($this->pull_requests ?? 0) + 
               ($this->forks ?? 0);
    }

    /**
     * Check if contribution score is above threshold.
     */
    public function isAboveThreshold(int $threshold = 60): bool
    {
        return $this->score >= $threshold;
    }

    /**
     * Get average score
     */
    public function getAverageScoreAttribute(): float
    {
        $scores = [
            $this->score,
            $this->peer_review_score ?? 0,
            $this->attendance_score ?? 0,
            $this->working_hours_score ?? 0
        ];
        
        return array_sum($scores) / count($scores);
    }

    /**
     * ============================================
     * SCOPES
     * ============================================
     */

    /**
     * Scope a query to only include scores above a threshold.
     */
    public function scopeAboveThreshold($query, int $threshold = 60)
    {
        return $query->where('score', '>=', $threshold);
    }

    /**
     * Scope a query to only include scores below a threshold.
     */
    public function scopeBelowThreshold($query, int $threshold = 60)
    {
        return $query->where('score', '<', $threshold);
    }

    /**
     * Scope a query to only include latest scores.
     */
    public function scopeLatest($query)
    {
        return $query->orderBy('calculated_at', 'desc');
    }

    /**
     * Scope for a specific student
     */
    public function scopeForStudent($query, $studentId)
    {
        return $query->where('student_id', $studentId);
    }

    /**
     * Scope for a specific group
     */
    public function scopeForGroup($query, $groupId)
    {
        return $query->where('group_id', $groupId);
    }
}