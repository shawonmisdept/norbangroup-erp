<?php

namespace App\Models\Hrm;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TaxSlab extends Model
{
    protected $table = 'hrm_tax_slabs';

    protected $fillable = [
        'tax_year_id', 'min_income', 'max_income', 'rate_percent', 'sort_order',
    ];

    protected $casts = [
        'min_income'   => 'decimal:2',
        'max_income'   => 'decimal:2',
        'rate_percent' => 'decimal:2',
    ];

    public function taxYear(): BelongsTo
    {
        return $this->belongsTo(TaxYear::class);
    }
}
