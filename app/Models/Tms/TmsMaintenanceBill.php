<?php

namespace App\Models\Tms;

use App\Models\Factory;
use App\Models\User;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TmsMaintenanceBill extends Model
{
    protected $table = 'tms_maintenance_bills';

    protected $fillable = [
        'factory_id', 'vehicle_id', 'bill_no', 'bill_date', 'workshop_name',
        'total_amount', 'paid_by', 'notes', 'created_by', 'updated_by',
        'posted_to_finance_at', 'posted_to_finance_by',
    ];

    protected function casts(): array
    {
        return [
            'bill_date'            => 'date',
            'total_amount'         => 'decimal:2',
            'posted_to_finance_at' => 'datetime',
        ];
    }

    protected function billNo(): Attribute
    {
        return Attribute::make(
            set: fn (?string $value) => $value === null ? null : trim($value),
        );
    }

    public function factory(): BelongsTo
    {
        return $this->belongsTo(Factory::class);
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(TmsVehicle::class, 'vehicle_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(TmsMaintenanceItem::class, 'maintenance_bill_id')->orderBy('sort_order')->orderBy('id');
    }

    public function createdByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function postedToFinanceByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'posted_to_finance_by');
    }

    public function isPostedToFinance(): bool
    {
        return $this->posted_to_finance_at !== null;
    }

    public function monthKey(): string
    {
        return $this->bill_date?->format('Y-m') ?? '';
    }

    public function monthLabel(): string
    {
        return $this->bill_date?->format('F Y') ?? '';
    }

    public function itemsDescription(): string
    {
        return $this->items
            ->pluck('item_name')
            ->filter()
            ->implode(', ');
    }
}
