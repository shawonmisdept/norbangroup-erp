<?php

namespace App\Models\Hrm;

use App\Models\Concerns\HasMasterCode;
use App\Models\Factory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Line extends Model
{
    use HasMasterCode;

    protected $table = 'hrm_lines';

    protected $fillable = ['factory_id', 'floor_id', 'name', 'description', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];

    public static function codePrefix(): string
    {
        return 'LIN';
    }

    public function factory(): BelongsTo
    {
        return $this->belongsTo(Factory::class);
    }

    public function floor(): BelongsTo
    {
        return $this->belongsTo(Floor::class, 'floor_id');
    }
}
