<?php

namespace App\Models\Tms;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TmsMaintenanceItem extends Model
{
    protected $table = 'tms_maintenance_items';

    protected $fillable = [
        'maintenance_bill_id', 'item_name', 'quantity', 'unit', 'amount', 'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:3',
            'amount'   => 'decimal:2',
        ];
    }

    public function bill(): BelongsTo
    {
        return $this->belongsTo(TmsMaintenanceBill::class, 'maintenance_bill_id');
    }

    public function quantityLabel(): ?string
    {
        if ($this->quantity === null) {
            return null;
        }

        $qty = rtrim(rtrim(number_format((float) $this->quantity, 3, '.', ''), '0'), '.');

        return $this->unit ? "{$qty} {$this->unit}" : $qty;
    }
}
