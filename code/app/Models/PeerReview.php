<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PeerReview extends Model
{
    use HasFactory;

    protected $fillable = [
        'reviewer_id',
        'reviewee_id',
        'group_id',
        'assignment_id',
        'communication_rating',
        'reliability_rating',
        'task_participation_rating',
        'overall_rating',
        'comments',
        'submitted_at',
        'is_anonymous'
    ];

    protected $casts = [
        'submitted_at' => 'datetime',
        'is_anonymous' => 'boolean',
        'communication_rating' => 'integer',
        'reliability_rating' => 'integer',
        'task_participation_rating' => 'integer',
        'overall_rating' => 'integer',
    ];

    /**
     * ============================================
     * RELATIONSHIPS
     * ============================================
     */

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewer_id');
    }

    public function reviewee()
    {
        return $this->belongsTo(User::class, 'reviewee_id');
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

    public function getAverageRatingAttribute()
    {
        $ratings = [
            $this->communication_rating,
            $this->reliability_rating,
            $this->task_participation_rating
        ];
        
        return array_sum($ratings) / count($ratings);
    }

    /**
     * Get overall rating if set, otherwise calculate average
     */
    public function getOverallRatingAttribute($value)
    {
        if ($value) {
            return $value;
        }
        return $this->average_rating;
    }

    /**
     * Set overall rating
     */
    public function setOverallRatingAttribute($value)
    {
        $this->attributes['overall_rating'] = $value ?: $this->average_rating;
    }

    /**
     * ============================================
     * HELPER METHODS
     * ============================================
     */

    /**
     * Check if review is submitted
     */
    public function isSubmitted(): bool
    {
        return !is_null($this->submitted_at);
    }

    /**
     * Check if review is anonymous
     */
    public function isAnonymous(): bool
    {
        return $this->is_anonymous;
    }

    /**
     * Get rating level description
     */
    public function getRatingLevelAttribute(): string
    {
        $avg = $this->average_rating;
        
        if ($avg >= 4.5) {
            return 'Excellent';
        } elseif ($avg >= 3.5) {
            return 'Good';
        } elseif ($avg >= 2.5) {
            return 'Average';
        } elseif ($avg >= 1.5) {
            return 'Below Average';
        } else {
            return 'Poor';
        }
    }

    /**
     * Get rating level color
     */
    public function getRatingLevelColorAttribute(): string
    {
        return match($this->rating_level) {
            'Excellent' => 'text-green-600 dark:text-green-400',
            'Good' => 'text-blue-600 dark:text-blue-400',
            'Average' => 'text-yellow-600 dark:text-yellow-400',
            'Below Average' => 'text-orange-600 dark:text-orange-400',
            'Poor' => 'text-red-600 dark:text-red-400',
            default => 'text-gray-600 dark:text-gray-400'
        };
    }

    /**
     * ============================================
     * SCOPES
     * ============================================
     */

    /**
     * Scope for submitted reviews
     */
    public function scopeSubmitted($query)
    {
        return $query->whereNotNull('submitted_at');
    }

    /**
     * Scope for pending reviews
     */
    public function scopePending($query)
    {
        return $query->whereNull('submitted_at');
    }

    /**
     * Scope for anonymous reviews
     */
    public function scopeAnonymous($query)
    {
        return $query->where('is_anonymous', true);
    }

    /**
     * Scope for reviews of a specific reviewee
     */
    public function scopeForReviewee($query, $revieweeId)
    {
        return $query->where('reviewee_id', $revieweeId);
    }

    /**
     * Scope for reviews by a specific reviewer
     */
    public function scopeByReviewer($query, $reviewerId)
    {
        return $query->where('reviewer_id', $reviewerId);
    }

    /**
     * Scope for high ratings
     */
    public function scopeHighRating($query, float $threshold = 4.0)
    {
        return $query->whereRaw('(communication_rating + reliability_rating + task_participation_rating) / 3 >= ?', [$threshold]);
    }

    /**
     * Scope for low ratings
     */
    public function scopeLowRating($query, float $threshold = 2.0)
    {
        return $query->whereRaw('(communication_rating + reliability_rating + task_participation_rating) / 3 <= ?', [$threshold]);
    }
}