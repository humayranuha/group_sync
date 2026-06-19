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

    public function getWeightageAttribute($value)
    {
        return json_decode($value, true);
    }

    public function setWeightageAttribute($value)
    {
        $this->attributes['weightage'] = is_array($value) ? json_encode($value) : $value;
    }
}
