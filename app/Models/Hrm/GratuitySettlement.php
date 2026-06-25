<?php

namespace App\Models\Hrm;

use App\Models\Factory;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GratuitySettlement extends Model
{
    protected $table = 'hrm_gratuity_settlements';

    protected $fillable = [
        'factory_id', 'employee_id', 'separation_date',
        'years_of_service', 'last_basic_salary', 'gratuity_amount',
        'status', 'paid_at', 'calculated_by', 'notes',
    ];

    protected $casts = [
        'separation_date'   => 'date',
        'years_of_service'  => 'decimal:2',
        'last_basic_salary' => 'decimal:2',
        'gratuity_amount'   => 'decimal:2',
        'paid_at'           => 'datetime',
    ];

    public const STATUSES = [
        'calculated' => 'Calculated',
        'paid'       => 'Paid',
    ];

    public function factory(): BelongsTo
    {
        return $this->belongsTo(Factory::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function calculator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'calculated_by');
    }
}
