<?php

namespace App\Models;

use App\Models\Concerns\HasMasterCode;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Item extends Model
{
    use HasMasterCode;

    protected $fillable = ['name', 'description', 'image', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];

    public static function codePrefix(): string
    {
        return 'ITM';
    }

    protected static function booted(): void
    {
        static::deleting(function (Item $item) {
            if ($item->image) {
                Storage::disk('public')->delete($item->image);
            }
        });
    }

    public function imageUrl(): ?string
    {
        return $this->image ? Storage::disk('public')->url($this->image) : null;
    }
}
