@component('mail::message')
# Quotation for your requirement

Dear **{{ $order->name }}**,

Thank you for your interest. We have prepared a quotation for requirement **{{ $order->ref_code }}**.

@component('mail::panel')
**Item:** {{ $order->item_name }}
@if($order->quantity)
**Quantity:** {{ number_format($order->quantity) }} pcs
@endif
**Quoted Amount:** ৳{{ number_format($order->quote_amount, 2) }}
@endcomponent

@if($order->quote_notes)
**Notes**

{{ $order->quote_notes }}
@endif

Please reply to this email or contact us if you have any questions. We look forward to working with you.

Thanks,<br>
**{{ config('portal.name') }}**
@endcomponent
