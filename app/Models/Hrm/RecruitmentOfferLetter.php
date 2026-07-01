<?php

namespace App\Models\Hrm;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RecruitmentOfferLetter extends Model
{
    protected $table = 'hrm_recruitment_offer_letters';

    protected $fillable = [
        'application_id', 'reference_no', 'content',
        'offered_salary', 'joining_date', 'notes', 'issued_by', 'issued_at',
        'response', 'responded_at', 'decline_reason',
    ];

    protected $casts = [
        'offered_salary' => 'decimal:2',
        'joining_date'   => 'date',
        'issued_at'      => 'datetime',
        'responded_at'   => 'datetime',
    ];

    public function application(): BelongsTo
    {
        return $this->belongsTo(RecruitmentApplication::class, 'application_id');
    }

    public function issuer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'issued_by');
    }

    public function isPendingResponse(): bool
    {
        return $this->response === null;
    }

    public function responseLabel(): ?string
    {
        return match ($this->response) {
            'accepted' => 'Accepted',
            'declined' => 'Declined',
            default    => null,
        };
    }
}
