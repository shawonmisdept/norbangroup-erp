<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KbArticle extends Model
{
    protected $fillable = [
        'kb_module_id',
        'submodule_key',
        'title_en',
        'title_bn',
        'summary_en',
        'summary_bn',
        'purpose_en',
        'purpose_bn',
        'audience_en',
        'audience_bn',
        'usage_rules_en',
        'usage_rules_bn',
        'body_en',
        'body_bn',
        'is_published',
        'updated_by_user_id',
    ];

    protected function casts(): array
    {
        return [
            'is_published' => 'boolean',
        ];
    }

    public function module(): BelongsTo
    {
        return $this->belongsTo(KbModule::class, 'kb_module_id');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by_user_id');
    }

    public function isOverview(): bool
    {
        return $this->submodule_key === null || $this->submodule_key === '';
    }

    public function routeKey(): string
    {
        return $this->isOverview() ? 'overview' : $this->submodule_key;
    }
}
