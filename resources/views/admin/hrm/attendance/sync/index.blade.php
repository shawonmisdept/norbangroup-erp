@extends('layouts.admin')

@section('title', 'Device Sync — Attendance')

@section('breadcrumbs')
    <a href="{{ route('admin.hrm.attendance.hub') }}" class="hover:text-brand">Attendance</a>
    <span>/</span>
    <span class="text-gray-800 font-medium">Device Sync</span>
@endsection

@section('admin-content')
@include('admin.hrm.partials.submodule-nav', ['section' => 'attendance', 'current' => 'sync'])

@php
    $syncActions = '';
    if (auth()->user()->hasPermission('hrm.attendance.sync')) {
        $syncActions .= '<form method="POST" action="' . route('admin.hrm.attendance.sync-all') . '" class="inline"'
            . ' data-confirm="Pull sync from all biometric devices?"'
            . ' data-confirm-variant="primary"'
            . ' data-confirm-ok="Yes, sync all">'
            . csrf_field()
            . '<button type="submit" class="erp-btn-primary">Sync All Devices</button>'
            . '</form>';
    }
    if (auth()->user()->canManageAttendanceSubmodule('periods')) {
        $syncActions .= '<form method="POST" action="' . route('admin.hrm.attendance.process-today') . '" class="inline"'
            . ' data-confirm="Process today\'s unprocessed attendance punches?"'
            . ' data-confirm-variant="warning"'
            . ' data-confirm-ok="Yes, process today">'
            . csrf_field()
            . '<button type="submit" class="erp-btn-secondary">Process Today</button>'
            . '</form>';
    }
@endphp

@include('partials.erp.page-header', [
    'title' => 'ZKTeco SpeedFace V5L',
    'subtitle' => 'Real-time face attendance — device pushes directly to ERP',
    'actions' => $syncActions,
])

@include('admin.hrm.attendance.partials.sync-dashboard')
@endsection
