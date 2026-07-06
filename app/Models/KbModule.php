<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class KbModule extends Model
{
    protected $fillable = [
        'code',
        'label_en',
        'label_bn',
        'view_permission',
        'submodules_config',
        'sort_order',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active'  => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function articles(): HasMany
    {
        return $this->hasMany(KbArticle::class);
    }

    public function publishedArticles(): HasMany
    {
        return $this->articles()->where('is_published', true);
    }

    /** @return array<string, array<string, mixed>> */
    public function submoduleDefinitions(): array
    {
        if (! $this->submodules_config) {
            return [];
        }

        return config($this->submodules_config, []);
    }
}
