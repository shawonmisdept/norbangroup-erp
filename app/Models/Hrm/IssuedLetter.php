<?php

namespace App\Models\Hrm;

use App\Models\Factory;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IssuedLetter extends Model
{
    protected $table = 'hrm_issued_letters';

    protected $fillable = [
        'factory_id', 'employee_id', 'template_id', 'letter_type',
        'reference_no', 'content', 'notes', 'issued_at', 'issued_by',
    ];

    protected $casts = [
        'issued_at' => 'datetime',
    ];

    public function factory(): BelongsTo
    {
        return $this->belongsTo(Factory::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(HrLetterTemplate::class, 'template_id');
    }

    public function issuer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'issued_by');
    }

    public function typeLabel(): string
    {
        return config("hrm.letter_types.{$this->letter_type}", ucfirst(str_replace('_', ' ', $this->letter_type)));
    }
}
