<?php

namespace App\Services\Tms;

use App\Contracts\SmsGateway;
use App\Contracts\WhatsAppGateway;
use App\Models\AppSetting;
use App\Models\Tms\TmsTransportRequest;
use App\Models\Tms\TmsTripLog;

class TmsMessagingService
{
    public function __construct(
        private SmsGateway $sms,
        private WhatsAppGateway $whatsapp,
    ) {}

    public function requestApproved(TmsTransportRequest $request): void
    {
        $request->loadMissing(['employee', 'driver.employee', 'destination']);

        $destination = $request->destinationLabel();
        $when = $request->pickup_at->format('d M Y H:i');
        $app = config('app.name');

        if ($request->employee?->phone) {
            $this->send(
                $request->employee->phone,
                "Transport approved: {$destination} on {$when}. {$app}"
            );
        }

        $driverPhone = $request->driver?->contactPhone();
        if ($driverPhone) {
            $this->send(
                $driverPhone,
                "Trip assigned: {$request->employee?->name} to {$destination} at {$when}. {$app}"
            );
        }
    }

    public function requestRejected(TmsTransportRequest $request): void
    {
        $request->loadMissing('employee');

        if (! $request->employee?->phone) {
            return;
        }

        $this->send(
            $request->employee->phone,
            'Transport request rejected. Check the employee portal for details. ' . config('app.name')
        );
    }

    public function tripCompleted(TmsTransportRequest $request): void
    {
        $request->loadMissing('employee');

        if (! $request->employee?->phone) {
            return;
        }

        $this->send(
            $request->employee->phone,
            'Your transport trip has been completed. ' . config('app.name')
        );
    }

    public function otPendingPayment(TmsTripLog $tripLog): void
    {
        $tripLog->loadMissing(['driver.employee']);

        $phone = $tripLog->driver?->contactPhone();
        if (! $phone) {
            return;
        }

        $amount = number_format((float) $tripLog->total_driver_pay, 2);
        $this->send($phone, "Driver pay ৳{$amount} recorded for trip #{$tripLog->id}. " . config('app.name'));
    }

    private function send(string $phone, string $message): void
    {
        $settings = AppSetting::current();

        if ($settings->notify_sms_tms && $settings->canSendSms()) {
            $this->sms->send($phone, $message);
        }

        if ($settings->notify_whatsapp_tms && $settings->canSendWhatsApp()) {
            $this->whatsapp->send($phone, $message);
        }
    }
}
