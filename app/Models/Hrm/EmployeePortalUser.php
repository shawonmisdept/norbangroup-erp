<?php

namespace App\Models\Hrm;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class EmployeePortalUser extends Authenticatable
{
    use Notifiable;
    protected $table = 'hrm_employee_portal_users';

    protected $fillable = [
        'employee_id',
        'password',
        'is_active',
        'last_login_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'password'      => 'hashed',
            'is_active'     => 'boolean',
            'last_login_at' => 'datetime',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function canLogin(): bool
    {
        if (! $this->is_active || ! $this->employee) {
            return false;
        }

        return in_array($this->employee->status, ['active', 'probation'], true);
    }
}
