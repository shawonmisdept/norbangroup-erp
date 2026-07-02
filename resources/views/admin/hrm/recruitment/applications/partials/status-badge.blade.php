@php
    $statusBadge = match($application->status) {
        'applied'   => 'bg-sky-100 text-sky-800 ring-sky-200',
        'screening' => 'bg-violet-100 text-violet-800 ring-violet-200',
        'interview' => 'bg-purple-100 text-purple-800 ring-purple-200',
        'selected'  => 'bg-indigo-100 text-indigo-800 ring-indigo-200',
        'offered'   => 'bg-amber-100 text-amber-800 ring-amber-200',
        'hired'     => 'bg-emerald-100 text-emerald-800 ring-emerald-200',
        'rejected'  => 'bg-red-100 text-red-800 ring-red-200',
        'withdrawn' => 'bg-gray-100 text-gray-600 ring-gray-200',
        default     => 'bg-gray-100 text-gray-600 ring-gray-200',
    };
@endphp
<span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-semibold ring-1 ring-inset {{ $statusBadge }}">
    <span class="w-1.5 h-1.5 rounded-full bg-current opacity-70"></span>
    {{ $application->statusLabel() }}
</span>
