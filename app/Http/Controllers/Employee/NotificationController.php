<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Support\NotificationUrl;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $notifications = $request->user('employee')->notifications()->paginate(20);

        return view('employee.notifications.index', compact('notifications'));
    }

    public function unreadCount(Request $request): JsonResponse
    {
        return response()->json([
            'count' => $request->user('employee')->unreadNotifications()->count(),
        ]);
    }

    public function markRead(Request $request, string $id): RedirectResponse
    {
        $notification = $request->user('employee')->notifications()->where('id', $id)->firstOrFail();
        $notification->markAsRead();

        $url = NotificationUrl::resolve($notification->data['url'] ?? null)
            ?? $this->fallbackUrl($notification->data['type'] ?? null);

        return redirect($url);
    }

    public function markAllRead(Request $request): RedirectResponse
    {
        $request->user('employee')->unreadNotifications->markAsRead();

        return back()->with('success', 'All notifications marked as read.');
    }

    private function fallbackUrl(?string $type): string
    {
        return match ($type) {
            'tms_request_approved', 'tms_request_rejected' => route('employee.transport.requests'),
            'tms_trip_assigned', 'tms_trip_started', 'tms_trip_completed' => route('employee.transport.trips'),
            default => route('employee.dashboard'),
        };
    }
}
