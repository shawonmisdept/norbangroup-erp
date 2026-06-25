@component('mail::message')
# Leave Application Submitted

**{{ $application->employee?->name }}** ({{ $application->employee?->employee_code }}) has applied for leave.

@component('mail::panel')
- **Type:** {{ $application->leaveType?->name ?? 'Leave' }}
- **Dates:** {{ $application->start_date->format('d M Y') }} — {{ $application->end_date->format('d M Y') }}
- **Days:** {{ $application->total_days }}
@if($application->reason)
- **Reason:** {{ $application->reason }}
@endif
@endcomponent

Please review and approve in the employee portal or ERP.

Thanks,<br>
{{ config('app.name') }}
@endcomponent
