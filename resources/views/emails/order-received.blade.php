@component('mail::message')
# Your requirement has been received

Dear **{{ $order->name }}**,

Thank you for submitting your production requirement. Our team will contact you within 24 hours.

@component('mail::panel')
**Reference:** {{ $order->ref_code }}
**Item Name:** {{ $order->item_name }}
**Quantity:** {{ $order->quantity ? $order->quantity . ' pcs' : '—' }}
**Status:** {{ $order->status }}
@endcomponent

@component('mail::button', ['url' => config('app.url'), 'color' => 'primary'])
Visit Order Portal
@endcomponent

Thanks,<br>
**{{ config('portal.name') }}**
@endcomponent
