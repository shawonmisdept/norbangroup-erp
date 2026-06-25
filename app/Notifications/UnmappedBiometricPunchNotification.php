<?php

namespace App\Notifications;

use App\Models\Hrm\AttendanceRawPunch;
use Illuminate\Notifications\Notification;

class UnmappedBiometricPunchNotification extends Notification
{
    public function __construct(public AttendanceRawPunch $punch) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'type'    => 'hrm_unmapped_punch',
            'title'   => 'Unmapped Biometric Punch',
            'message' => 'PIN ' . $this->punch->biometric_user_id . ' punched at '
                . $this->punch->punched_at->format('d M Y H:i') . ' — employee not mapped',
            'url'     => route('admin.hrm.attendance.punches'),
        ];
    }
}
