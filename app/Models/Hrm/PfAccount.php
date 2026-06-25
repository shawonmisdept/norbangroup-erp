<?php

namespace App\Models\Hrm;

use App\Models\Factory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PfAccount extends Model
{
    protected $table = 'hrm_pf_accounts';

    protected $fillable = [
        'factory_id', 'employee_id', 'employee_rate_pct', 'employer_rate_pct',
        'balance', 'is_active', 'opened_at',
    ];

    protected $casts = [
        'employee_rate_pct' => 'decimal:2',
        'employer_rate_pct' => 'decimal:2',
        'balance'           => 'decimal:2',
        'is_active'         => 'boolean',
        'opened_at'         => 'date',
    ];

    public function factory(): BelongsTo
    {
        return $this->belongsTo(Factory::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function contributions(): HasMany
    {
        return $this->hasMany(PfContribution::class);
    }
}
