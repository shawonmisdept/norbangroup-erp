<?php

namespace App\Services\Tms;

use App\Models\Tms\TmsRentalDriver;
use App\Models\Tms\TmsRentalDriverPortalUser;
use Illuminate\Support\Str;

class RentalDriverPortalService
{
    public static function createForDriver(TmsRentalDriver $driver, ?string $password = null): array
    {
        $plainPassword = $password ?? Str::password(10);

        $portalUser = TmsRentalDriverPortalUser::updateOrCreate(
            ['rental_driver_id' => $driver->id],
            [
                'password'  => $plainPassword,
                'is_active' => static::driverEligibleForPortal($driver),
            ]
        );

        return [
            'portalUser'    => $portalUser,
            'plainPassword' => $plainPassword,
        ];
    }

    public static function resetPassword(TmsRentalDriver $driver, string $password): TmsRentalDriverPortalUser
    {
        $portalUser = TmsRentalDriverPortalUser::firstOrCreate(
            ['rental_driver_id' => $driver->id],
            ['password' => $password, 'is_active' => true]
        );

        $portalUser->update([
            'password'  => $password,
            'is_active' => static::driverEligibleForPortal($driver),
        ]);

        return $portalUser->fresh();
    }

    public static function syncPortalState(TmsRentalDriver $driver): void
    {
        $portalUser = $driver->portalUser;

        if (! $portalUser) {
            return;
        }

        $portalUser->update(['is_active' => static::driverEligibleForPortal($driver)]);
    }

    public static function driverEligibleForPortal(TmsRentalDriver $driver): bool
    {
        return $driver->isActive() && filled($driver->mobile);
    }
}
