@component('mail::message')
# Quotation for your requirement

Dear **{{ $order->name }}**,

Thank you for your interest. We have prepared a quotation for requirement **{{ $order->ref_code }}**.

@php
    $symbol = $order->currencySymbol();
@endphp

@component('mail::panel')
**Item:** {{ $order->item_name }}
@if($order->quantity)
**Quantity:** {{ number_format($order->quantity) }} pcs
@endif
@if($order->quote_garment_type || $order->quote_basis)
**Type:** {{ ucfirst($order->quote_garment_type ?? '') }} · {{ strtoupper($order->quote_basis ?? '') }}
@endif
@if($order->quote_price_per_pc)
**Price:** {{ $symbol }}{{ number_format((float) $order->quote_price_per_pc, 2) }} / pc
@endif
**Quoted Amount:** {{ $symbol }}{{ number_format((float) $order->quote_amount, 2) }}
@if($order->quote_lead_time_days)
**Lead Time:** {{ $order->quote_lead_time_days }} days
@endif
@if($order->quote_valid_until)
**Valid Until:** {{ $order->quote_valid_until->format('d M Y') }}
@endif
@if($order->quote_payment_terms)
**Payment Terms:** {{ $order->quote_payment_terms }}
@endif
@endcomponent

@if($order->hasQuoteBreakdown())
**Cost Breakdown (per pc)**

@foreach($order->quote_breakdown['sections'] ?? [] as $section)
@if(($section['subtotal_pc'] ?? 0) > 0)
- **{{ $section['label'] ?? $section['code'] }}:** {{ $symbol }}{{ number_format((float) $section['subtotal_pc'], 2) }}
@endif
@endforeach

@endif

@if($order->quote_notes)
**Notes**

{{ $order->quote_notes }}
@endif

Please reply to this email or contact us if you have any questions. We look forward to working with you.

Thanks,<br>
**{{ config('portal.name') }}**
@endcomponent
