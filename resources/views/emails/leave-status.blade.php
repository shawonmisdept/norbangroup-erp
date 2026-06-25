@component('mail::message')
# Leave {{ $statusLabel }}

Your leave application has been **{{ strtolower($statusLabel) }}**.

@component('mail::panel')
- **Type:** {{ $application->leaveType?->name ?? 'Leave' }}
- **Dates:** {{ $application->start_date->format('d M Y') }} — {{ $application->end_date->format('d M Y') }}
- **Days:** {{ $application->total_days }}
@if($application->rejection_reason)
- **Reason:** {{ $application->rejection_reason }}
@endif
@endcomponent

@component('mail::button', ['url' => route('employee.leave')])
View Leave
@endcomponent

Thanks,<br>
{{ config('app.name') }}
@endcomponent
