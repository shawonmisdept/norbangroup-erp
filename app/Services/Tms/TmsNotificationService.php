<?php

namespace App\Services\Tms;

use App\Models\AppSetting;
use App\Models\Hrm\EmployeePortalUser;
use App\Models\Tms\TmsTransportRequest;
use App\Models\Tms\TmsTripLog;
use App\Models\User;
use App\Notifications\PortalTmsDriverTripAssignedNotification;
use App\Notifications\PortalTmsRequestApprovedNotification;
use App\Notifications\PortalTmsRequestRejectedNotification;
use App\Notifications\TmsRequestCancelledNotification;
use App\Notifications\TmsRequestSubmittedNotification;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Auth;

class TmsNotificationService
{
    public function requestSubmitted(TmsTransportRequest $request): void
    {
        if (! $this->enabled()) {
            return;
        }

        $request->loadMissing('employee');

        $this->notifyPermission('tms.requests.approve', new TmsRequestSubmittedNotification($request), $request->factory_id);
    }

    public function requestApproved(TmsTransportRequest $request): void
    {
        if (! $this->enabled()) {
            return;
        }

        $this->notifyEmployeePortal($request->employee_id, new PortalTmsRequestApprovedNotification($request));

        if ($request->driver?->employee_id) {
            $this->notifyEmployeePortal($request->driver->employee_id, new PortalTmsDriverTripAssignedNotification($request));
        }
    }

    public function requestRejected(TmsTransportRequest $request): void
    {
        if (! $this->enabled()) {
            return;
        }

        $this->notifyEmployeePortal($request->employee_id, new PortalTmsRequestRejectedNotification($request));
    }

    public function requestCancelled(TmsTransportRequest $request): void
    {
        if (! $this->enabled()) {
            return;
        }

        $request->loadMissing('employee');
        $this->notifyPermission('tms.requests.approve', new TmsRequestCancelledNotification($request), $request->factory_id);
    }

    public function tripStarted(TmsTransportRequest $request): void
    {
        if (! $this->enabled()) {
            return;
        }

        $this->notifyPermission('tms.requests.approve', $this->tripStatusNotification($request, 'started'), $request->factory_id);
    }

    public function tripCompleted(TmsTransportRequest $request): void
    {
        if (! $this->enabled()) {
            return;
        }

        $this->notifyEmployeePortal($request->employee_id, $this->tripStatusNotification($request, 'completed', true));
        $this->notifyPermission('tms.requests.approve', $this->tripStatusNotification($request, 'completed'), $request->factory_id);
    }

    public function otPendingPayment(TmsTripLog $tripLog): void
    {
        if (! $this->enabled()) {
            return;
        }

        $driver = $tripLog->driver?->employee?->name ?? 'Driver';

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

    private function tripStatusNotification(TmsTransportRequest $request, string $event, bool $portal = false): Notification
    {
        $label = $event === 'started' ? 'Trip Started' : 'Trip Completed';
        $message = $event === 'started'
            ? 'Driver started the trip for ' . $request->pickup_at->format('d M Y H:i')
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

    private function enabled(): bool
    {
        return AppSetting::current()->notify_popup_enabled;
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
}
