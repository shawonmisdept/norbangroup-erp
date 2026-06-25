<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
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

        $url = $notification->data['url'] ?? route('employee.dashboard');

        return redirect($url);
    }

    public function markAllRead(Request $request): RedirectResponse
    {
        $request->user('employee')->unreadNotifications->markAsRead();

        return back()->with('success', 'All notifications marked as read.');
    }
}
