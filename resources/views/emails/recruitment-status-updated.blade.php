@component('mail::message')
# Application Status Updated

Your application **{{ $application->application_no }}** has been updated.

@component('mail::panel')
- **Position:** {{ $application->jobPosting?->title }}
- **New Status:** {{ $statusLabel }}
@if($notes)
- **Note:** {{ $notes }}
@endif
@if($application->status === 'rejected' && $application->rejection_reason)
- **Reason:** {{ $application->rejection_reason }}
@endif
@endcomponent

@component('mail::button', ['url' => route('careers.track')])
Track Application
@endcomponent

Thanks,<br>
{{ config('portal.name', config('app.name')) }} HR Team
@endcomponent
