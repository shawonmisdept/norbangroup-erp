<?php

namespace App\Models\Hrm;

use App\Models\Factory;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PerformanceTemplate extends Model
{
    protected $table = 'hrm_performance_templates';

    protected $fillable = [
        'factory_id', 'name', 'cycle_types', 'is_default', 'is_active', 'created_by',
    ];

    protected $casts = [
        'cycle_types' => 'array',
        'is_default'  => 'boolean',
        'is_active'   => 'boolean',
    ];

    public function factory(): BelongsTo
    {
        return $this->belongsTo(Factory::class);
    }

    public function criteria(): HasMany
    {
        return $this->hasMany(PerformanceTemplateCriterion::class, 'template_id')->orderBy('sort_order');
    }

    public function createdByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function supportsCycleType(string $cycleType): bool
    {
        $types = $this->cycle_types;

        return $types === null || $types === [] || in_array($cycleType, $types, true);
    }
}
