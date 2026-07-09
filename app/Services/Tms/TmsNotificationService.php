<?php

namespace App\Services\Tms;

use App\Models\AppSetting;
use App\Models\Hrm\EmployeePortalUser;
use App\Models\Tms\TmsRentalDriverPortalUser;
use App\Models\Tms\TmsTransportRequest;
use App\Models\Tms\TmsTripLog;
use App\Models\Tms\TmsVehicle;
use App\Models\User;
use App\Notifications\PortalEmployeeTmsTripCompletedNotification;
use App\Notifications\PortalEmployeeTmsTripStartedNotification;
use App\Notifications\PortalRentalTmsTripCompletedNotification;
use App\Notifications\PortalRentalTmsTripStartedNotification;
use App\Notifications\PortalTmsDriverTripAssignedNotification;
use App\Notifications\PortalTmsRentalDriverTripAssignedNotification;
use App\Notifications\PortalTmsRequestApprovedNotification;
use App\Notifications\PortalTmsRequestRejectedNotification;
use App\Notifications\TmsOdometerReminderNotification;
use App\Notifications\TmsOtPendingNotification;
use App\Notifications\TmsRequestCancelledNotification;
use App\Notifications\TmsRequestSubmittedNotification;
use App\Notifications\TmsTripCompletedNotification;
use App\Notifications\TmsTripStartedNotification;
use App\Notifications\TmsVehiclePaperAlertNotification;
use Carbon\Carbon;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Auth;

class TmsNotificationService
{
    public function __construct(
        private TmsMessagingService $messaging,
    ) {}

    public function requestSubmitted(TmsTransportRequest $request): void
    {
        if (! $this->popupEnabled('request_submitted')) {
            return;
        }

        $request->loadMissing('employee');

        $notification = new TmsRequestSubmittedNotification($request);

        $this->notifyTransportApprovers($notification, $request->factory_id);
    }

    public function requestApproved(TmsTransportRequest $request): void
    {
        if ($this->popupEnabled('request_approved')) {
            $request->loadMissing(['employee', 'driver.employee', 'rentalDriver']);

            $this->notifyEmployeePortal($request->employee_id, new PortalTmsRequestApprovedNotification($request));

            if ($request->driver?->employee_id) {
                $this->notifyEmployeePortal($request->driver->employee_id, new PortalTmsDriverTripAssignedNotification($request));
            }

            if ($request->rental_driver_id) {
                $this->notifyRentalDriverPortal($request->rental_driver_id, new PortalTmsRentalDriverTripAssignedNotification($request));
            }
        }

        $this->messaging->requestApproved($request);
    }

    public function requestRejected(TmsTransportRequest $request): void
    {
        if ($this->popupEnabled('request_rejected')) {
            $this->notifyEmployeePortal($request->employee_id, new PortalTmsRequestRejectedNotification($request));
        }

        $this->messaging->requestRejected($request);
    }

    public function requestCancelled(TmsTransportRequest $request): void
    {
        if (! $this->popupEnabled('request_cancelled')) {
            return;
        }

        $request->loadMissing('employee');
        $this->notifyTmsSubmodule('requests', new TmsRequestCancelledNotification($request), $request->factory_id, excludeActingAdmin: false);
    }

    public function tripStarted(TmsTransportRequest $request): void
    {
        if (! $this->popupEnabled('trip_started')) {
            return;
        }

        $request->loadMissing(['employee', 'driver.employee', 'rentalDriver']);

        $this->notifyTmsSubmodule('requests', new TmsTripStartedNotification($request), $request->factory_id);
        $this->notifyEmployeePortal($request->employee_id, new PortalEmployeeTmsTripStartedNotification($request));

        $driverEmployeeId = $request->driver?->employee_id;

        if ($driverEmployeeId && $driverEmployeeId !== $request->employee_id) {
            $this->notifyEmployeePortal($driverEmployeeId, new PortalEmployeeTmsTripStartedNotification($request, forDriver: true));
        }

        if ($request->rental_driver_id) {
            $this->notifyRentalDriverPortal($request->rental_driver_id, new PortalRentalTmsTripStartedNotification($request));
        }
    }

    public function tripCompleted(TmsTransportRequest $request): void
    {
        if ($this->popupEnabled('trip_completed')) {
            $request->loadMissing(['employee', 'driver.employee', 'rentalDriver']);

            $this->notifyEmployeePortal($request->employee_id, new PortalEmployeeTmsTripCompletedNotification($request));
            $this->notifyTmsSubmodule('requests', new TmsTripCompletedNotification($request), $request->factory_id);

            $driverEmployeeId = $request->driver?->employee_id;

            if ($driverEmployeeId && $driverEmployeeId !== $request->employee_id) {
                $this->notifyEmployeePortal($driverEmployeeId, new PortalEmployeeTmsTripCompletedNotification($request, forDriver: true));
            }

            if ($request->rental_driver_id) {
                $this->notifyRentalDriverPortal($request->rental_driver_id, new PortalRentalTmsTripCompletedNotification($request));
            }
        }

        $this->messaging->tripCompleted($request);
    }

