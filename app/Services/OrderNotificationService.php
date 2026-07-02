<?php

namespace App\Services;

use App\Mail\AdminOrderNotificationMail;
use App\Mail\OrderQuoteMail;
use App\Mail\OrderReceivedMail;
use App\Mail\StatusUpdatedMail;
use App\Models\AppSetting;
use App\Models\Order;
use App\Models\User;
use App\Notifications\NewRequirementNotification;
use App\Notifications\RequirementAssignedNotification;
use App\Notifications\RequirementStatusNotification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class OrderNotificationService
{
    public function __construct(private AppSettingsService $appSettings) {}

    public function orderSubmitted(Order $order): void
    {
        $settings = AppSetting::current();

        if ($settings->notify_mail_client_on_order) {
            $this->sendMail($order->email, new OrderReceivedMail($order));
        }

        if ($settings->notify_mail_admin_on_order) {
            $this->sendMail($settings->adminMailAddress(), new AdminOrderNotificationMail($order));
        }

        if ($settings->notify_popup_enabled && $settings->notify_popup_admin_on_order) {
            $this->notifyAdmins(new NewRequirementNotification($order));
        }
    }

    public function statusUpdated(Order $order, string $previousStatus): void
    {
        $settings = AppSetting::current();

        if ($settings->notify_mail_client_on_status) {
            $this->sendMail($order->email, new StatusUpdatedMail($order, $previousStatus));
        }

        if ($settings->notify_popup_enabled && $settings->notify_popup_admin_on_status) {
            $this->notifyAdmins(new RequirementStatusNotification($order, $previousStatus));
        }
    }

    public function quoteSent(Order $order): void
    {
        $settings = AppSetting::current();

        if (! $settings->notify_mail_client_on_status || ! $order->quote_amount) {
            return;
        }

        $this->sendMail($order->email, new OrderQuoteMail($order));
    }

    public function assignmentUpdated(Order $order, ?int $previousAssigneeId, ?User $assignedBy = null): void
    {
        $settings = AppSetting::current();

        if (! $settings->notify_popup_enabled || ! $settings->notify_popup_admin_on_assignment) {
            return;
        }

        $notification = new RequirementAssignedNotification($order, $previousAssigneeId, $assignedBy);
        $actorId = $assignedBy?->id ?? Auth::id();
        $newAssigneeId = $order->assigned_to_user_id ? (int) $order->assigned_to_user_id : null;

        User::query()
            ->with('role')
            ->get()
            ->filter(fn (User $user) => $user->hasPermission('orders.view'))
            ->filter(function (User $user) use ($actorId, $newAssigneeId) {
                if ($newAssigneeId !== null && (int) $user->id === $newAssigneeId) {
                    return true;
                }

                return $actorId === null || (int) $user->id !== (int) $actorId;
            })
            ->each(fn (User $user) => $user->notify($notification));
    }

    private function sendMail(string $recipient, object $mailable): void
    {
        $settings = AppSetting::current();

        if (! $settings->canSendMail()) {
            Log::warning('Mail skipped: SMTP/Gmail is not fully configured in App Settings.', [
                'mailer' => $settings->mail_mailer,
            ]);

            return;
        }

        $this->appSettings->applyRuntimeConfig();

        try {
            Mail::to($recipient)->send($mailable);
        } catch (Throwable $e) {
            Log::error('Mail delivery failed.', [
                'recipient' => $recipient,
                'mailer'    => config('mail.default'),
                'host'      => config('mail.mailers.smtp.host'),
                'error'     => $e->getMessage(),
            ]);
        }
    }

    private function notifyAdmins(object $notification, ?int $exceptUserId = null): void
    {
        $actorId = $exceptUserId ?? Auth::id();

        User::query()
            ->with('role')
            ->get()
            ->filter(fn (User $user) => $user->hasPermission('orders.view'))
            ->filter(fn (User $user) => $actorId === null || (int) $user->id !== (int) $actorId)
            ->each(fn (User $user) => $user->notify($notification));
    }
}
