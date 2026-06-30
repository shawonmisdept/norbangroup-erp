<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

#[Fillable(['name', 'email', 'password', 'role_id', 'factory_id', 'photo'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected static function booted(): void
    {
        static::creating(function (User $user) {
            if ($user->user_code) {
                return;
            }

            do {
                $user->user_code = 'USR-' . strtoupper(Str::random(6));
            } while (static::where('user_code', $user->user_code)->exists());
        });

        static::deleting(function (User $user) {
            if ($user->photo) {
                Storage::disk('public')->delete($user->photo);
            }
        });
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    public function factory(): BelongsTo
    {
        return $this->belongsTo(Factory::class);
    }

    /** True when the account is limited to a single factory / unit. */
    public function isUnitScoped(): bool
    {
        return $this->factory_id !== null && ! $this->hasCrossUnitFactoryAccess();
    }

    /** Group-level access: no unit assignment, or elevated admin permissions. */
    public function hasCrossUnitFactoryAccess(): bool
    {
        if ($this->factory_id === null) {
            return true;
        }

        foreach (config('permissions.cross_unit_factory_permissions', []) as $permission) {
            if ($this->hasPermission($permission)) {
                return true;
            }
        }

        return false;
    }

    public function canAccessFactory(?int $factoryId): bool
    {
        if (! $this->isUnitScoped()) {
            return true;
        }

        if ($factoryId === null) {
            return true;
        }

        return (int) $this->factory_id === (int) $factoryId;
    }

    public function scopedFactoryId(): ?int
    {
        return $this->isUnitScoped() ? (int) $this->factory_id : null;
    }

    /**
     * Resolve a factory filter for list/report screens.
     * Unit-scoped users always get their assigned factory.
     */
    public function resolveFactoryFilter(?int $requested = null): ?int
    {
        if ($this->isUnitScoped()) {
            return (int) $this->factory_id;
        }

        return $requested ?: null;
    }

    public function hasPermission(string $permission): bool
    {
        return $this->role?->hasPermission($permission) ?? false;
    }

    public function canViewMaster(string $module): bool
    {
        return $this->hasPermission("masters.{$module}.view");
    }

    public function canManageMaster(string $module): bool
    {
        return $this->hasPermission("masters.{$module}.manage");
    }

    public function hasAnyMasterViewPermission(): bool
    {
        if ($this->hasPermission('masters.view')) {
            return true;
        }

        foreach (array_keys(config('masters.modules', [])) as $module) {
            if ($this->hasPermission("masters.{$module}.view")) {
                return true;
            }
        }

        return false;
    }

    public function canViewHrmMaster(string $module): bool
    {
        return $this->hasPermission("hrm.{$module}.view");
    }

    public function canManageHrmMaster(string $module): bool
    {
        return $this->hasPermission("hrm.{$module}.manage");
    }

    public function hasAnyHrmMasterViewPermission(): bool
    {
        if ($this->hasPermission('hrm.masters.view')) {
            return true;
        }

        foreach (array_keys(config('hrm.modules', [])) as $module) {
            if ($this->hasPermission("hrm.{$module}.view")) {
                return true;
            }
        }

        return false;
    }

    public function hasAnyHrmViewPermission(): bool
    {
        return $this->hasAnyHrmMasterViewPermission()
            || $this->hasAnyEmployeeViewPermission()
            || $this->hasAnyRecruitmentViewPermission()
            || $this->hasAnyAttendanceViewPermission()
            || $this->hasAnyLeaveViewPermission()
            || $this->hasAnySalaryViewPermission()
            || $this->hasAnyComplianceViewPermission()
            || $this->hasAnyFinanceViewPermission()
            || $this->hasAnyRmgViewPermission()
            || $this->hasAnyPerformanceViewPermission();
    }

    public function canViewEmployeeSubmodule(string $key): bool
    {
        $sub = config("hrm.employee_submodules.{$key}");

        if (! $sub) {
            return false;
        }

        return $this->hasPermission($sub['permission'] ?? 'hrm.employees.view');
    }

    public function canManageEmployeeSubmodule(string $key): bool
    {
        $sub = config("hrm.employee_submodules.{$key}");

        if (! $sub) {
            return false;
        }

        return $this->hasPermission($sub['manage'] ?? 'hrm.employees.manage');
    }

    public function hasAnyEmployeeViewPermission(): bool
    {
        foreach (array_keys(config('hrm.employee_submodules', [])) as $key) {
            if ($this->canViewEmployeeSubmodule($key)) {
                return true;
            }
        }

        return false;
    }

    public function canViewRecruitmentSubmodule(string $key): bool
    {
        $sub = config("hrm.recruitment_submodules.{$key}");

        if (! $sub) {
            return false;
        }

        return $this->hasPermission($sub['permission'] ?? 'hrm.recruitment.applications.view');
    }

    public function canManageRecruitmentSubmodule(string $key): bool
    {
        $sub = config("hrm.recruitment_submodules.{$key}");

        if (! $sub) {
            return false;
        }

        return $this->hasPermission($sub['manage'] ?? 'hrm.recruitment.applications.manage');
    }

    public function canApproveRecruitmentPostings(): bool
    {
        return $this->hasPermission('hrm.recruitment.postings.approve');
    }

    public function hasAnyRecruitmentViewPermission(): bool
    {
        foreach (array_keys(config('hrm.recruitment_submodules', [])) as $key) {
            if ($this->canViewRecruitmentSubmodule($key)) {
                return true;
            }
        }

        return false;
    }

    public function canViewLeaveSubmodule(string $key): bool
    {
        if ($this->hasPermission('hrm.leave.view')) {
            return true;
        }

        $sub = config("hrm.leave_submodules.{$key}");

        if (! $sub) {
            return false;
        }

        return $this->hasPermission($sub['permission'] ?? 'hrm.leave.view');
    }

    public function canManageLeaveSubmodule(string $key): bool
    {
        if ($this->hasPermission('hrm.leave.manage')) {
            return true;
        }

        $sub = config("hrm.leave_submodules.{$key}");

        if (! $sub) {
            return false;
        }

        return $this->hasPermission($sub['manage'] ?? 'hrm.leave.manage');
    }

    public function hasAnyLeaveViewPermission(): bool
    {
        if ($this->hasPermission('hrm.leave.view')) {
            return true;
        }

        foreach (array_keys(config('hrm.leave_submodules', [])) as $key) {
            if ($this->canViewLeaveSubmodule($key)) {
                return true;
            }
        }

        return false;
    }

    public function canViewSalarySubmodule(string $key): bool
    {
        $sub = config("hrm.salary_submodules.{$key}");

        if (! $sub) {
            return false;
        }

        return $this->hasPermission($sub['permission'] ?? 'hrm.salary.view');
    }

    public function canManageSalarySubmodule(string $key): bool
    {
        $sub = config("hrm.salary_submodules.{$key}");

        if (! $sub) {
            return false;
        }

        return $this->hasPermission($sub['manage'] ?? 'hrm.salary.manage');
    }

    public function hasAnySalaryViewPermission(): bool
    {
        if ($this->hasPermission('hrm.salary.view') || $this->hasPermission('hrm.payroll.view')) {
            return true;
        }

        foreach (array_keys(config('hrm.salary_submodules', [])) as $key) {
            if ($this->canViewSalarySubmodule($key)) {
                return true;
            }
        }

        return false;
    }

    public function canViewAttendanceSubmodule(string $key): bool
    {
        if ($this->hasPermission('hrm.attendance.view') || $this->hasPermission('hrm.attendance.sync')) {
            return true;
        }

        $sub = config("hrm.attendance_submodules.{$key}");

        if (! $sub) {
            return false;
        }

        return $this->hasPermission($sub['permission'] ?? 'hrm.attendance.view');
    }

    public function canManageAttendanceSubmodule(string $key): bool
    {
        if ($this->hasPermission('hrm.attendance.manage')) {
            return true;
        }

        $sub = config("hrm.attendance_submodules.{$key}");

        if (! $sub) {
            return false;
        }

        return $this->hasPermission($sub['manage'] ?? 'hrm.attendance.manage');
    }

    public function hasAnyAttendanceViewPermission(): bool
    {
        if ($this->hasPermission('hrm.attendance.view') || $this->hasPermission('hrm.attendance.sync')) {
            return true;
        }

        foreach (array_keys(config('hrm.attendance_submodules', [])) as $key) {
            if ($this->canViewAttendanceSubmodule($key)) {
                return true;
            }
        }

        return false;
    }

    public function canViewComplianceSubmodule(string $key): bool
    {
        if ($this->hasPermission('hrm.compliance.view')) {
            return true;
        }

        $sub = config("hrm.compliance_submodules.{$key}");

        if (! $sub) {
            return false;
        }

        return $this->hasPermission($sub['permission'] ?? 'hrm.compliance.view');
    }

    public function canManageComplianceSubmodule(string $key): bool
    {
        if ($this->hasPermission('hrm.compliance.manage')) {
            return true;
        }

        $sub = config("hrm.compliance_submodules.{$key}");

        if (! $sub) {
            return false;
        }

        return $this->hasPermission($sub['manage'] ?? 'hrm.compliance.manage');
    }

    public function hasAnyComplianceViewPermission(): bool
    {
        if ($this->hasPermission('hrm.compliance.view')) {
            return true;
        }

        foreach (array_keys(config('hrm.compliance_submodules', [])) as $key) {
            if ($this->canViewComplianceSubmodule($key)) {
                return true;
            }
        }

        return false;
    }

    public function canViewFinanceSubmodule(string $key): bool
    {
        if ($this->hasPermission('hrm.finance.view')) {
            return true;
        }

        $sub = config("hrm.finance_submodules.{$key}");

        if (! $sub) {
            return false;
        }

        return $this->hasPermission($sub['permission'] ?? 'hrm.finance.view');
    }

    public function canManageFinanceSubmodule(string $key): bool
    {
        if ($this->hasPermission('hrm.finance.manage')) {
            return true;
        }

        $sub = config("hrm.finance_submodules.{$key}");

        if (! $sub) {
            return false;
        }

        return $this->hasPermission($sub['manage'] ?? 'hrm.finance.manage');
    }

    public function hasAnyFinanceViewPermission(): bool
    {
        if ($this->hasPermission('hrm.finance.view')) {
            return true;
        }

        foreach (array_keys(config('hrm.finance_submodules', [])) as $key) {
            if ($this->canViewFinanceSubmodule($key)) {
                return true;
            }
        }

        return false;
    }

    public function canViewRmgSubmodule(string $key): bool
    {
        if ($this->hasPermission('hrm.rmg.view')) {
            return true;
        }

        $sub = config("hrm.rmg_submodules.{$key}");

        if (! $sub) {
            return false;
        }

        return $this->hasPermission($sub['permission'] ?? 'hrm.rmg.view');
    }

    public function canManageRmgSubmodule(string $key): bool
    {
        if ($this->hasPermission('hrm.rmg.manage')) {
            return true;
        }

        $sub = config("hrm.rmg_submodules.{$key}");

        if (! $sub) {
            return false;
        }

        return $this->hasPermission($sub['manage'] ?? 'hrm.rmg.manage');
    }

    public function hasAnyRmgViewPermission(): bool
    {
        if ($this->hasPermission('hrm.rmg.view')) {
            return true;
        }

        foreach (array_keys(config('hrm.rmg_submodules', [])) as $key) {
            if ($this->canViewRmgSubmodule($key)) {
                return true;
            }
        }

        return false;
    }

    public function canViewPerformanceSubmodule(string $key): bool
    {
        if ($this->hasPermission('hrm.performance.view')
            || $this->hasPermission('hrm.performance.bonus.view')
            || $this->hasPermission('hrm.performance.increment.view')) {
            return true;
        }

        $sub = config("hrm.performance_submodules.{$key}");

        if (! $sub) {
            return false;
        }

        return $this->hasPermission($sub['permission'] ?? 'hrm.performance.view');
    }

    public function canManagePerformanceSubmodule(string $key): bool
    {
        if ($this->hasPermission('hrm.performance.manage')
            || $this->hasPermission('hrm.performance.bonus.manage')
            || $this->hasPermission('hrm.performance.increment.manage')) {
            return true;
        }

        $sub = config("hrm.performance_submodules.{$key}");

        if (! $sub) {
            return false;
        }

        return $this->hasPermission($sub['manage'] ?? 'hrm.performance.manage');
    }

    public function hasAnyPerformanceViewPermission(): bool
    {
        if ($this->hasPermission('hrm.performance.view')
            || $this->hasPermission('hrm.performance.bonus.view')
            || $this->hasPermission('hrm.performance.increment.view')) {
            return true;
        }

        foreach (array_keys(config('hrm.performance_submodules', [])) as $key) {
            if ($this->canViewPerformanceSubmodule($key)) {
                return true;
            }
        }

        return false;
    }

    public function canRatePerformance(): bool
    {
        return $this->hasPermission('hrm.performance.rate')
            || $this->hasPermission('hrm.performance.manage');
    }

    public function canApprovePerformance(): bool
    {
        return $this->hasPermission('hrm.performance.approve')
            || $this->hasPermission('hrm.performance.manage');
    }

    public function canViewTmsSubmodule(string $key): bool
    {
        $sub = config("tms.submodules.{$key}");

        if (! $sub) {
            return false;
        }

        if ($this->hasPermission($sub['permission'] ?? 'tms.dashboard.view')) {
            return true;
        }

        if (! empty($sub['also']) && $this->hasPermission($sub['also'])) {
            return true;
        }

        return false;
    }

    public function canManageTmsSubmodule(string $key): bool
    {
        $sub = config("tms.submodules.{$key}");

        if (! $sub || empty($sub['manage'])) {
            return false;
        }

        return $this->hasPermission($sub['manage']);
    }

    public function hasAnyTmsViewPermission(): bool
    {
        foreach (array_keys(config('tms.submodules', [])) as $key) {
            if ($this->canViewTmsSubmodule($key)) {
                return true;
            }
        }

        foreach (config('tms.permissions', []) as $group) {
            foreach (array_keys($group) as $perm) {
                if ($this->hasPermission($perm)) {
                    return true;
                }
            }
        }

        return false;
    }

    public function canReceiveNotifications(): bool
    {
        return $this->hasPermission('orders.view')
            || $this->hasAnyHrmViewPermission()
            || $this->hasAnyTmsViewPermission()
            || $this->unreadNotifications()->exists();
    }

    public function canViewMasterModule(string $namespace, string $module): bool
    {
        return match ($namespace) {
            'hrm'   => $this->canViewHrmMaster($module),
            default => $this->canViewMaster($module),
        };
    }

    public function canManageMasterModule(string $namespace, string $module): bool
    {
        return match ($namespace) {
            'hrm'   => $this->canManageHrmMaster($module),
            default => $this->canManageMaster($module),
        };
    }

    public function roleLabel(): string
    {
        return $this->role?->name ?? 'Unassigned';
    }

    public function photoUrl(): ?string
    {
        return $this->photo
            ? Storage::disk('public')->url($this->photo)
            : null;
    }

    public function initials(): string
    {
        $parts = preg_split('/\s+/', trim($this->name));

        return strtoupper(collect($parts)->take(2)->map(fn ($p) => mb_substr($p, 0, 1))->implode(''));
    }

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
}
