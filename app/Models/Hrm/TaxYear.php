<?php

namespace App\Models\Hrm;

use App\Models\Factory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TaxYear extends Model
{
    protected $table = 'hrm_tax_years';

    protected $fillable = [
        'factory_id', 'label', 'start_date', 'end_date', 'is_active',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date'   => 'date',
        'is_active'  => 'boolean',
    ];

    public function factory(): BelongsTo
    {
        return $this->belongsTo(Factory::class);
    }

    public function slabs(): HasMany
    {
        return $this->hasMany(TaxSlab::class, 'tax_year_id')->orderBy('sort_order');
    }
}
