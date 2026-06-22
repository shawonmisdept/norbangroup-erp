<?php

namespace App\Models;

use App\Models\Concerns\HasMasterCode;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Supplier extends Model
{
    use HasMasterCode;

    protected $fillable = [
        'name', 'supplier_type_id', 'company', 'email', 'phone', 'country', 'is_active',
    ];

    protected $casts = ['is_active' => 'boolean'];

    public static function codePrefix(): string
    {
        return 'SUP';
    }

    public function supplierType(): BelongsTo
    {
        return $this->belongsTo(SupplierType::class);
    }
}
