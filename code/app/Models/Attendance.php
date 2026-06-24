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
        'present',
        'time_in',
        'time_out',
        'status'
    ];

    protected $casts = [
        'date' => 'date',
        'present' => 'boolean',
        'time_in' => 'datetime',
        'time_out' => 'datetime',
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

    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    /**
     * ============================================
     * HELPER METHODS
     * ============================================
     */

    /**
     * Get attendance status
     */
    public function getStatusAttribute($value)
    {
        return $value ?? ($this->present ? 'present' : 'absent');
    }

    /**
     * Check if student was present
     */
    public function wasPresent(): bool
    {
        return $this->present;
    }

    /**
     * Check if student was absent
     */
    public function wasAbsent(): bool
    {
        return !$this->present;
    }

    /**
     * Get duration in hours
     */
    public function getDurationAttribute(): float
    {
        if ($this->time_in && $this->time_out) {
            return $this->time_in->diffInHours($this->time_out);
        }
        return 0;
    }

    /**
     * ============================================
     * SCOPES
     * ============================================
     */

    /**
     * Scope for present students
     */
    public function scopePresent($query)
    {
        return $query->where('present', true);
    }

    /**
     * Scope for absent students
     */
    public function scopeAbsent($query)
    {
        return $query->where('present', false);
    }

    /**
     * Scope for a specific date
     */
    public function scopeOnDate($query, $date)
    {
        return $query->whereDate('date', $date);
    }

    /**
     * Scope for a specific student
     */
    public function scopeForStudent($query, $studentId)
    {
        return $query->where('student_id', $studentId);
    }

    /**
     * Scope for a specific course
     */
    public function scopeForCourse($query, $courseId)
    {
        return $query->where('course_id', $courseId);
    }

    /**
     * Scope for this month
     */
    public function scopeThisMonth($query)
    {
        return $query->whereMonth('date', now()->month)
                     ->whereYear('date', now()->year);
    }
}