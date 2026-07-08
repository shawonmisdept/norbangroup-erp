<?php

namespace App\Models\Tms;

use App\Models\Factory;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class TmsVehiclePaperRenewal extends Model
{
    protected $table = 'tms_vehicle_paper_renewals';

    protected $fillable = [
        'vehicle_id', 'factory_id', 'paper_type', 'previous_expires_at', 'new_expires_at',
        'cost', 'receipt_number', 'document_path', 'notes', 'renewed_by', 'renewed_at',
    ];

    protected function casts(): array
    {
        return [
            'vehicle_id'          => 'integer',
            'factory_id'          => 'integer',
            'previous_expires_at' => 'date',
            'new_expires_at'      => 'date',
            'cost'                => 'decimal:2',
            'renewed_at'          => 'datetime',
        ];
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(TmsVehicle::class, 'vehicle_id');
    }

    public function factory(): BelongsTo
    {
        return $this->belongsTo(Factory::class);
    }

    public function renewedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'renewed_by');
    }

    public function paperTypeLabel(): string
    {
        return config("tms.paper_types.{$this->paper_type}", ucfirst(str_replace('_', ' ', $this->paper_type)));
    }

    public function hasDocument(): bool
    {
        return $this->document_path !== null
            && Storage::disk('public')->exists($this->document_path);
    }
}
