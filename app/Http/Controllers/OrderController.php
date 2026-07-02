<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreOrderRequest;
use App\Models\AppSetting;
use App\Models\Order;
use App\Models\User;
use App\Services\Commercial\QuoteBreakdownService;
use App\Services\OrderNotificationService;
use App\Support\RequirementAssigneeOptions;
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
        $order->load(['assignedTo.role', 'assignedTo.factory']);

        $assignees = RequirementAssigneeOptions::forOrder($order);

        return view('admin.requirements.show', compact('order', 'assignees'));
    }

    public function destroy(Order $order)
    {
        $order->delete();

        return redirect()->route('admin.requirements.index')
            ->with('success', "Requirement {$order->ref_code} deleted successfully.");
    }

    public function update(Request $request, Order $order, OrderNotificationService $notifications)
    {
        $allowedStatuses = array_values(array_unique([
            ...Order::availableStatuses(),
            $order->status,
        ]));

        $request->validate([
            'status' => ['required', Rule::in($allowedStatuses)],
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

        if ($request->status === Order::STATUS_COMMERCIAL_QUOTE) {
            return redirect()
                ->to(route('admin.requirements.show', $order) . '#commercial-quote')
                ->with('success', $message . ' Commercial Quote costing is now open below.');
        }

        return back()->with('success', $message);
    }

    public function updateWorkflow(Request $request, Order $order, OrderNotificationService $notifications, QuoteBreakdownService $quoteBreakdown)
    {
        $validated = $request->validate([
            'assigned_to_user_id' => ['nullable', 'exists:users,id'],
            'quote_garment_type'  => ['nullable', Rule::in(array_keys($quoteBreakdown->garmentTypes()))],
            'quote_basis'         => ['nullable', Rule::in(array_keys($quoteBreakdown->quoteBases()))],
            'quote_currency'      => ['nullable', Rule::in(array_keys($quoteBreakdown->currencies()))],
            'quote_breakdown'     => ['nullable', 'json'],
            'quote_amount'        => ['nullable', 'numeric', 'min:0'],
            'quote_price_per_pc'  => ['nullable', 'numeric', 'min:0'],
            'quote_notes'         => ['nullable', 'string', 'max:5000'],
            'quote_lead_time_days'=> ['nullable', 'integer', 'min:0', 'max:999'],
            'quote_valid_until'   => ['nullable', 'date'],
            'quote_payment_terms' => ['nullable', 'string', 'max:500'],
            'send_quote'          => ['sometimes', 'boolean'],
        ]);

        if (! $request->filled('quote_breakdown')) {
            $order->update([
                'assigned_to_user_id' => ($validated['assigned_to_user_id'] ?? null) ?: null,
            ]);

            return back()->with('success', 'Assignment updated.');
        }

        $quantity = max(1, (int) ($order->quantity ?? 1));
        $breakdownPayload = [];

        if (! empty($validated['quote_breakdown'])) {
            $decoded = json_decode($validated['quote_breakdown'], true);
            $breakdownPayload = is_array($decoded) ? $decoded : [];
        }

        $breakdown = $quoteBreakdown->normalizeFromRequest([
            'garment_type' => $validated['quote_garment_type'] ?? $breakdownPayload['garment_type'] ?? 'woven',
            'quote_basis'  => $validated['quote_basis'] ?? $breakdownPayload['quote_basis'] ?? 'fob',
            'currency'     => $validated['quote_currency'] ?? $breakdownPayload['currency'] ?? 'BDT',
            'sections'     => $breakdownPayload['sections'] ?? [],
        ], $quantity);

        $summary = $breakdown['summary'] ?? [];
        $quoteAmount = $summary['order_total'] ?? ($validated['quote_amount'] ?? null);
        $pricePerPc = $summary['price_per_pc'] ?? ($validated['quote_price_per_pc'] ?? null);

        $updates = [
            'assigned_to_user_id' => ($validated['assigned_to_user_id'] ?? null) ?: null,
            'quote_garment_type'  => $breakdown['garment_type'],
            'quote_basis'         => $breakdown['quote_basis'],
            'quote_currency'      => $breakdown['currency'],
            'quote_breakdown'     => $breakdown,
            'quote_price_per_pc'  => $pricePerPc,
            'quote_amount'        => $quoteAmount,
            'quote_notes'         => $validated['quote_notes'] ?? null,
            'quote_lead_time_days'=> $validated['quote_lead_time_days'] ?? null,
            'quote_valid_until'   => $validated['quote_valid_until'] ?? null,
            'quote_payment_terms' => $validated['quote_payment_terms'] ?? null,
        ];

        $sendQuote = $request->boolean('send_quote') && filled($quoteAmount) && (float) $quoteAmount > 0;
        $previousStatus = $order->status;

        if ($sendQuote) {
            $updates['quoted_at'] = now();
            if (in_array($order->status, ['New', 'Under Review', Order::STATUS_COMMERCIAL_QUOTE], true)) {
                $updates['status'] = 'Quoted';
            }
        }

        $order->update($updates);
        $order->refresh();

        $message = 'Quote and workflow details saved.';

        if ($sendQuote) {
            $notifications->quoteSent($order);

            if ($order->status !== $previousStatus) {
                $notifications->statusUpdated($order, $previousStatus);
            }

            if (AppSetting::current()->notify_mail_client_on_status) {
                $message .= " Quote email sent to {$order->email}.";
            }
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
