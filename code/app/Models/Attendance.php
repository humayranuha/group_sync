<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Attendance extends Model
{
    use HasFactory;

    // Explicitly specify the table name
    protected $table = 'attendance';

    protected $fillable = [
        'student_id',
        'course_id',
        'date',
        'present'
    ];

    protected $casts = [
        'date' => 'date',
        'present' => 'boolean'
    ];

    public function student()
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function course()
    {
        return $this->belongsTo(Course::class);
    }
}