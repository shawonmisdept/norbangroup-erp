<?php

namespace App\Models\Hrm;

use App\Models\Concerns\HasMasterCode;
use App\Models\Factory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Holiday extends Model
{
    use HasMasterCode;

    protected $table = 'hrm_holidays';

    protected $fillable = [
        'factory_id', 'name', 'date', 'is_optional', 'description', 'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_optional' => 'boolean',
        'date' => 'date',
    ];

    public static function codePrefix(): string
    {
        return 'HOL';
    }

    public function factory(): BelongsTo
    {
        return $this->belongsTo(Factory::class);
    }
}
