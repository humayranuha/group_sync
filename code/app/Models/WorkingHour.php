<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WorkingHour extends Model
{
    use HasFactory;

    // Explicitly specify the table name
    protected $table = 'working_hours';

    protected $fillable = [
        'student_id',
        'assignment_id',
        'group_id',
        'date',
        'hours',
        'description',
        'is_approved',
        'approved_at',
        'approved_by'
    ];

    protected $casts = [
        'date' => 'date',
        'hours' => 'decimal:2',
        'is_approved' => 'boolean',
        'approved_at' => 'datetime',
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

    public function assignment()
    {
        return $this->belongsTo(Assignment::class);
    }

    public function group()
    {
        return $this->belongsTo(Group::class);
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * ============================================
     * HELPER METHODS
     * ============================================
     */

    /**
     * Check if working hours are approved
     */
    public function isApproved(): bool
    {
        return $this->is_approved;
    }

    /**
     * Approve working hours
     */
    public function approve($approverId): void
    {
        $this->update([
            'is_approved' => true,
            'approved_at' => now(),
            'approved_by' => $approverId
        ]);
    }

    /**
     * Reject working hours
     */
    public function reject(): void
    {
        $this->update([
            'is_approved' => false,
            'approved_at' => null,
            'approved_by' => null
        ]);
    }

    /**
     * Get total hours for a specific period
     */
    public static function getTotalHours($studentId, $startDate, $endDate)
    {
        return self::where('student_id', $studentId)
            ->whereBetween('date', [$startDate, $endDate])
            ->sum('hours');
    }

    /**
     * ============================================
     * SCOPES
     * ============================================
     */

    /**
     * Scope for approved working hours
     */
    public function scopeApproved($query)
    {
        return $query->where('is_approved', true);
    }

    /**
     * Scope for pending working hours
     */
    public function scopePending($query)
    {
        return $query->where('is_approved', false);
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

    /**
     * Scope for a specific date range
     */
    public function scopeDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('date', [$startDate, $endDate]);
    }

    /**
     * Scope for this week
     */
    public function scopeThisWeek($query)
    {
        return $query->whereBetween('date', [now()->startOfWeek(), now()->endOfWeek()]);
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