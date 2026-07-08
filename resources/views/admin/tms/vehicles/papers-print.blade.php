<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vehicle Papers Status — Norban Group</title>
    @vite(['resources/css/app.css'])
    <style>
        @page {
            size: A4 landscape;
            margin: 5mm;
        }
    </style>
</head>
<body class="vehicle-papers-print-sheet">
    <div class="vehicle-papers-print-toolbar">
        <button type="button" class="erp-btn-primary" onclick="window.print()">Print</button>
        <a href="{{ route('admin.tms.vehicles.papers', $filters) }}" class="erp-btn-secondary">← Back to list</a>
    </div>

    <h1 class="vehicle-papers-print-title text-4xl font-bold">Norban Group</h1>
    <h2 class="vehicle-papers-print-subtitle text-2xl font-bold">Vehicle Papers Status</h2>
    <div class="vehicle-papers-print-meta text-sm">Printed: {{ $printedAt->format('d-M-Y g:i A') }} · {{ $vehicles->count() }} vehicle(s)</div>

    <table class="vehicle-papers-print-table">
        <colgroup>
            <col class="col-num">
            <col class="col-vehicle">
            <col class="col-reg">
            <col class="col-year">
            <col class="col-purchase">
            <col class="col-reg-date">
            <col class="col-cc">
            <col class="col-fuel">
            <col class="col-purchase-value">
            <col class="col-paper">
            <col class="col-paper">
            <col class="col-paper">
            <col class="col-paper">
            <col class="col-reg-status">
            <col class="col-unit">
            <col class="col-type">
            <col class="col-assign">
            <col class="col-assign">
            <col class="col-assign">
        </colgroup>
        <thead>
            <tr>
                <th rowspan="2">S. No</th>
                <th rowspan="2">Vehicle Name</th>
                <th rowspan="2">Registration Number</th>
                <th rowspan="2">Model / Year</th>
                <th rowspan="2">Date Of Purchase</th>
                <th rowspan="2">Date Of Registration</th>
                <th rowspan="2">CC</th>
                <th rowspan="2">Fuel</th>
                <th rowspan="2">Purchase Value</th>
                <th colspan="4">Date of</th>
                <th rowspan="2">Registration</th>
                <th rowspan="2">Unit</th>
                <th rowspan="2">Type</th>
                <th rowspan="2">Allocated User</th>
                <th rowspan="2">Driver Name</th>
                <th rowspan="2">Driver Contact No</th>
            </tr>
            <tr>
                <th class="vp-print-paper-date">Fitness</th>
                <th class="vp-print-paper-date">Taxtoken</th>
                <th class="vp-print-paper-date">Insurance</th>
                <th class="vp-print-paper-date">Route Permit</th>
            </tr>
        </thead>
        <tbody>
            @foreach($vehicles as $i => $v)
                @php
                    $papers = collect($paperService->papersForVehicle($v))->keyBy('paper_type');
                    $fitness = $papers->get('fitness');
                    $taxToken = $papers->get('tax_token');
                    $insurance = $papers->get('insurance');
                    $routePermit = $papers->get('route_permit');
                    $unitName = $v->factory?->name ?? '';
                @endphp
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td class="vp-left">{{ $v->name }}</td>
                    <td class="vp-print-reg">{{ $v->reg_number }}</td>
                    <td>{{ $v->model_year ?? '' }}</td>
                    <td class="vp-print-date">{{ $paperService->printSlashDate($v->purchase_date) }}</td>
                    <td class="vp-print-date">{{ $paperService->printSlashDate($v->registration_date) }}</td>
                    <td>{{ $v->engine_cc ? number_format($v->engine_cc) : '' }}</td>
                    <td>{{ $paperService->printFuelLabel($v->fuel_type) }}</td>
                    <td>{{ $v->purchase_value ? number_format((float) $v->purchase_value) : '' }}</td>
                    <td class="vp-print-paper-date" style="{{ $fitness ? $paperService->printPaperCellStyle($fitness['status']) : '' }}">
                        {{ $paperService->printPaperDate($fitness['expires_at'] ?? null, $fitness['status'] ?? 'missing') }}
                    </td>
                    <td class="vp-print-paper-date" style="{{ $taxToken ? $paperService->printPaperCellStyle($taxToken['status']) : '' }}">
                        {{ $paperService->printPaperDate($taxToken['expires_at'] ?? null, $taxToken['status'] ?? 'missing') }}
                    </td>
                    <td class="vp-print-paper-date" style="{{ $insurance ? $paperService->printPaperCellStyle($insurance['status']) : '' }}">
                        {{ $paperService->printPaperDate($insurance['expires_at'] ?? null, $insurance['status'] ?? 'missing') }}
                    </td>
                    <td class="vp-print-paper-date" style="{{ $routePermit ? $paperService->printPaperCellStyle($routePermit['status']) : '' }}">
                        {{ $paperService->printPaperDate($routePermit['expires_at'] ?? null, $routePermit['status'] ?? 'na') }}
                    </td>
                    <td>{{ config('tms.registration_paper_statuses.' . $v->registration_paper_status, $v->registration_paper_status) }}</td>
                    <td style="{{ $paperService->printUnitCellStyle($unitName) }}">{{ $unitName }}</td>
                    <td>{{ ucfirst($v->type) }}</td>
                    <td class="vp-left">{{ $v->allocatedEmployee?->name ?? '' }}</td>
                    <td class="vp-left">{{ $paperService->printDriverName($v) }}</td>
                    <td>{{ $v->primaryDriverContact() ?? '' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
