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
}
