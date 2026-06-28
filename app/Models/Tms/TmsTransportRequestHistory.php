<?php

namespace App\Models\Tms;

use App\Models\Hrm\Employee;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TmsTransportRequestHistory extends Model
{
    public $timestamps = false;

    protected $table = 'tms_transport_request_histories';

    protected $fillable = [
        'transport_request_id', 'from_status', 'to_status',
        'changed_by_user_id', 'changed_by_employee_id', 'notes', 'created_at',
    ];

    protected function casts(): array
    {
        return ['created_at' => 'datetime'];
    }

    public function transportRequest(): BelongsTo
    {
        return $this->belongsTo(TmsTransportRequest::class, 'transport_request_id');
    }

    public function changedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by_user_id');
    }

    public function changedByEmployee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'changed_by_employee_id');
    }
}
