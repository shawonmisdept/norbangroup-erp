<?php

namespace App\Models\Hrm;

use App\Models\Factory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class HrLetterTemplate extends Model
{
    protected $table = 'hrm_hr_letter_templates';

    protected $fillable = [
        'factory_id', 'code', 'name', 'letter_type', 'body', 'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function factory(): BelongsTo
    {
        return $this->belongsTo(Factory::class);
    }

    public function issuedLetters(): HasMany
    {
        return $this->hasMany(IssuedLetter::class, 'template_id');
    }

    public function typeLabel(): string
    {
        return config("hrm.letter_types.{$this->letter_type}", ucfirst(str_replace('_', ' ', $this->letter_type)));
    }
}
