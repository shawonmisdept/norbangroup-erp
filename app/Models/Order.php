<?php

namespace App\Models;

use App\Models\Concerns\HasFileMetadata;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class Order extends Model
{
    use HasFileMetadata;
    public const STATUSES = [
        'New',
        'Under Review',
        'Quoted',
        'Approved',
        'In Production',
        'Shipped',
        'Closed',
        'Cancelled',
    ];

    protected $fillable = [
        'name', 'company', 'email', 'phone',
        'item_name', 'quantity', 'notes', 'status',
        'techpack_files', 'artwork_files',
    ];

    protected $casts = [
        'techpack_files' => 'array',
        'artwork_files' => 'array',
    ];

    protected static function booted(): void
    {
        static::creating(function (Order $order) {
            if ($order->ref_code) {
                return;
            }

            do {
                $order->ref_code = 'NOR-' . strtoupper(Str::random(6));
            } while (static::where('ref_code', $order->ref_code)->exists());
        });

        static::deleting(function (Order $order) {
            $order->deleteStoredFiles();
        });
    }

    public function deleteStoredFiles(): void
    {
        foreach (['techpack', 'artwork'] as $type) {
            foreach ($this->normalizedFiles($type) as $file) {
                if (! empty($file['path'])) {
                    Storage::disk('public')->delete($file['path']);
                }
            }
        }
    }

    public static function statusColors(): array
    {
        return [
            'New'           => 'bg-blue-100 text-blue-700',
            'Under Review'  => 'bg-amber-100 text-amber-700',
            'Quoted'        => 'bg-purple-100 text-purple-700',
            'Approved'      => 'bg-green-100 text-green-700',
            'In Production' => 'bg-teal-100 text-teal-700',
            'Shipped'       => 'bg-sky-100 text-sky-700',
            'Closed'        => 'bg-gray-100 text-gray-600',
            'Cancelled'     => 'bg-red-100 text-red-700',
        ];
    }
}
