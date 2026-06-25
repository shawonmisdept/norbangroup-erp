<?php

namespace App\Models\Hrm;

use App\Models\Factory;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BonusRun extends Model
{
    protected $table = 'hrm_bonus_runs';

    protected $fillable = [
        'factory_id', 'bonus_type', 'year', 'bonus_date',
        'status', 'calculated_at', 'calculated_by',
    ];

    protected $casts = [
        'bonus_date'    => 'date',
        'calculated_at' => 'datetime',
    ];

    public const BONUS_TYPES = [
        'eid_ul_fitr'  => 'Eid-ul-Fitr',
        'eid_ul_adha'  => 'Eid-ul-Adha',
        'festival'     => 'Festival Bonus',
    ];

    public const STATUSES = [
        'draft'       => 'Draft',
        'calculated'  => 'Calculated',
        'approved'    => 'Approved',
    ];

    public function factory(): BelongsTo
    {
        return $this->belongsTo(Factory::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(BonusItem::class);
    }

    public function calculator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'calculated_by');
    }

    public function bonusTypeLabel(): string
    {
        return self::BONUS_TYPES[$this->bonus_type] ?? ucfirst(str_replace('_', ' ', $this->bonus_type));
    }
}
