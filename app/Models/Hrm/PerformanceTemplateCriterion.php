<?php

namespace App\Models\Hrm;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PerformanceTemplateCriterion extends Model
{
    public const TYPES = [
        'auto'   => 'Automatic',
        'manual' => 'Manual',
    ];

    protected $table = 'hrm_performance_template_criteria';

    protected $fillable = [
        'template_id', 'code', 'label', 'criterion_type', 'weight', 'sort_order', 'config',
    ];

    protected $casts = [
        'weight' => 'decimal:2',
        'config' => 'array',
    ];

    public function template(): BelongsTo
    {
        return $this->belongsTo(PerformanceTemplate::class, 'template_id');
    }

    public function typeLabel(): string
    {
        return self::TYPES[$this->criterion_type] ?? ucfirst($this->criterion_type);
    }
}
