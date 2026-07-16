<?php

namespace App\Services\Tms;

use App\Models\Tms\TmsVehicle;
use App\Models\Tms\TmsVehiclePaperRenewal;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;

class VehiclePaperService
{
    public const STATUS_OK = 'ok';

    public const STATUS_WARNING = 'warning';

    public const STATUS_URGENT = 'urgent';

    public const STATUS_EXPIRED = 'expired';

    public const STATUS_NA = 'na';

    public const STATUS_MISSING = 'missing';

    /** @return array<string, string> */
    public function paperFieldMap(): array
    {
        return [
            'fitness'      => 'fitness_expires_at',
            'tax_token'    => 'tax_token_expires_at',
            'insurance'    => 'insurance_expires_at',
            'route_permit' => 'route_permit_expires_at',
        ];
    }

    public function expiryDateFor(TmsVehicle $vehicle, string $paperType): ?Carbon
    {
        $field = $this->paperFieldMap()[$paperType] ?? null;
        if (! $field) {
            return null;
        }

        $value = $vehicle->{$field};

        return $value ? Carbon::parse($value)->startOfDay() : null;
    }

    public function statusForDate(?Carbon $expiresAt, bool $optional = false): string
    {
        if ($expiresAt === null) {
            return $optional ? self::STATUS_NA : self::STATUS_MISSING;
        }

        $today = now()->startOfDay();
        $warningDays = (int) config('tms.paper_alert_days.warning', 60);
        $urgentDays = (int) config('tms.paper_alert_days.urgent', 30);

        if ($expiresAt->lt($today)) {
            return self::STATUS_EXPIRED;
        }

        $daysLeft = (int) $today->diffInDays($expiresAt, false);

        if ($daysLeft <= $urgentDays) {
            return self::STATUS_URGENT;
        }

        if ($daysLeft <= $warningDays) {
            return self::STATUS_WARNING;
        }

        return self::STATUS_OK;
    }

    /** @return array<int, array{paper_type: string, label: string, expires_at: ?Carbon, status: string, days_left: ?int}> */
    public function papersForVehicle(TmsVehicle $vehicle): array
    {
        $papers = [];

        foreach (config('tms.paper_types', []) as $type => $label) {
            $optional = $type === 'route_permit';
            $expiresAt = $this->expiryDateFor($vehicle, $type);
            $status = $this->statusForDate($expiresAt, $optional);
            $daysLeft = $expiresAt ? (int) now()->startOfDay()->diffInDays($expiresAt, false) : null;

            $papers[] = [
                'paper_type' => $type,
                'label'      => $label,
                'expires_at' => $expiresAt,
                'status'     => $status,
                'days_left'  => $daysLeft,
            ];
        }

        return $papers;
    }

    public function worstStatusForVehicle(TmsVehicle $vehicle): string
    {
        return $this->worstStatusFromPapers($this->papersForVehicle($vehicle));
    }

    /** @param  array<int, array{paper_type: string, label: string, expires_at: ?Carbon, status: string, days_left: ?int}>  $papers */
    public function worstStatusFromPapers(array $papers): string
    {
        $priority = [
            self::STATUS_EXPIRED => 5,
            self::STATUS_URGENT  => 4,
            self::STATUS_WARNING => 3,
            self::STATUS_MISSING => 2,
            self::STATUS_OK      => 1,
            self::STATUS_NA      => 0,
        ];

        $worst = self::STATUS_OK;

        foreach ($papers as $paper) {
            if (($priority[$paper['status']] ?? 0) > ($priority[$worst] ?? 0)) {
                $worst = $paper['status'];
            }
        }

        return $worst;
    }

    /** @return array<int, array{label: string, status: string}> */
    public function alertPapersForVehicle(TmsVehicle $vehicle): array
    {
        return collect($this->papersForVehicle($vehicle))
            ->filter(fn (array $paper) => in_array($paper['status'], [
                self::STATUS_EXPIRED,
                self::STATUS_URGENT,
                self::STATUS_WARNING,
                self::STATUS_MISSING,
            ], true))
            ->map(fn (array $paper) => [
                'label'  => $paper['label'],
                'status' => $paper['status'],
            ])
            ->values()
            ->all();
    }

    /** @return array<int, string> */
    public function warningMessagesForVehicle(TmsVehicle $vehicle): array
    {
        $messages = [];

        foreach ($this->papersForVehicle($vehicle) as $paper) {
            if (! in_array($paper['status'], [self::STATUS_EXPIRED, self::STATUS_URGENT, self::STATUS_WARNING, self::STATUS_MISSING], true)) {
                continue;
            }

            $dateLabel = $paper['expires_at']?->format('d M Y') ?? 'not set';

            $messages[] = match ($paper['status']) {
                self::STATUS_EXPIRED => "{$paper['label']} expired ({$dateLabel})",
                self::STATUS_URGENT  => "{$paper['label']} expires in {$paper['days_left']} day(s) ({$dateLabel})",
                self::STATUS_WARNING => "{$paper['label']} expires in {$paper['days_left']} day(s) ({$dateLabel})",
                self::STATUS_MISSING => "{$paper['label']} expiry date not set",
                default              => "{$paper['label']} needs attention",
            };
        }

        return $messages;
    }

