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
            || $this->hasAnyRmgViewPermission();
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

    public function canReceiveNotifications(): bool
    {
        return $this->hasPermission('orders.view')
            || $this->hasAnyHrmViewPermission()
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
