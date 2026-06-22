<?php

namespace App\Models;

use App\Models\Concerns\HasMasterCode;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Designation extends Model
{
    use HasMasterCode;

    protected $fillable = ['name', 'department_id', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];

    public static function codePrefix(): string
    {
        return 'DES';
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }
}
