<?php

namespace App\Models\Hrm;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RecruitmentInterview extends Model
{
    public const TYPES = [
        'in_person' => 'In Person',
        'phone'     => 'Phone',
        'video'     => 'Video Call',
    ];

    public const RESULTS = [
        'pending'   => 'Pending',
        'passed'    => 'Passed',
        'failed'    => 'Failed',
        'cancelled' => 'Cancelled',
        'no_show'   => 'No Show',
    ];

    protected $table = 'hrm_recruitment_interviews';

    protected $fillable = [
        'application_id', 'scheduled_at', 'location', 'interview_type',
        'result', 'score', 'panel_notes', 'scheduled_by', 'completed_at', 'reminder_sent_at',
    ];

    protected $casts = [
        'scheduled_at'     => 'datetime',
        'completed_at'     => 'datetime',
        'reminder_sent_at' => 'datetime',
    ];

    public function application(): BelongsTo
    {
        return $this->belongsTo(RecruitmentApplication::class, 'application_id');
    }

    public function scheduler(): BelongsTo
    {
        return $this->belongsTo(User::class, 'scheduled_by');
    }

    public function typeLabel(): string
    {
        return static::TYPES[$this->interview_type] ?? ucfirst($this->interview_type);
    }

    public function resultLabel(): string
    {
        return static::RESULTS[$this->result] ?? ucfirst($this->result);
    }

    public function isUpcoming(): bool
    {
        return $this->result === 'pending' && $this->scheduled_at->isFuture();
    }
}
