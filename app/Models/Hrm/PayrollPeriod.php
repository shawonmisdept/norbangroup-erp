<?php

namespace App\Models\Hrm;

use App\Models\Factory;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PayrollPeriod extends Model
{
    public const STATUSES = [
        'draft'      => 'Draft',
        'calculated' => 'Calculated',
        'frozen'     => 'Frozen',
    ];

    protected $table = 'hrm_payroll_periods';

    protected $fillable = [
        'factory_id', 'year', 'month', 'start_date', 'end_date', 'status',
        'attendance_period_id', 'calculated_at', 'frozen_at', 'payslips_sent_at', 'calculated_by', 'frozen_by', 'notes',
    ];

    protected $casts = [
        'start_date'      => 'date',
        'end_date'        => 'date',
        'calculated_at'   => 'datetime',
        'frozen_at'       => 'datetime',
        'payslips_sent_at'=> 'datetime',
    ];

    public function factory(): BelongsTo
    {
        return $this->belongsTo(Factory::class);
    }

    public function attendancePeriod(): BelongsTo
    {
        return $this->belongsTo(AttendancePeriod::class);
    }

    public function calculatedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'calculated_by');
    }

    public function frozenByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'frozen_by');
    }

    public function runs(): HasMany
    {
        return $this->hasMany(PayrollRun::class)->latest('started_at');
    }

    public function items(): HasMany
    {
        return $this->hasMany(PayrollItem::class);
    }

    public function statusLabel(): string
    {
        return static::STATUSES[$this->status] ?? ucfirst($this->status);
    }

    public function isFrozen(): bool
    {
        return $this->status === 'frozen';
    }

    public function pendingCashDisbursementCount(): int
    {
        return $this->items()
            ->where('cash_pay_amount', '>', 0)
            ->whereNull('cash_disbursed_at')
            ->count();
    }

    public function canFreeze(): bool
    {
        return $this->status === 'calculated'
            && $this->items()->count() > 0
            && $this->pendingCashDisbursementCount() === 0;
    }

    public function periodLabel(): string
    {
        return Carbon::create($this->year, $this->month, 1)->format('F Y');
    }

    public static function getOrCreateForMonth(int $factoryId, int $year, int $month): self
    {
        $start = Carbon::create($year, $month, 1)->startOfMonth();
        $end = $start->copy()->endOfMonth();

        $attendancePeriod = AttendancePeriod::getOrCreateForMonth($factoryId, $year, $month);

        return static::firstOrCreate(
            ['factory_id' => $factoryId, 'year' => $year, 'month' => $month],
            [
                'start_date'           => $start->toDateString(),
                'end_date'             => $end->toDateString(),
                'status'               => 'draft',
                'attendance_period_id' => $attendancePeriod->id,
            ]
        );
    }
}
