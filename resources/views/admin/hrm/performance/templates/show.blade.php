@extends('layouts.admin')

@section('title', $template->name)

@section('breadcrumbs')
    <a href="{{ route('admin.hrm.performance.templates.index') }}" class="hover:text-brand">Templates</a>
    <span>/</span>
    <span class="text-gray-800 font-medium">{{ $template->name }}</span>
@endsection

@section('admin-content')
@include('partials.erp.page-header', [
    'title' => $template->name,
    'subtitle' => ($template->factory?->name ?? 'All units') . ' · ' . $template->criteria->count() . ' criteria',
])

<div class="flex flex-wrap items-center justify-end gap-2 mb-4">
    <a href="{{ route('admin.hrm.performance.templates.index') }}" class="erp-btn-secondary">← Back</a>
    @if($canManage)
        <a href="{{ route('admin.hrm.performance.templates.edit', $template) }}" class="erp-btn-primary !py-2 !px-4 text-xs">Edit</a>
    @endif
</div>

<div class="erp-panel">
    <div class="overflow-x-auto">
        <table class="erp-table">
            <thead><tr><th>Code</th><th>Label</th><th>Type</th><th>Weight</th></tr></thead>
            <tbody>
                @foreach($template->criteria as $c)
                    <tr>
                        <td><code class="text-xs">{{ $c->code }}</code></td>
                        <td>{{ $c->label }}</td>
                        <td><span class="erp-badge {{ $c->criterion_type === 'auto' ? 'bg-blue-100 text-blue-700' : 'bg-purple-100 text-purple-700' }} text-[10px]">{{ $c->typeLabel() }}</span></td>
                        <td>{{ number_format($c->weight, 0) }}%</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
