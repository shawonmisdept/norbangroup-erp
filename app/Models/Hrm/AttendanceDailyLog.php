<?php

namespace App\Models\Hrm;

use App\Models\Factory;
use App\Services\Hrm\EmployeeScheduleService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class AttendanceDailyLog extends Model
{
    public const STATUSES = [
        'present'  => 'Present',
        'late'     => 'Late',
        'absent'   => 'Absent',
        'half_day' => 'Half Day',
        'holiday'  => 'Holiday',
        'leave'    => 'Leave',
        'off_day'  => 'Off Day',
    ];

    protected $table = 'hrm_attendance_daily_logs';

    protected $fillable = [
        'factory_id', 'employee_id', 'attendance_period_id', 'shift_id',
        'attendance_date', 'check_in', 'check_out',
        'expected_in', 'expected_out',
        'work_minutes', 'late_minutes', 'early_leave_minutes', 'break_minutes',
        'punch_count', 'status', 'is_late_forgiven', 'half_day_type', 'half_day_pay_ratio',
        'is_manual_half_day', 'half_day_notes',
        'late_streak_day', 'late_deduction_amount',
        'late_acceptance_application_id', 'is_manual', 'notes',
    ];

    protected $casts = [
        'attendance_date' => 'date',
        'check_in'        => 'datetime',
        'check_out'       => 'datetime',
        'is_manual'       => 'boolean',
        'is_late_forgiven'=> 'boolean',
        'is_manual_half_day' => 'boolean',
        'half_day_pay_ratio' => 'decimal:2',
        'late_deduction_amount' => 'decimal:2',
    ];

    public function factory(): BelongsTo
    {
        return $this->belongsTo(Factory::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function period(): BelongsTo
    {
        return $this->belongsTo(AttendancePeriod::class, 'attendance_period_id');
    }

    public function shift(): BelongsTo
    {
        return $this->belongsTo(Shift::class);
    }

    public function lateAcceptanceApplication(): BelongsTo
    {
        return $this->belongsTo(LateAcceptanceApplication::class);
    }

    public function mobileCheckInPhotoPunch(): HasOne
    {
        return $this->hasOne(AttendanceRawPunch::class, 'employee_id', 'employee_id')
            ->where('punch_type', 'in')
            ->whereNotNull('photo_path')
            ->whereIn('source', ['mobile_gps', 'qr_scan'])
            ->latest('punched_at');
    }

    public function halfDayTypeLabel(): string
    {
        if ($this->status !== 'half_day') {
            return $this->statusLabel();
        }

        $type = $this->half_day_type ?? 'auto';

        if ($type === 'auto') {
            return 'Half Day (Auto)';
        }

        $typeLabel = EmployeeScheduleService::HALF_DAY_TYPES[$type]
            ?? ucfirst(str_replace('_', ' ', $type));

        return 'Half Day — ' . $typeLabel;
    }

    public function displayStatusLabel(): string
    {
        if ($this->status === 'half_day') {
            $label = $this->halfDayTypeLabel();

            if ($this->late_minutes > 0) {
                return $label . ' · Late';
            }

            return $label;
        }

        return $this->lateStatusLabel();
    }

    public function lateStatusLabel(): string
    {
        if ($this->status !== 'late') {
            return $this->statusLabel();
        }

        if ($this->is_late_forgiven || $this->employee?->late_acceptance_enabled) {
            return 'Late — Accepted';
        }

        if ($this->lateAcceptanceApplication?->isPending()) {
            return 'Late — Pending';
        }

        return 'Late';
    }

    public function statusLabel(): string
    {
        return static::STATUSES[$this->status] ?? ucfirst($this->status);
    }

    public function workHoursFormatted(): string
    {
        $minutes = (int) $this->work_minutes;

        if ($minutes <= 0 && $this->check_in && $this->check_out && $this->check_out->greaterThan($this->check_in)) {
            $minutes = $this->check_in->diffInMinutes($this->check_out);
        }

        if ($minutes <= 0) {
            return '—';
        }

        $hours = intdiv($minutes, 60);
        $mins = $minutes % 60;

        return sprintf('%dh %02dm', $hours, $mins);
    }
}
