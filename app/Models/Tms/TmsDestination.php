<?php

namespace App\Models\Tms;

use App\Models\Factory;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;

class TmsDestination extends Model
{
    use SoftDeletes;

    protected $table = 'tms_destinations';

    protected $fillable = [
        'factory_id', 'name', 'address', 'is_active', 'created_by', 'updated_by',
    ];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    /** Shared master list — destinations apply to every unit. */
    public function scopeShared(Builder $query): Builder
    {
        return $query->orderBy('name');
    }

    /** @return Collection<int, self> */
    public static function activeShared(): Collection
    {
        return static::query()
            ->shared()
            ->where('is_active', true)
            ->get();
    }

    /**
     * factory_id remains for legacy FK / ownership metadata only;
     * destinations are not scoped by unit when listing or validating.
     */
    public static function anchorFactoryId(): ?int
    {
        return Factory::query()
            ->where('is_active', true)
            ->orderBy('id')
            ->value('id');
    }

    public function factory(): BelongsTo
    {
        return $this->belongsTo(Factory::class);
    }

    public function createdByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
