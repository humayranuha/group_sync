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
        'calculated_at'
    ];

    protected $casts = [
        'calculated_at' => 'datetime',
    ];

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

    public function getBreakdownAttribute($value)
    {
        return json_decode($value, true);
    }

    public function setBreakdownAttribute($value)
    {
        $this->attributes['breakdown'] = is_array($value) ? json_encode($value) : $value;
    }
}
