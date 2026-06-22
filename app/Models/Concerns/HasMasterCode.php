<?php

namespace App\Models\Concerns;

use Illuminate\Support\Str;

trait HasMasterCode
{
    protected static function bootHasMasterCode(): void
    {
        static::creating(function ($model) {
            if ($model->code) {
                return;
            }

            $prefix = $model->codePrefix();

            do {
                $model->code = $prefix . '-' . strtoupper(Str::random(6));
            } while ($model->newQuery()->where('code', $model->code)->exists());
        });
    }

    abstract public static function codePrefix(): string;
}
