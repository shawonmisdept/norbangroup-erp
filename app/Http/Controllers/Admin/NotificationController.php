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
            'hrm_working_hours'   => route('admin.hrm.compliance.working-hours.index'),
            'hrm_performance_pending_hr', 'hrm_performance_pending_rating' => route('admin.hrm.performance.reviews.index'),
            'loan_application'    => route('admin.hrm.finance.loans.index'),
            'final_settlement_approved', 'final_settlement_calculated', 'final_settlement_pending' => route('admin.hrm.finance.final-settlement.index'),
            'separation_submitted', 'separation_pending_hr', 'separation_approved', 'separation_rejected' => route('admin.hrm.separations.index'),
            'promotion_pending', 'promotion_approved', 'promotion_rejected' => route('admin.hrm.promotions.index'),
            'recruitment_application' => route('admin.hrm.recruitment.applications.index'),
            'gate_pass_pending'   => route('admin.hrm.rmg.gate-pass.index'),
            'proxy_punch_flagged' => route('admin.hrm.rmg.proxy-punch.index'),
            'worker_transfer_pending' => route('admin.hrm.rmg.worker-transfer.index'),
            'manpower_variance'   => route('admin.hrm.rmg.manpower-planning.index'),
            'new_requirement', 'status_updated' => route('admin.requirements.index'),
            'tms_request_submitted', 'tms_request_cancelled', 'tms_trip_started', 'tms_trip_completed' => route('admin.tms.requests.index'),
            'tms_ot_pending' => route('admin.tms.trips.index'),
            default => $user?->hasAnyTmsViewPermission()
                ? route('admin.tms.dashboard')
                : ($user?->hasAnyHrmViewPermission()
                    ? route('admin.hrm.dashboard')
                    : route('admin.profile.edit')),
        };
    }
}
