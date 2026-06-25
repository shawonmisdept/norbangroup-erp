@php
    $application = $interview->application;
@endphp

@component('mail::message')
# Interview Reminder

This is a reminder for your upcoming interview.

@component('mail::panel')
- **Application No:** {{ $application?->application_no }}
- **Position:** {{ $application?->jobPosting?->title }}
- **Date & Time:** {{ $interview->scheduled_at->format('d M Y, h:i A') }}
@if($interview->location)
- **Location:** {{ $interview->location }}
@endif
@endcomponent

@component('mail::button', ['url' => route('careers.track')])
Track Application
@endcomponent

Thanks,<br>
{{ config('portal.name', config('app.name')) }} HR Team
@endcomponent
