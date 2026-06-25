<?php

namespace App\Models\Hrm;

use App\Models\Department;
use App\Models\Designation;
use App\Models\Factory;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class JobPosting extends Model
{
    public const STATUSES = [
        'draft'  => 'Draft',
        'open'   => 'Open',
        'closed' => 'Closed',
    ];

    protected $table = 'hrm_job_postings';

    protected $fillable = [
        'factory_id', 'department_id', 'designation_id', 'worker_category_id',
        'title', 'description', 'requirements', 'skills_expertise', 'responsibilities',
        'employment_status', 'salary_text', 'salary_negotiable', 'benefits',
        'slots', 'openings_filled', 'status', 'published_at', 'closes_at', 'created_by',
    ];

    protected $casts = [
        'published_at'      => 'datetime',
        'closes_at'         => 'datetime',
        'slots'             => 'integer',
        'openings_filled'   => 'integer',
        'salary_negotiable' => 'boolean',
    ];

    public function factory(): BelongsTo
    {
        return $this->belongsTo(Factory::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function designation(): BelongsTo
    {
        return $this->belongsTo(Designation::class);
    }

    public function workerCategory(): BelongsTo
    {
        return $this->belongsTo(WorkerCategory::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function applications(): HasMany
    {
        return $this->hasMany(RecruitmentApplication::class, 'job_posting_id');
    }

    public function statusLabel(): string
    {
        return static::STATUSES[$this->status] ?? ucfirst($this->status);
    }

    public function isOpen(): bool
    {
        if ($this->status !== 'open') {
            return false;
        }

        if ($this->closes_at && $this->closes_at->isPast()) {
            return false;
        }

        return $this->openings_filled < $this->slots;
    }

    public function remainingSlots(): int
    {
        return max(0, $this->slots - $this->openings_filled);
    }

    public function salaryDisplay(): ?string
    {
        $text = trim(strip_tags((string) $this->salary_text));

        if ($text !== '') {
            return $this->salary_negotiable ? "{$text} (Negotiable)" : $text;
        }

        return $this->salary_negotiable ? 'Negotiable' : null;
    }

    public function hasDetailSections(): bool
    {
        return $this->requirements
            || $this->responsibilities
            || $this->skills_expertise
            || $this->employment_status;
    }

    public function listingExcerpt(int $limit = 140): ?string
    {
        foreach (['requirements', 'responsibilities', 'skills_expertise', 'employment_status', 'description'] as $field) {
            $text = trim(strip_tags((string) $this->{$field}));

            if ($text !== '') {
                return Str::limit($text, $limit);
            }
        }

        return null;
    }

    public function scopeOpen($query)
    {
        return $query->where('status', 'open')
            ->where(function ($q) {
                $q->whereNull('closes_at')->orWhere('closes_at', '>', now());
            })
            ->whereColumn('openings_filled', '<', 'slots');
    }

    public function refreshAvailability(): void
    {
        if ($this->status === 'open' && $this->openings_filled >= $this->slots) {
            $this->update(['status' => 'closed']);
        }
    }
}
