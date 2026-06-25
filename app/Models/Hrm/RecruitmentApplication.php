<?php

namespace App\Models\Hrm;

use App\Models\Factory;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RecruitmentApplication extends Model
{
    protected $table = 'hrm_recruitment_applications';

    protected $fillable = [
        'application_no', 'job_posting_id', 'factory_id', 'source', 'status',
        'name', 'phone', 'email', 'gender', 'date_of_birth', 'nid_number',
        'present_address', 'permanent_address', 'photo_path', 'nid_document_path',
        'education_history', 'employment_history', 'expected_salary', 'referral_source',
        'notes', 'rejection_reason', 'converted_employee_id', 'reviewed_by',
        'reviewed_at', 'applied_at', 'phone_verified_at',
    ];

    protected $casts = [
        'date_of_birth'       => 'date',
        'education_history'   => 'array',
        'employment_history'  => 'array',
        'expected_salary'     => 'decimal:2',
        'applied_at'          => 'datetime',
        'reviewed_at'         => 'datetime',
        'phone_verified_at'   => 'datetime',
    ];

    public function jobPosting(): BelongsTo
    {
        return $this->belongsTo(JobPosting::class, 'job_posting_id');
    }

    public function factory(): BelongsTo
    {
        return $this->belongsTo(Factory::class);
    }

    public function convertedEmployee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'converted_employee_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function logs(): HasMany
    {
        return $this->hasMany(RecruitmentApplicationLog::class, 'application_id')->latest();
    }

    public function interviews(): HasMany
    {
        return $this->hasMany(RecruitmentInterview::class, 'application_id')->orderBy('scheduled_at');
    }

    public function offerLetters(): HasMany
    {
        return $this->hasMany(RecruitmentOfferLetter::class, 'application_id')->latest('issued_at');
    }

    public function upcomingInterview(): ?RecruitmentInterview
    {
        return $this->interviews()
            ->where('result', 'pending')
            ->where('scheduled_at', '>=', now())
            ->first();
    }

    public function statusLabel(): string
    {
        return config("hrm.recruitment_statuses.{$this->status}", ucfirst($this->status));
    }

    public function sourceLabel(): string
    {
        return config("hrm.recruitment_sources.{$this->source}", ucfirst(str_replace('_', ' ', $this->source)));
    }

    public function isTerminal(): bool
    {
        return in_array($this->status, ['hired', 'rejected', 'withdrawn'], true);
    }

    public function canConvert(): bool
    {
        return in_array($this->status, ['selected', 'offered'], true)
            && ! $this->converted_employee_id;
    }

    public function photoUrl(): ?string
    {
        return $this->photo_path ? asset('storage/' . $this->photo_path) : null;
    }

    public function nidDocumentUrl(): ?string
    {
        return $this->nid_document_path ? asset('storage/' . $this->nid_document_path) : null;
    }
}
