<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NotificationController extends Controller
{
    public function index(Request $request): View
    {
        $notifications = $request->user()->notifications()->paginate(25);

        return view('admin.notifications.index', compact('notifications'));
    }

    public function unreadCount(Request $request): JsonResponse
    {
        return response()->json([
            'count' => $request->user()->unreadNotifications()->count(),
        ]);
    }

    public function markRead(Request $request, string $id): RedirectResponse
    {
        $notification = $request->user()->notifications()->where('id', $id)->firstOrFail();
        $notification->markAsRead();

        $url = $notification->data['url'] ?? $this->fallbackUrl($notification->data['type'] ?? null, $request->user());

        return redirect($url);
    }

    public function markAllRead(Request $request): RedirectResponse
    {
        $request->user()->unreadNotifications->markAsRead();

        return back()->with('success', 'All notifications marked as read.');
    }

    private function fallbackUrl(?string $type, $user): string
    {
        return match ($type) {
            'hrm_late_acceptance' => route('admin.hrm.attendance.late-acceptance.index'),
            'hrm_unmapped_punch'  => route('admin.hrm.attendance.punches'),
            'hrm_manual_punch'    => route('admin.hrm.attendance.manual-punch.index'),
            'hrm_sync_failed'     => route('admin.hrm.attendance.sync.failures'),
            'hrm_leave_applied', 'hrm_leave_pending_hr' => route('admin.hrm.leave.transactions.index'),
            'hrm_daily_attendance'=> route('admin.hrm.attendance.reports.index'),
            'hrm_contract_expiry', 'hrm_probation_end', 'hrm_ot_limit' => route('admin.hrm.employees.index'),
            'new_requirement', 'status_updated' => route('admin.requirements.index'),
            default => $user?->hasAnyHrmViewPermission()
                ? route('admin.hrm.dashboard')
                : route('admin.profile.edit'),
        };
    }
}
