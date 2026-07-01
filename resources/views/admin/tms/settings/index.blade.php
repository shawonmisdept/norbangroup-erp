@extends('layouts.admin')
@section('title', 'TMS Settings')
@section('admin-content')
@include('partials.erp.page-header', ['title' => 'TMS Settings', 'subtitle' => 'Office time, driver pay rates, and rental billing per unit'])

<div class="erp-panel p-6 max-w-2xl">
    <form method="GET" class="mb-6 flex gap-3 items-end">
        <div class="flex-1">
            <label class="erp-label">Unit</label>
            <select name="factory_id" class="erp-input" onchange="this.form.submit()">
                <option value="">Select unit…</option>
                @foreach($factories as $id => $name)
                    <option value="{{ $id }}" @selected($factoryId == $id)>{{ $name }}</option>
                @endforeach
            </select>
        </div>
    </form>

    @if($settings)
        @php $weekendDays = old('weekend_days', $settings->weekend_days ?? [5, 6]); @endphp

        <form method="POST" action="{{ route('admin.tms.settings.update') }}" class="space-y-4">
            @csrf
            @method('PUT')
            <input type="hidden" name="factory_id" value="{{ $settings->factory_id }}">

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="erp-label">Office Start</label>
                    <input type="time" name="office_start" class="erp-input" value="{{ substr(old('office_start', $settings->office_start), 0, 5) }}" required>
                </div>
                <div>
                    <label class="erp-label">Office End</label>
                    <input type="time" name="office_end" class="erp-input" value="{{ substr(old('office_end', $settings->office_end), 0, 5) }}" required>
                </div>
            </div>

            <div>
                <label class="erp-label">OT Basis</label>
                <select name="ot_basis" class="erp-input">
                    @foreach($otBasis as $k => $l)
                        <option value="{{ $k }}" @selected(old('ot_basis', $settings->ot_basis) === $k)>{{ $l }}</option>
                    @endforeach
                </select>
            </div>

            <div class="border-t pt-4 space-y-4">
                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Driver Pay (for future OT rules)</p>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="erp-label">Company Night Bill (BDT)</label>
                        <input type="number" step="0.01" min="0" name="company_night_bill" class="erp-input" value="{{ old('company_night_bill', $settings->company_night_bill) }}" required>
                    </div>
                    <div>
                        <label class="erp-label">Company Holiday Duty Bill (BDT)</label>
                        <input type="number" step="0.01" min="0" name="company_holiday_duty_bill" class="erp-input" value="{{ old('company_holiday_duty_bill', $settings->company_holiday_duty_bill) }}" required>
                    </div>
                    <div>
                        <label class="erp-label">Rental Driver OT Rate (BDT/hr)</label>
                        <input type="number" step="0.01" min="0" name="rental_ot_hourly_rate" class="erp-input" value="{{ old('rental_ot_hourly_rate', $settings->rental_ot_hourly_rate) }}" required>
                    </div>
                </div>
            </div>

            <div class="border-t pt-4 space-y-4">
                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Rental Vehicle Billing</p>
                <div>
                    <label class="erp-label">Default Rental KM Rate (BDT/km)</label>
                    <input type="number" step="0.01" min="0" name="rental_km_rate" class="erp-input" value="{{ old('rental_km_rate', $settings->rental_km_rate) }}" required>
                    <p class="text-xs text-gray-500 mt-1">Used when vendor and vehicle do not have their own rate.</p>
                </div>
                <div>
                    <p class="erp-label mb-2">Weekend Days</p>
                    <div class="flex flex-wrap gap-3">
                        @foreach($weekdayLabels as $dayNum => $dayLabel)
                            <label class="flex items-center gap-2 text-sm">
                                <input type="checkbox" name="weekend_days[]" value="{{ $dayNum }}" @checked(in_array($dayNum, (array) $weekendDays, true))>
                                {{ $dayLabel }}
                            </label>
                        @endforeach
                    </div>
                </div>
            </div>

            <div class="border-t pt-4 space-y-4 opacity-90">
                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide">GPS Tracking <span class="normal-case font-normal text-amber-600">(Phase 2 — coming soon)</span></p>
                <p class="text-xs text-gray-500 rounded-sm border border-erp-border bg-gray-50 px-3 py-2">
                    Schema and position history are ready. Enable when your GPS device or mobile provider is configured.
                    View positions under <strong>GPS Tracking</strong> in the TMS menu.
                </p>
                <label class="flex items-center gap-2 text-sm">
                    <input type="checkbox" name="gps_tracking_enabled" value="1" @checked(old('gps_tracking_enabled', $settings->gps_tracking_enabled))>
                    Enable GPS tracking (stub mode — records via future integrations only)
                </label>
                <div>
                    <label class="erp-label">GPS Provider</label>
                    <select name="gps_provider" class="erp-input">
                        @foreach($gpsProviders as $key => $provider)
                            <option value="{{ $key }}" @selected(old('gps_provider', $settings->gps_provider ?? 'none') === $key)>{{ $provider['label'] }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            @if(auth()->user()->canManageTmsSubmodule('settings'))
                <button type="submit" class="erp-btn-primary">Save Settings</button>
            @endif
        </form>
    @else
        <p class="text-sm text-gray-500">Select a unit to configure TMS settings.</p>
    @endif
</div>
@endsection
