<?php

namespace App\Models\Hrm;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PerformanceScore extends Model
{
    protected $table = 'hrm_performance_scores';

    protected $fillable = [
        'review_id', 'criterion_id', 'code', 'label', 'criterion_type', 'weight',
        'score', 'is_auto', 'auto_source', 'notes',
    ];

    protected $casts = [
        'weight'      => 'decimal:2',
        'score'       => 'decimal:2',
        'is_auto'     => 'boolean',
        'auto_source' => 'array',
    ];

    public function review(): BelongsTo
    {
        return $this->belongsTo(PerformanceReview::class, 'review_id');
    }

    public function criterion(): BelongsTo
    {
        return $this->belongsTo(PerformanceTemplateCriterion::class, 'criterion_id');
    }

    public function weightedContribution(): float
    {
        if ($this->score === null) {
            return 0.0;
        }

        return round(((float) $this->score * (float) $this->weight) / 100, 2);
    }
}
