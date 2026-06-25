@extends('layouts.admin')
@section('title', 'Festival Bonus')
@section('admin-content')
@include('partials.erp.page-header', ['title' => 'Festival Bonus Runs', 'actions' => ($canManage?'<a href="'.route('admin.hrm.compliance.bonus.create').'" class="erp-btn-primary !py-2 !px-4 text-xs">New Run</a>':'').'<a href="'.route('admin.hrm.compliance.hub').'" class="erp-btn-secondary ml-2">← Hub</a>'])
<div class="erp-panel"><div class="overflow-x-auto"><table class="erp-table w-full text-xs">
<thead><tr><th>Factory</th><th>Type</th><th>Year</th><th>Status</th><th>Calculated</th><th></th></tr></thead>
<tbody>@forelse($runs as $run)
<tr><td>{{ $run->factory?->name }}</td><td>{{ $run->bonusTypeLabel() }}</td><td>{{ $run->year }}</td>
<td>@php $badge = match($run->status) { 'approved' => 'bg-green-100 text-green-800', 'calculated' => 'bg-amber-100 text-amber-800', default => 'bg-gray-100 text-gray-600' }; @endphp
<span class="erp-badge {{ $badge }}">{{ ucfirst($run->status) }}</span></td><td>{{ $run->calculated_at?->format('d M Y') ?? '—' }}</td>
<td class="text-right">@include('partials.erp.table-actions', ['viewUrl' => route('admin.hrm.compliance.bonus.show', $run)])</td></tr>
@empty<tr><td colspan="6" class="text-center py-8 text-gray-400">No bonus runs yet.</td></tr>@endforelse</tbody></table></div>
<div class="p-3">{{ $runs->links() }}</div></div>
@endsection
