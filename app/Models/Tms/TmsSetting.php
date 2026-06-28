<?php

namespace App\Models\Tms;

use App\Models\Factory;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TmsSetting extends Model
{
    protected $table = 'tms_settings';

    protected $fillable = [
        'factory_id', 'office_start', 'office_end', 'ot_basis', 'updated_by',
    ];

    protected function casts(): array
    {
        return [];
    }

    public function factory(): BelongsTo
    {
        return $this->belongsTo(Factory::class);
    }

    public function updatedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
