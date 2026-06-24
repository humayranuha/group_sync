<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'type',
        'title',
        'message',
        'is_read',
        'read_at',
        'link',
        'data',
        'sent_via_email',
        'email_sent_at'
    ];

    protected $casts = [
        'is_read' => 'boolean',
        'read_at' => 'datetime',
        'email_sent_at' => 'datetime',
        'data' => 'array'
    ];

    /**
     * ============================================
     * RELATIONSHIPS
     * ============================================
     */

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * ============================================
     * HELPER METHODS
     * ============================================
     */

    /**
     * Mark notification as read
     */
    public function markAsRead(): void
    {
        $this->update([
            'is_read' => true,
            'read_at' => now()
        ]);
    }

    /**
     * Mark notification as unread
     */
    public function markAsUnread(): void
    {
        $this->update([
            'is_read' => false,
            'read_at' => null
        ]);
    }

    /**
     * Mark email as sent
     */
    public function markEmailAsSent(): void
    {
        $this->update([
            'sent_via_email' => true,
            'email_sent_at' => now()
        ]);
    }

    /**
     * Get notification type color
     */
    public function getTypeColorAttribute(): string
    {
        return match($this->type) {
            'info' => 'blue',
            'success' => 'green',
            'warning' => 'yellow',
            'danger', 'alert' => 'red',
            default => 'gray'
        };
    }

    /**
     * Get notification type icon
     */
    public function getTypeIconAttribute(): string
    {
        return match($this->type) {
            'info' => 'info-circle',
            'success' => 'check-circle',
            'warning' => 'exclamation-triangle',
            'danger', 'alert' => 'exclamation-circle',
            'score_update' => 'chart-line',
            'peer_review' => 'star',
            'github_update' => 'github',
            default => 'bell'
        };
    }

    /**
     * ============================================
     * SCOPES
     * ============================================
     */

    /**
     * Scope for unread notifications
     */
    public function scopeUnread($query)
    {
        return $query->where('is_read', false);
    }

    /**
     * Scope for read notifications
     */
    public function scopeRead($query)
    {
        return $query->where('is_read', true);
    }

    /**
     * Scope for email not sent
     */
    public function scopeEmailNotSent($query)
    {
        return $query->where(function($q) {
            $q->whereNull('sent_via_email')
              ->orWhere('sent_via_email', false);
        });
    }

    /**
     * Scope for a specific type
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }
}