    /** @return array<int, string> */
    public function warningMessagesForVehicleId(?int $vehicleId, Collection $vehiclesById): array
    {
        if (! $vehicleId) {
            return [];
        }

        $vehicle = $vehiclesById->get($vehicleId);

        return $vehicle ? $this->warningMessagesForVehicle($vehicle) : [];
    }

    public function statusBadgeClass(string $status): string
    {
        return config("tms.paper_status_colors.{$status}", 'bg-gray-100 text-gray-600');
    }

    public function statusCellClass(string $status): string
    {
        return config("tms.paper_status_cell_colors.{$status}", '');
    }

    /**
     * @param  array{paper_type: string, new_expires_at: string, cost?: ?float, receipt_number?: ?string, notes?: ?string}  $data
     */
    public function recordRenewal(
        TmsVehicle $vehicle,
        array $data,
        User $user,
        ?UploadedFile $document = null,
    ): TmsVehiclePaperRenewal {
        $paperType = $data['paper_type'];
        $field = $this->paperFieldMap()[$paperType] ?? null;

        if (! $field) {
            throw new \InvalidArgumentException("Unknown paper type: {$paperType}");
        }

        $previous = $vehicle->{$field} ? Carbon::parse($vehicle->{$field}) : null;
        $newExpiry = Carbon::parse($data['new_expires_at'])->startOfDay();

        $documentPath = null;
        if ($document) {
            $documentPath = $document->store('tms/vehicle-papers', 'public');
        }

        $renewal = TmsVehiclePaperRenewal::create([
            'vehicle_id'          => $vehicle->id,
            'factory_id'          => $vehicle->factory_id,
            'paper_type'          => $paperType,
            'previous_expires_at' => $previous,
            'new_expires_at'      => $newExpiry,
            'cost'                => $data['cost'] ?? null,
            'receipt_number'      => $data['receipt_number'] ?? null,
            'document_path'       => $documentPath,
            'notes'               => $data['notes'] ?? null,
            'renewed_by'          => $user->id,
            'renewed_at'          => now(),
        ]);

        $vehicle->update([
            $field       => $newExpiry->toDateString(),
            'updated_by' => $user->id,
        ]);

        return $renewal;
    }

    /** @return array{expired: int, urgent: int, warning: int} */
    public function dashboardCounts(Collection $vehicles): array
    {
        $counts = ['expired' => 0, 'urgent' => 0, 'warning' => 0];

        foreach ($vehicles as $vehicle) {
            $worst = $this->worstStatusForVehicle($vehicle);

            if ($worst === self::STATUS_EXPIRED) {
                $counts['expired']++;
            } elseif ($worst === self::STATUS_URGENT) {
                $counts['urgent']++;
            } elseif ($worst === self::STATUS_WARNING) {
                $counts['warning']++;
            }
        }

        return $counts;
    }

    /** @return Collection<int, TmsVehicle> */
    public function vehiclesNeedingAttention(Collection $vehicles): Collection
    {
        return $vehicles->filter(function (TmsVehicle $vehicle) {
            return in_array($this->worstStatusForVehicle($vehicle), [
                self::STATUS_EXPIRED,
                self::STATUS_URGENT,
                self::STATUS_WARNING,
                self::STATUS_MISSING,
            ], true);
        })->values();
    }

    public function printSlashDate(?Carbon $date): string
    {
        return $date ? $date->format('j/M/y') : '';
    }

    public function printPaperDate(?Carbon $date, string $status): string
    {
        if ($status === self::STATUS_NA) {
            return 'N/A';
        }

        return $date ? $date->format('j-M-y') : '';
    }

    public function printFuelLabel(?string $fuelType): string
    {
        if (! $fuelType) {
            return '';
        }

        return config("tms.vehicle_papers_print_fuel_labels.{$fuelType}")
            ?? config("tms.fuel_types.{$fuelType}", $fuelType);
    }

    public function printPaperCellStyle(string $status): string
    {
        $colors = config("tms.vehicle_papers_print_colors.{$status}");

        if (! $colors) {
            return '';
        }

        return sprintf(
            'background-color:%s;color:%s;',
            $colors['bg'],
            $colors['color'],
        );
    }

    public function printUnitCellStyle(?string $unitName): string
    {
        if (! $unitName) {
            return '';
        }

        foreach (config('tms.vehicle_papers_unit_colors', []) as $label => $color) {
            if (strcasecmp($unitName, $label) === 0 || str_contains(strtolower($unitName), strtolower($label))) {
                return "background-color:{$color};";
            }
        }

        return '';
    }

    public function printDriverName(TmsVehicle $vehicle): string
    {
        $driver = $vehicle->relationLoaded('primaryDriver')
            ? $vehicle->primaryDriver
            : ($vehicle->primary_driver_id ? $vehicle->primaryDriver()->with('employee')->first() : null);

        return $driver?->employee?->name ?? '';
    }
}
