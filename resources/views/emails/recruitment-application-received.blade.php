@component('mail::message')
# Application Received

Thank you for applying with **{{ config('portal.name', config('app.name')) }}**.

@component('mail::panel')
- **Application No:** {{ $application->application_no }}
- **Position:** {{ $application->jobPosting?->title }}
- **Factory:** {{ $application->factory?->name }}
- **Applied:** {{ $application->applied_at->format('d M Y') }}
@endcomponent

You can track your application status anytime using your application number and phone.

@component('mail::button', ['url' => route('careers.track')])
Track Application
@endcomponent

Thanks,<br>
{{ config('portal.name', config('app.name')) }} HR Team
@endcomponent
