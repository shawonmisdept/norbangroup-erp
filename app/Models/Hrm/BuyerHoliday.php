<?php

namespace App\Models\Hrm;

use App\Models\Buyer;
use App\Models\Factory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BuyerHoliday extends Model
{
    protected $table = 'hrm_buyer_holidays';

    protected $fillable = [
        'factory_id', 'buyer_id', 'name', 'date', 'description', 'is_active',
    ];

    protected $casts = [
        'date'      => 'date',
        'is_active' => 'boolean',
    ];

    public function factory(): BelongsTo
    {
        return $this->belongsTo(Factory::class);
    }

    public function buyer(): BelongsTo
    {
        return $this->belongsTo(Buyer::class);
    }
}
