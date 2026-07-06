<?php

namespace App\Services\Tms;

use App\Models\AppSetting;
use App\Models\Hrm\EmployeePortalUser;
use App\Models\Tms\TmsRentalDriverPortalUser;
use App\Models\Tms\TmsTransportRequest;
use App\Models\Tms\TmsTripLog;
use App\Models\Tms\TmsVehicle;
use App\Models\User;
use App\Support\PortalDateTime;
use App\Notifications\PortalTmsDriverTripAssignedNotification;
use App\Notifications\PortalTmsRentalDriverTripAssignedNotification;
use App\Notifications\PortalTmsRequestApprovedNotification;
use App\Notifications\PortalTmsRequestRejectedNotification;
use App\Notifications\TmsRequestCancelledNotification;
use App\Notifications\TmsRequestSubmittedNotification;
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

        $this->notifyPermission('tms.requests.approve', new TmsRequestSubmittedNotification($request), $request->factory_id);
    }

    public function requestApproved(TmsTransportRequest $request): void
    {
        if ($this->popupEnabled('request_approved')) {
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
        $this->notifyPermission('tms.requests.approve', new TmsRequestCancelledNotification($request), $request->factory_id);
    }

    public function tripStarted(TmsTransportRequest $request): void
    {
        if (! $this->popupEnabled('trip_started')) {
            return;
        }

        $this->notifyPermission('tms.requests.approve', $this->tripStatusNotification($request, 'started'), $request->factory_id);
    }

    public function tripCompleted(TmsTransportRequest $request): void
    {
        if ($this->popupEnabled('trip_completed')) {
            $this->notifyEmployeePortal($request->employee_id, $this->tripStatusNotification($request, 'completed', true));
            $this->notifyPermission('tms.requests.approve', $this->tripStatusNotification($request, 'completed'), $request->factory_id);
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

            $notification = new class($driver, $tripLog) extends Notification {
                public function __construct(private string $driverName, private TmsTripLog $tripLog) {}

                public function via(object $notifiable): array
                {
                    return ['database'];
                }

                public function toDatabase(object $notifiable): array
                {
                    return [
                        'type'    => 'tms_ot_pending',
                        'title'   => 'Driver OT Pending Payment',
                        'message' => $this->driverName . ' has ৳' . number_format((float) $this->tripLog->ot_amount, 2) . ' OT pending.',
                        'url'     => route('admin.tms.trips.show', $this->tripLog),
                    ];
                }
            };

            $this->notifyPermission('tms.overtime.manage', $notification, $tripLog->factory_id);
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

        $notification = new class($label, $message, $vehicle) extends Notification {
            public function __construct(private string $title, private string $message, private TmsVehicle $vehicle) {}

            public function via(object $notifiable): array
            {
                return ['database'];
            }

            public function toDatabase(object $notifiable): array
            {
                return [
                    'type'    => 'tms_odometer_reminder',
                    'title'   => $this->title,
                    'message' => $this->message,
                    'url'     => route('admin.tms.odometer.index'),
                ];
            }
        };

        $this->notifyPermission('tms.trips.manage', $notification, $vehicle->factory_id);
    }

    private function tripStatusNotification(TmsTransportRequest $request, string $event, bool $portal = false): Notification
    {
        $label = $event === 'started' ? 'Trip Started' : 'Trip Completed';
        $message = $event === 'started'
            ? 'Driver started the trip for ' . PortalDateTime::dateTime($request->pickup_at)
            : 'Your transport trip has been completed.';

        $url = $portal
            ? route('employee.transport.requests.show', $request)
            : route('admin.tms.requests.show', $request);

        return new class($label, $message, $url, $event) extends Notification {
            public function __construct(private string $title, private string $message, private string $url, private string $event) {}

            public function via(object $notifiable): array
            {
                return ['database'];
            }

            public function toDatabase(object $notifiable): array
            {
                return [
                    'type'    => 'tms_trip_' . $this->event,
                    'title'   => $this->title,
                    'message' => $this->message,
                    'url'     => $this->url,
                ];
            }
        };
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
            default              => true,
        };
    }

    private function notifyPermission(string $permission, Notification $notification, ?int $factoryId = null): void
    {
        $currentUserId = Auth::id();

        User::query()
            ->with('role')
            ->when($factoryId, fn ($q) => $q->where(function ($query) use ($factoryId) {
                $query->whereNull('factory_id')->orWhere('factory_id', $factoryId);
            }))
            ->get()
            ->filter(fn (User $user) => $user->hasPermission($permission))
            ->filter(fn (User $user) => $currentUserId === null || $user->id !== $currentUserId)
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
