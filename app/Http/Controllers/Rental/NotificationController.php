<?php

namespace App\Http\Controllers\Rental;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Rental\Concerns\ResolvesPortalRentalDriver;
use App\Support\NotificationUrl;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    use ResolvesPortalRentalDriver;

    public function index(Request $request)
    {
        $driver = $this->portalRentalDriver($request);
        $portalUser = Auth::guard('rental_driver')->user();

        $notifications = $portalUser->notifications()->paginate(20);

        return view('rental.notifications', compact('notifications', 'driver'));
    }

    public function unreadCount(Request $request): JsonResponse
    {
        return response()->json([
            'count' => $request->user('rental_driver')->unreadNotifications()->count(),
        ]);
    }

    public function markRead(Request $request, string $id)
    {
        $portalUser = Auth::guard('rental_driver')->user();
        $notification = $portalUser->notifications()->where('id', $id)->firstOrFail();
        $notification->markAsRead();

        $url = NotificationUrl::resolve($notification->data['url'] ?? null)
            ?? $this->fallbackUrl($notification->data['type'] ?? null);

        return redirect($url);
    }

    public function markAllRead(Request $request)
    {
        Auth::guard('rental_driver')->user()?->unreadNotifications->markAsRead();

        return back()->with('success', 'All notifications marked as read.');
    }

    private function fallbackUrl(?string $type): string
    {
        return match ($type) {
            'tms_trip_assigned', 'tms_trip_started', 'tms_trip_completed' => route('rental.trips'),
            default => route('rental.dashboard'),
        };
    }
}
