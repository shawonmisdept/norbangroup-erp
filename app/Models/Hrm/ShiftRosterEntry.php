<?php

namespace App\Models\Hrm;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShiftRosterEntry extends Model
{
    protected $table = 'hrm_shift_roster_entries';

    protected $fillable = [
        'roster_id', 'employee_id', 'roster_date', 'shift_id', 'line_id',
    ];

    protected $casts = [
        'roster_date' => 'date',
    ];

    public function roster(): BelongsTo
    {
        return $this->belongsTo(ShiftRoster::class, 'roster_id');
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function shift(): BelongsTo
    {
        return $this->belongsTo(Shift::class);
    }

    public function line(): BelongsTo
    {
        return $this->belongsTo(Line::class);
    }
}
