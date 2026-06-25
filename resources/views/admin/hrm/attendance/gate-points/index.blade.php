@extends('layouts.admin')

@section('title', 'Gate QR Points')

@section('breadcrumbs')
    <a href="{{ route('admin.hrm.attendance.hub') }}" class="hover:text-brand">Attendance</a>
    <span>/</span>
    <span class="text-gray-800 font-medium">Gate QR Points</span>
@endsection

@section('admin-content')
@include('admin.hrm.partials.submodule-nav', ['section' => 'attendance', 'current' => 'gate-points'])

@include('partials.erp.page-header', [
    'title' => 'Gate QR Points',
    'subtitle' => 'Print QR codes at factory gates — employees scan to check in',
    'actions' => auth()->user()?->canManageAttendanceSubmodule('gate-points')
        ? '<a href="' . route('admin.hrm.attendance.gate-points.create') . '" class="erp-btn-primary">+ New Gate</a>'
        : '',
])

<div class="erp-panel overflow-hidden">
    <div class="overflow-x-auto">
        <table class="erp-table">
            <thead>
                <tr>
                    <th>Code</th>
                    <th>Name</th>
                    <th>Factory</th>
                    <th>Location</th>
                    <th>Status</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($points as $point)
                    <tr>
                        <td class="font-mono text-xs">{{ $point->code }}</td>
                        <td class="font-medium text-xs">{{ $point->name }}</td>
                        <td class="text-xs">{{ $point->factory?->name }}</td>
                        <td class="text-xs text-gray-500">{{ $point->location ?? '—' }}</td>
                        <td>
                            @if($point->is_active)
                                <span class="erp-badge bg-emerald-100 text-emerald-800">Active</span>
                            @else
                                <span class="erp-badge bg-gray-100 text-gray-600">Inactive</span>
                            @endif
                        </td>
                        <td class="text-right">
                            <div class="erp-table-actions">
                                <a href="{{ route('admin.hrm.attendance.gate-points.qr', $point) }}" class="erp-btn-sm-secondary">QR Print</a>
                                @if(auth()->user()?->canManageAttendanceSubmodule('gate-points'))
                                    <a href="{{ route('admin.hrm.attendance.gate-points.edit', $point) }}" class="erp-btn-sm-primary">Edit</a>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center text-sm text-gray-400 py-10">No gate points yet. Create one and print QR for the main gate.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($points->hasPages())
        <div class="erp-panel-footer">{{ $points->links() }}</div>
    @endif
</div>
@endsection
