@component('mail::message')
# New requirement submitted

A new production requirement has been submitted on Order Portal.

@component('mail::panel')
**Reference:** {{ $order->ref_code }}
**Client:** {{ $order->name }}{{ $order->company ? ' (' . $order->company . ')' : '' }}
**Email:** {{ $order->email }}
**Phone:** {{ $order->phone }}
**Item Name:** {{ $order->item_name }}
**Quantity:** {{ $order->quantity ? $order->quantity . ' pcs' : '—' }}
**Tech Pack Files:** {{ count($order->techpack_files ?? []) }}
**Artwork Files:** {{ count($order->artwork_files ?? []) }}
@endcomponent

@component('mail::button', ['url' => route('admin.requirements.show', $order), 'color' => 'primary'])
View in Dashboard
@endcomponent

Thanks,<br>
**{{ config('app.name') }}**
@endcomponent
