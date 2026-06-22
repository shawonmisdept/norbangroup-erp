@component('mail::message')
# Order status updated

Dear **{{ $order->name }}**,

The status of your requirement **{{ $order->ref_code }}** has been updated.

@component('mail::panel')
**Previous Status:** {{ $previousStatus }}
**New Status:** {{ $order->status }}
@endcomponent

@php
$messages = [
    'Under Review'  => 'Our team is reviewing your requirement.',
    'Quoted'        => 'A quotation has been prepared and will be sent to you shortly.',
    'Approved'      => 'Your requirement has been approved. Production is being scheduled.',
    'In Production' => 'Production of your garments has started.',
    'Shipped'       => 'Your order has been shipped. Tracking details will follow.',
    'Closed'        => 'Your requirement has been completed successfully. Thank you!',
    'Cancelled'     => 'Your requirement has been cancelled. Please contact us for details.',
];
@endphp

{{ $messages[$order->status] ?? '' }}

Thanks,<br>
**{{ config('portal.name') }}**
@endcomponent
