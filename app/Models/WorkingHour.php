<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WorkingHour extends Model
{
    use HasFactory;

    // Explicitly specify the table name (optional, but safe)
    protected $table = 'working_hours';

    protected $fillable = [
        'student_id',
        'assignment_id',
        'date',
        'hours'
    ];

    protected $casts = [
        'date' => 'date',
        'hours' => 'decimal:2'
    ];

    public function student()
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function assignment()
    {
        return $this->belongsTo(Assignment::class);
    }
}