@php
    $application = $interview->application;
@endphp

@component('mail::message')
# Interview Scheduled

An interview has been scheduled for your application **{{ $application?->application_no }}**.

@component('mail::panel')
- **Position:** {{ $application?->jobPosting?->title }}
- **Date & Time:** {{ $interview->scheduled_at->format('d M Y, h:i A') }}
- **Type:** {{ $interview->typeLabel() }}
@if($interview->location)
- **Location:** {{ $interview->location }}
@endif
@endcomponent

Please arrive on time and bring your NID and relevant documents.

@component('mail::button', ['url' => route('careers.track')])
Track Application
@endcomponent

Thanks,<br>
{{ config('portal.name', config('app.name')) }} HR Team
@endcomponent