    public function otPendingPayment(TmsTripLog $tripLog): void
    {
        if ($this->popupEnabled('ot_pending')) {
            $tripLog->loadMissing(['driver.employee', 'rentalDriver']);

            $driver = $tripLog->rentalDriver?->name
                ?? $tripLog->driver?->employee?->name
                ?? 'Driver';

            $this->notifyTmsPermission(
                ['tms.overtime.manage'],
                new TmsOtPendingNotification($driver, $tripLog),
                $tripLog->factory_id
            );
        }

        $this->messaging->otPendingPayment($tripLog);
    }

    public function odometerReminder(TmsVehicle $vehicle, string $type, string $date): void
    {
        if (! $this->popupEnabled('odometer_reminder')) {
            return;
        }

        $label = $type === 'morning' ? 'Morning KM Missing' : 'Evening KM Missing';
        $message = $type === 'morning'
            ? $vehicle->displayLabel() . ' — morning KM not recorded for ' . Carbon::parse($date)->format('d M Y') . '.'
            : $vehicle->displayLabel() . ' — evening KM pending for ' . Carbon::parse($date)->format('d M Y') . '.';

        $this->notifyTmsSubmodule(
            'odometer',
            new TmsOdometerReminderNotification($label, $message, $vehicle, $date),
            $vehicle->factory_id
        );
    }

    /** @param  array<int, string>  $warnings */
    public function vehiclePaperAlert(TmsVehicle $vehicle, string $status, array $warnings): void
    {
        if (! $this->popupEnabled('vehicle_paper')) {
            return;
        }

        $title = $status === 'expired' ? 'Vehicle Paper Expired' : 'Vehicle Paper Expiring Soon';
        $message = $vehicle->displayLabel() . ' — ' . implode('; ', array_slice($warnings, 0, 3));

        $this->notifyTmsSubmodule(
            'vehicles',
            new TmsVehiclePaperAlertNotification($title, $message, $vehicle),
            $vehicle->factory_id
        );
    }

    private function popupEnabled(string $event): bool
    {
        $settings = AppSetting::current();

        if (! $settings->notify_popup_enabled || ! $settings->notify_popup_tms) {
            return false;
        }

        return match ($event) {
            'request_submitted'  => (bool) $settings->notify_popup_tms_request_submitted,
            'request_approved'   => (bool) $settings->notify_popup_tms_request_approved,
            'request_rejected'   => (bool) $settings->notify_popup_tms_request_rejected,
            'request_cancelled'  => (bool) $settings->notify_popup_tms_request_cancelled,
            'trip_started'       => (bool) $settings->notify_popup_tms_trip_started,
            'trip_completed'     => (bool) $settings->notify_popup_tms_trip_completed,
            'ot_pending'         => (bool) $settings->notify_popup_tms_ot_pending,
            'odometer_reminder'  => (bool) $settings->notify_popup_tms_odometer_reminder,
            'vehicle_paper'      => (bool) $settings->notify_popup_tms_vehicle_paper,
            default              => true,
        };
    }

    private function notifyTmsSubmodule(string $submoduleKey, Notification $notification, ?int $factoryId = null, bool $excludeActingAdmin = false): void
    {
        $this->notifyAdminUsers(
            fn (User $user) => $user->canViewTmsSubmodule($submoduleKey),
            $notification,
            $factoryId,
            $excludeActingAdmin,
        );
    }

    /** @param  list<string>  $permissions */
    private function notifyTmsPermission(array $permissions, Notification $notification, ?int $factoryId = null): void
    {
        $this->notifyAdminUsers(
            fn (User $user) => collect($permissions)->contains(fn (string $permission) => $user->hasPermission($permission)),
            $notification,
            $factoryId,
        );
    }

    private function notifyTransportApprovers(Notification $notification, ?int $factoryId = null): void
    {
        $this->notifyAdminUsers(
            fn (User $user) => $user->hasPermission('tms.requests.approve')
                || $user->hasPermission('tms.requests.view')
                || $user->canViewTmsSubmodule('requests'),
            $notification,
            $factoryId,
            excludeActingAdmin: false,
        );
    }

    /** @param  callable(User): bool  $predicate */
    private function notifyAdminUsers(
        callable $predicate,
        Notification $notification,
        ?int $factoryId = null,
        bool $excludeActingAdmin = true,
    ): void {
        $currentAdminId = $excludeActingAdmin ? Auth::guard('web')->id() : null;

        User::query()
            ->with('role')
            ->get()
            ->filter(fn (User $user) => $factoryId === null || $user->canAccessFactory($factoryId))
            ->filter($predicate)
            ->filter(fn (User $user) => $currentAdminId === null || (int) $user->id !== (int) $currentAdminId)
            ->each(fn (User $user) => $user->notify($notification));
    }

    private function notifyEmployeePortal(int $employeeId, Notification $notification): void
    {
        $portalUser = EmployeePortalUser::where('employee_id', $employeeId)->where('is_active', true)->first();

        if ($portalUser) {
            $portalUser->notify($notification);
        }
    }

    private function notifyRentalDriverPortal(int $rentalDriverId, Notification $notification): void
    {
        $portalUser = TmsRentalDriverPortalUser::where('rental_driver_id', $rentalDriverId)->where('is_active', true)->first();

        if ($portalUser) {
            $portalUser->notify($notification);
        }
    }
}
