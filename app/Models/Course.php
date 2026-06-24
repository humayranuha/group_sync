<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Course extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'title',
        'semester',
        'description',
        'teacher_id',
        'enrollment_code',
        'is_active'
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function teacher()
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function students()
    {
        return $this->belongsToMany(User::class, 'course_student', 'course_id', 'student_id')
                    ->withPivot('enrolled_at')
                    ->withTimestamps();
    }

    public function groups()
    {
        return $this->hasMany(Group::class);
    }

    public function assignments()
    {
        return $this->hasMany(Assignment::class);
    }
}
