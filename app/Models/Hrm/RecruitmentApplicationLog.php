<?php

namespace App\Models\Hrm;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RecruitmentApplicationLog extends Model
{
    protected $table = 'hrm_recruitment_application_logs';

    protected $fillable = [
        'application_id', 'from_status', 'to_status', 'notes', 'user_id',
    ];

    public function application(): BelongsTo
    {
        return $this->belongsTo(RecruitmentApplication::class, 'application_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
