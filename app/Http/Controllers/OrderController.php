<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreOrderRequest;
use App\Models\AppSetting;
use App\Models\Order;
use App\Services\OrderNotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpFoundation\Response;

class OrderController extends Controller
{
    public function create()
    {
        return view('orders.create');
    }

    public function store(StoreOrderRequest $request, OrderNotificationService $notifications)
    {
        $techpackFiles = $request->hasFile('techpack')
            ? Order::storeUploadedFiles($request->file('techpack'), 'orders/techpack')
            : [];

        $artworkFiles = $request->hasFile('artwork')
            ? Order::storeUploadedFiles($request->file('artwork'), 'orders/artwork')
            : [];

        $order = Order::create([
            'name'           => $request->name,
            'company'        => $request->company,
            'email'          => $request->email,
            'phone'          => $request->phone,
            'item_name'      => $request->item_name,
            'quantity'       => $request->quantity,
            'notes'          => $request->notes,
            'techpack_files' => $techpackFiles,
            'artwork_files'  => $artworkFiles,
        ]);

        $notifications->orderSubmitted($order);

        return redirect()->route('orders.success')
            ->with('ref_code', $order->ref_code);
    }

    public function success()
    {
        if (! session('ref_code')) {
            return redirect()->route('orders.create');
        }

        return view('orders.success', [
            'ref_code' => session('ref_code'),
        ]);
    }

    public function index(Request $request)
    {
        $query = Order::latest();

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('ref_code', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%")
                    ->orWhere('company', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $orders = $query->paginate(15);

        $stats = [
            'total'      => Order::count(),
            'new'        => Order::where('status', 'New')->count(),
            'production' => Order::where('status', 'In Production')->count(),
            'approved'   => Order::where('status', 'Approved')->count(),
        ];

        return view('admin.requirements.index', compact('orders', 'stats'));
    }

    public function show(Order $order)
    {
        return view('admin.requirements.show', compact('order'));
    }

    public function destroy(Order $order)
    {
        $order->delete();

        return redirect()->route('admin.requirements.index')
            ->with('success', "Requirement {$order->ref_code} deleted successfully.");
    }

    public function update(Request $request, Order $order, OrderNotificationService $notifications)
    {
        $request->validate([
            'status' => ['required', Rule::in(Order::STATUSES)],
        ]);

        $previousStatus = $order->status;

        if ($previousStatus === $request->status) {
            return back()->with('success', 'Status is unchanged.');
        }

        $order->update(['status' => $request->status]);

        $notifications->statusUpdated($order, $previousStatus);

        $message = "Status updated to '{$request->status}'.";
        if (AppSetting::current()->notify_mail_client_on_status) {
            $message .= " Email sent to {$order->email}.";
        }

        return back()->with('success', $message);
    }

    public function downloadFile(Order $order, string $type, int $index): StreamedResponse
    {
        $file = $this->resolveFile($order, $type, $index);

        return Storage::disk('public')->download($file['path'], $file['original_name']);
    }

    public function previewFile(Order $order, string $type, int $index): Response
    {
        $file = $this->resolveFile($order, $type, $index);

        abort_unless(Order::isPreviewable($file['mime']), 404);

        return response()->file(
            Storage::disk('public')->path($file['path']),
            ['Content-Type' => $file['mime']]
        );
    }

    private function resolveFile(Order $order, string $type, int $index): array
    {
        abort_unless(in_array($type, ['techpack', 'artwork'], true), 404);

        $file = $order->normalizedFiles($type)->get($index);

        abort_unless($file && Storage::disk('public')->exists($file['path']), 404);

        return $file;
    }
}
