<?php

namespace Database\Seeders\Tms;

use App\Models\Factory;
use App\Models\Tms\TmsVehicle;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class VehicleSeeder extends Seeder
{
    /** Spreadsheet Owner codes → factory names. */
    private const UNIT_FACTORY_NAMES = [
        'NCL'    => 'Norban Comtex Limited',
        'HAL'    => 'Hornbill Apparel Ltd',
        'BD COM' => 'BD Com',
        'BDCOM'  => 'BD Com',
        'NFL'    => 'NFL',
        'DHL'    => 'DHL',
        'HO'     => 'Head Office',
        'HEAD OFFICE' => 'Head Office',
    ];

    public function run(): void
    {
        $rows = require database_path('seeders/data/tms_vehicles.php');

        $factoriesByName = Factory::query()
            ->where('is_active', true)
            ->get()
            ->keyBy(fn (Factory $factory) => mb_strtolower(trim($factory->name)));

        $fallbackFactoryId = $factoriesByName->get('head office')?->id
            ?? Factory::query()->where('is_active', true)->orderBy('id')->value('id');

        if (! $fallbackFactoryId) {
            $this->command?->error('No active factory found — run FactorySeeder first.');

            return;
        }

        $created = 0;
        $updated = 0;
        $seededRegNumbers = [];
        $unknownUnits = [];

        foreach ($rows as $row) {
            $regNumber = $this->normalizeRegNumber($row['reg_number']);
            $seededRegNumbers[] = $regNumber;

            $factoryId = $this->resolveFactoryId(
                $row['unit'] ?? null,
                $factoriesByName,
                (int) $fallbackFactoryId,
                $unknownUnits,
            );

            $payload = [
                'factory_id'                => $factoryId,
                'reg_number'                => $regNumber,
                'name'                      => $row['name'] ?? 'Vehicle',
                'vehicle_category'          => $row['vehicle_category'] ?? null,
                'model_year'                => $row['model_year'] ?? null,
                'engine_cc'                 => $row['engine_cc'] ?? null,
                'type'                      => $row['type'] ?? 'own',
                'fuel_type'                 => $this->mapFuelType($row['fuel_type'] ?? null),
                'passenger_capacity'        => (int) ($row['passenger_capacity'] ?? 5),
                'status'                    => $row['status'] ?? 'available',
                'purchase_date'             => $this->parseSeedDate($row['purchase_date'] ?? null),
                'registration_date'         => $this->parseSeedDate($row['registration_date'] ?? null),
                'purchase_value'            => $this->parsePurchaseValue($row['purchase_value'] ?? null),
                'fitness_expires_at'        => $this->parseSeedDate($row['fitness_expires_at'] ?? null),
                'tax_token_expires_at'      => $this->parseSeedDate($row['tax_token_expires_at'] ?? null),
                'insurance_expires_at'      => $this->parseSeedDate($row['insurance_expires_at'] ?? null),
                'route_permit_expires_at'   => $this->parseSeedDate($row['route_permit_expires_at'] ?? null),
                'registration_paper_status' => $this->mapRegistrationStatus($row['registration_paper_status'] ?? null),
                'is_dedicated'              => (bool) ($row['is_dedicated'] ?? false),
                'allocated_employee_id'     => null,
                'primary_driver_id'         => null,
                'deleted_at'                => null,
            ];

            $vehicle = TmsVehicle::withTrashed()->where('reg_number', $regNumber)->first();

            if ($vehicle) {
                $vehicle->restore();
                $vehicle->update($payload);
                $updated++;
            } else {
                TmsVehicle::create($payload);
                $created++;
            }
        }

        $removed = TmsVehicle::query()
            ->whereNotIn('reg_number', $seededRegNumbers)
            ->delete();

        $this->command?->info("TMS vehicles seeded: {$created} created, {$updated} updated, {$removed} removed.");

        if ($unknownUnits !== []) {
            $this->command?->warn('Unknown Owner units fell back to Head Office: '.implode(', ', array_unique($unknownUnits)));
        }
    }

    /**
     * @param  \Illuminate\Support\Collection<string, Factory>  $factoriesByName
     * @param  list<string>  $unknownUnits
     */
    private function resolveFactoryId(?string $unit, $factoriesByName, int $fallbackFactoryId, array &$unknownUnits): int
    {
        $unit = trim((string) $unit);

        if ($unit === '') {
            return $fallbackFactoryId;
        }

        $key = strtoupper(preg_replace('/\s+/', ' ', $unit) ?? $unit);
        $factoryName = self::UNIT_FACTORY_NAMES[$key] ?? null;

        if ($factoryName === null) {
            // Allow full factory names from seed data.
            $factoryName = $unit;
        }

        $factory = $factoriesByName->get(mb_strtolower(trim($factoryName)));

        if ($factory) {
            return (int) $factory->id;
        }

        $unknownUnits[] = $unit;

        return $fallbackFactoryId;
    }

    private function normalizeRegNumber(string $regNumber): string
    {
        $parts = preg_split('/[\s\-]+/', trim($regNumber)) ?: [];

        return implode('-', array_map(
            static fn (string $part) => strtoupper($part),
            array_values(array_filter($parts, static fn (string $part) => $part !== ''))
        ));
    }

    private function parseSeedDate(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim((string) $value);

        if ($value === '' || strcasecmp($value, 'N/A') === 0 || $value === '-') {
            return null;
        }

        foreach (['Y-m-d', 'd/M/y', 'j/n/y', 'd-M-y', 'j-M-y', 'd/m/Y'] as $format) {
            try {
                $date = Carbon::createFromFormat($format, $value);

                if ($date !== false) {
                    return $date->format('Y-m-d');
                }
            } catch (\Throwable) {
                continue;
            }
        }

        try {
            return Carbon::parse($value)->format('Y-m-d');
        } catch (\Throwable) {
            return null;
        }
    }

    private function parsePurchaseValue(mixed $value): ?float
    {
        if ($value === null || $value === '' || $value === '-') {
            return null;
        }

        $normalized = str_replace([',', ' '], '', (string) $value);

        return is_numeric($normalized) ? (float) $normalized : null;
    }

    private function mapFuelType(?string $fuel): ?string
    {
        $fuel = strtolower(trim((string) ($fuel ?? '')));

        if ($fuel === '') {
            return null;
        }

        return match (true) {
            str_contains($fuel, 'diesel')                                => 'diesel',
            $fuel === 'cng' || str_contains($fuel, 'cng')                => 'cng',
            str_contains($fuel, 'lpg')                                   => 'lpg_octane',
            str_contains($fuel, 'elec') || str_contains($fuel, 'hybrid') => 'hybrid',
            str_contains($fuel, 'petrol')                                => 'petrol',
            str_contains($fuel, 'gas')                                   => 'gas',
            str_contains($fuel, 'octen') || str_contains($fuel, 'octan') => 'octane',
            default                                                      => null,
        };
    }

    private function mapRegistrationStatus(?string $status): string
    {
        $status = strtolower(trim((string) $status));

        return match ($status) {
            'ok'      => 'ok',
            'pending' => 'pending',
            'expired' => 'expired',
            default   => 'ok',
        };
    }
}
