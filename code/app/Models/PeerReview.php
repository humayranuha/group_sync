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
        'comments',
        'submitted_at',
        'is_anonymous'
    ];

    protected $casts = [
        'submitted_at' => 'datetime',
        'is_anonymous' => 'boolean',
    ];

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

    public function getAverageRatingAttribute()
    {
        return ($this->communication_rating + $this->reliability_rating + $this->task_participation_rating) / 3;
    }
}
