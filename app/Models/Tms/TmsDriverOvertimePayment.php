<?php

namespace App\Models\Tms;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TmsDriverOvertimePayment extends Model
{
    protected $table = 'tms_driver_overtime_payments';

    protected $fillable = [
        'trip_log_id', 'driver_id', 'rental_driver_id', 'amount', 'payment_breakdown',
        'payment_status', 'paid_at', 'paid_by',
    ];

    protected function casts(): array
    {
        return [
            'amount'             => 'decimal:2',
            'payment_breakdown'  => 'array',
            'paid_at'            => 'datetime',
        ];
    }

    public function tripLog(): BelongsTo
    {
        return $this->belongsTo(TmsTripLog::class, 'trip_log_id');
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(TmsDriver::class, 'driver_id');
    }

    public function rentalDriver(): BelongsTo
    {
        return $this->belongsTo(TmsRentalDriver::class, 'rental_driver_id');
    }

    public function paidByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'paid_by');
    }
}
