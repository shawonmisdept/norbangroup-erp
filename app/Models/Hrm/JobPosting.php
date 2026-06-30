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
        'draft'            => 'Draft',
        'pending_approval' => 'Pending Approval',
        'open'             => 'Open',
        'closed'           => 'Closed',
    ];

    protected $table = 'hrm_job_postings';

    protected $fillable = [
        'factory_id', 'department_id', 'designation_id', 'worker_category_id',
        'shift_type', 'min_age', 'max_age', 'required_gender', 'rehire_eligible',
        'title', 'title_bn', 'description', 'description_bn',
        'requirements', 'skills_expertise', 'responsibilities',
        'employment_status', 'salary_text', 'salary_negotiable', 'benefits', 'meta_description',
        'slots', 'openings_filled', 'status', 'is_internal', 'page_views',
        'published_at', 'closes_at', 'approved_at', 'approved_by', 'created_by', 'template_key',
    ];

    protected $casts = [
        'published_at'      => 'datetime',
        'closes_at'         => 'datetime',
        'approved_at'       => 'datetime',
        'slots'             => 'integer',
        'openings_filled'   => 'integer',
        'page_views'        => 'integer',
        'min_age'           => 'integer',
        'max_age'           => 'integer',
        'salary_negotiable' => 'boolean',
        'rehire_eligible'   => 'boolean',
        'is_internal'       => 'boolean',
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

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function applications(): HasMany
    {
        return $this->hasMany(RecruitmentApplication::class, 'job_posting_id');
    }

    public function logs(): HasMany
    {
        return $this->hasMany(JobPostingLog::class, 'job_posting_id')->latest();
    }

    public function statusLabel(): string
    {
        return static::STATUSES[$this->status] ?? ucfirst($this->status);
    }

    public function shiftLabel(): ?string
    {
        if (! $this->shift_type) {
            return null;
        }

        return config('hrm.job_posting_shift_types.' . $this->shift_type, ucfirst($this->shift_type));
    }

    public function requiredGenderLabel(): ?string
    {
        if (! $this->required_gender) {
            return null;
        }

        return config('hrm.recruitment_posting_genders.' . $this->required_gender);
    }

    public function ageRequirementLabel(): ?string
    {
        if ($this->min_age && $this->max_age) {
            return "{$this->min_age} – {$this->max_age} years";
        }

        if ($this->min_age) {
            return "Minimum {$this->min_age} years";
        }

        if ($this->max_age) {
            return "Maximum {$this->max_age} years";
        }

        return null;
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

    public function isPubliclyOpen(): bool
    {
        return $this->isOpen() && ! $this->is_internal;
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
            || $this->employment_status
            || $this->benefits
            || $this->description;
    }

    public function listingExcerpt(int $limit = 140): ?string
    {
        foreach (['requirements', 'responsibilities', 'skills_expertise', 'employment_status', 'description', 'description_bn'] as $field) {
            $text = trim(strip_tags((string) $this->{$field}));

            if ($text !== '') {
                return Str::limit($text, $limit);
            }
        }

        return null;
    }

    public function publicShareUrl(): string
    {
        return route('careers.show', $this);
    }

    public function scopeOpen($query)
    {
        return $query->where('status', 'open')
            ->where(function ($q) {
                $q->whereNull('closes_at')->orWhere('closes_at', '>', now());
            })
            ->whereColumn('openings_filled', '<', 'slots');
    }

    public function scopePublicOpen($query)
    {
        return $query->open()->where('is_internal', false);
    }

    public function refreshAvailability(): void
    {
        if ($this->status === 'open' && $this->openings_filled >= $this->slots) {
            $this->update(['status' => 'closed']);
        }
    }
}
