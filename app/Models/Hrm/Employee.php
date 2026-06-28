<?php

namespace App\Models\Hrm;

use App\Models\Department;
use App\Models\Designation;
use App\Models\Factory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class Employee extends Model
{
    use SoftDeletes;

    public const SEPARATED_STATUSES = ['terminated', 'resigned'];

    public const STATUSES = [
        'active'      => 'Active',
        'probation'   => 'Probation',
        'suspended'   => 'Suspended',
        'terminated'  => 'Terminated',
        'resigned'    => 'Resigned',
    ];

    protected $table = 'hrm_employees';

    protected $fillable = [
        'employee_code', 'factory_id', 'department_id', 'designation_id',
        'worker_category_id', 'employment_type_id',
        'building_id', 'floor_id', 'line_id', 'shift_id', 'reporting_to_id',
        'name', 'name_bangla', 'gender', 'date_of_birth', 'blood_group',
        'nid_number', 'nid_document', 'birth_certificate_no', 'birth_certificate_document', 'phone', 'email',
        'present_address', 'permanent_address',
        'emergency_contact_name', 'emergency_contact_phone', 'emergency_contact_relation',
        'nominee_name', 'nominee_relation', 'nominee_nid', 'nominee_nid_document', 'nominee_photo',
        'biometric_user_id', 'photo',
        'joining_date', 'confirmation_date', 'probation_passed_at', 'probation_end_date', 'contract_end_date',
        'separation_date', 'last_working_day', 'status', 'late_acceptance_enabled',
        'weekend_days', 'weekend_ot_allowed', 'half_day_pay_ratio', 'notes',
    ];

    protected $casts = [
        'date_of_birth'      => 'date',
        'joining_date'       => 'date',
        'confirmation_date'  => 'date',
        'probation_end_date' => 'date',
        'contract_end_date'  => 'date',
        'separation_date'    => 'date',
        'last_working_day'   => 'date',
        'late_acceptance_enabled' => 'boolean',
        'weekend_days'            => 'array',
        'weekend_ot_allowed'      => 'boolean',
        'half_day_pay_ratio'      => 'decimal:2',
    ];

    protected static function booted(): void
    {
        static::deleting(function (Employee $employee) {
            foreach (['photo', 'nid_document', 'birth_certificate_document', 'nominee_nid_document', 'nominee_photo'] as $field) {
                if ($employee->{$field}) {
                    Storage::disk('public')->delete($employee->{$field});
                }
            }
        });
    }

    public function factory(): BelongsTo
    {
        return $this->belongsTo(Factory::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function designation(): BelongsTo
    {
        return $this->belongsTo(Designation::class);
    }

    public function workerCategory(): BelongsTo
    {
        return $this->belongsTo(WorkerCategory::class);
    }

    public function employmentType(): BelongsTo
    {
        return $this->belongsTo(EmploymentType::class);
    }

    public function building(): BelongsTo
    {
        return $this->belongsTo(Building::class);
    }

    public function floor(): BelongsTo
    {
        return $this->belongsTo(Floor::class);
    }

    public function line(): BelongsTo
    {
        return $this->belongsTo(Line::class);
    }

    public function shift(): BelongsTo
    {
        return $this->belongsTo(Shift::class);
    }

    public function reportingTo(): BelongsTo
    {
        return $this->belongsTo(self::class, 'reporting_to_id');
    }

    public function reportees(): HasMany
    {
        return $this->hasMany(self::class, 'reporting_to_id');
    }

    public function portalUser(): HasOne
    {
        return $this->hasOne(EmployeePortalUser::class);
    }

    public function educationHistories(): HasMany
    {
        return $this->hasMany(EmployeeEducationHistory::class)->orderBy('sort_order');
    }

    public function employmentHistories(): HasMany
    {
        return $this->hasMany(EmployeeEmploymentHistory::class)->orderBy('sort_order');
    }

    public function serviceHistories(): HasMany
    {
        return $this->hasMany(EmployeeServiceHistory::class)->latest();
    }

    public function leaveBalances(): HasMany
    {
        return $this->hasMany(LeaveBalance::class);
    }

    public function leaveApplications(): HasMany
    {
        return $this->hasMany(LeaveApplication::class)->latest('applied_at');
    }

    public function salaryStructure(): HasOne
    {
        return $this->hasOne(SalaryStructure::class);
    }

    public function gratuitySettlement(): HasOne
    {
        return $this->hasOne(GratuitySettlement::class)->latestOfMany();
    }

    public function finalSettlement(): HasOne
    {
        return $this->hasOne(FinalSettlement::class);
    }

    public function separations(): HasMany
    {
        return $this->hasMany(EmployeeSeparation::class)->latest('applied_at');
    }

    public function latestSeparation(): HasOne
    {
        return $this->hasOne(EmployeeSeparation::class)->latestOfMany('applied_at');
    }

    public function pendingSeparation(): HasOne
    {
        return $this->hasOne(EmployeeSeparation::class)
            ->where('status', 'pending')
            ->latestOfMany('applied_at');
    }

    public function promotions(): HasMany
    {
        return $this->hasMany(EmployeePromotion::class)->latest('id');
    }

    public function pendingPromotion(): HasOne
    {
        return $this->hasOne(EmployeePromotion::class)
            ->where('status', 'pending')
            ->latestOfMany('id');
    }

    public function issuedLetters(): HasMany
    {
        return $this->hasMany(IssuedLetter::class)->latest('issued_at');
    }

    public function disciplinaryRecords(): HasMany
    {
        return $this->hasMany(DisciplinaryRecord::class)->latest('incident_date');
    }

    public function performanceReviews(): HasMany
    {
        return $this->hasMany(PerformanceReview::class)->latest('id');
    }

    public function pendingPerformanceReview(): HasOne
    {
        return $this->hasOne(PerformanceReview::class)
            ->whereIn('status', ['draft', 'blocked', 'pending_rating', 'pending_hr'])
            ->latestOfMany('id');
    }

    public function hasPassedProbation(): bool
    {
        return $this->probation_passed_at !== null;
    }

    public function isReviewBlocked(): bool
    {
        return $this->status === 'suspended';
    }

    public function isSeparated(): bool
    {
        return in_array($this->status, self::SEPARATED_STATUSES, true);
    }

    public function canInitiateSeparation(): bool
    {
        return in_array($this->status, ['active', 'probation', 'suspended'], true);
    }

    public function pfAccount(): HasOne
    {
        return $this->hasOne(PfAccount::class);
    }

    public function loanAccounts(): HasMany
    {
        return $this->hasMany(LoanAccount::class);
    }

    public function taxLedgers(): HasMany
    {
        return $this->hasMany(EmployeeTaxLedger::class);
    }

    public function payrollItems(): HasMany
    {
        return $this->hasMany(PayrollItem::class)->latest('id');
    }

    public function hasPortalAccess(): bool
    {
        return $this->portalUser?->canLogin() ?? false;
    }

    public function statusLabel(): string
    {
        return static::STATUSES[$this->status] ?? ucfirst($this->status);
    }

    public function photoUrl(): ?string
    {
        return $this->fileUrl($this->photo);
    }

    public function documentUrl(string $field): ?string
    {
        return $this->fileUrl($this->{$field} ?? null);
    }

    private function fileUrl(?string $path): ?string
    {
        return $path
            ? Storage::disk('public')->url($path)
            : null;
    }

    public function initials(): string
    {
        $parts = preg_split('/\s+/', trim($this->name));

        return strtoupper(collect($parts)->take(2)->map(fn ($p) => mb_substr($p, 0, 1))->implode(''));
    }
}
