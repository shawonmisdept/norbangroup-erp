@extends('layouts.admin')
@section('title', 'New ' . $config['label'])
@section('admin-content')
@include('partials.erp.page-header', ['title' => 'New ' . $config['label'], 'actions' => '<a href="' . route('admin.hrm.rmg.' . $submodule . '.index') . '" class="erp-btn-secondary">← Back</a>'])
<div class="erp-panel max-w-lg">
    <form method="POST" action="{{ route('admin.hrm.rmg.' . $submodule . '.store') }}" class="erp-panel-body space-y-4">
        @csrf
        <div><label class="erp-form-label">Factory</label><select name="factory_id" class="erp-input" required>@foreach($factories as $id=>$n)<option value="{{ $id }}">{{ $n }}</option>@endforeach</select></div>

        @if(in_array($submodule, ['osd-movement', 'canteen', 'medical', 'training', 'salary-hold']))
        <div><label class="erp-form-label">Employee</label><select name="employee_id" class="erp-input" required><option value="">Select</option>@foreach($employees as $id=>$n)<option value="{{ $id }}">{{ $n }}</option>@endforeach</select></div>
        @endif

        @if($submodule === 'osd-movement')
        <div><label class="erp-form-label">Movement Type</label><select name="movement_type" class="erp-input">@foreach($types as $k=>$l)<option value="{{ $k }}">{{ $l }}</option>@endforeach</select></div>
        <div class="grid grid-cols-2 gap-4">
            <div><label class="erp-form-label">Start Date</label><input type="date" name="start_date" value="{{ old('start_date', $record->start_date?->toDateString()) }}" class="erp-input" required></div>
            <div><label class="erp-form-label">End Date</label><input type="date" name="end_date" value="{{ old('end_date', $record->end_date?->toDateString()) }}" class="erp-input" required></div>
        </div>
        <div><label class="erp-form-label">Destination</label><input type="text" name="destination" class="erp-input"></div>
        <div><label class="erp-form-label">Purpose</label><textarea name="purpose" rows="2" class="erp-input"></textarea></div>
        @endif

        @if($submodule === 'canteen')
        <div class="grid grid-cols-2 gap-4">
            <div><label class="erp-form-label">Year</label><input type="number" name="period_year" value="{{ old('period_year', $record->period_year) }}" class="erp-input" required></div>
            <div><label class="erp-form-label">Month</label><input type="number" name="period_month" value="{{ old('period_month', $record->period_month) }}" class="erp-input" min="1" max="12" required></div>
        </div>
        <div class="grid grid-cols-2 gap-4">
            <div><label class="erp-form-label">Meal Count</label><input type="number" name="meal_count" value="{{ old('meal_count', $record->meal_count) }}" class="erp-input" min="0" required></div>
            <div><label class="erp-form-label">Amount (৳)</label><input type="number" step="0.01" name="amount" value="{{ old('amount', $record->amount) }}" class="erp-input" min="0" required></div>
        </div>
        <div><label class="erp-form-label">Notes</label><textarea name="notes" rows="2" class="erp-input"></textarea></div>
        @endif

        @if($submodule === 'medical')
        <div><label class="erp-form-label">Visit Date</label><input type="date" name="visit_date" value="{{ old('visit_date', $record->visit_date?->toDateString()) }}" class="erp-input" required></div>
        <div><label class="erp-form-label">Complaint</label><input type="text" name="complaint" class="erp-input"></div>
        <div><label class="erp-form-label">Diagnosis</label><input type="text" name="diagnosis" class="erp-input"></div>
        <div><label class="erp-form-label">Treatment</label><input type="text" name="treatment" class="erp-input"></div>
        <label class="flex items-center gap-2 text-sm"><input type="hidden" name="referred" value="0"><input type="checkbox" name="referred" value="1" class="rounded border-gray-300"> Referred to hospital</label>
        <div><label class="erp-form-label">Notes</label><textarea name="notes" rows="2" class="erp-input"></textarea></div>
        @endif

        @if($submodule === 'training')
        <div><label class="erp-form-label">Training Type</label><select name="training_type" class="erp-input">@foreach($types as $k=>$l)<option value="{{ $k }}">{{ $l }}</option>@endforeach</select></div>
        <div><label class="erp-form-label">Title</label><input type="text" name="title" class="erp-input" required></div>
        <div><label class="erp-form-label">Provider</label><input type="text" name="provider" class="erp-input"></div>
        <div class="grid grid-cols-2 gap-4">
            <div><label class="erp-form-label">Training Date</label><input type="date" name="training_date" value="{{ old('training_date', $record->training_date?->toDateString()) }}" class="erp-input" required></div>
            <div><label class="erp-form-label">Expiry Date</label><input type="date" name="expiry_date" class="erp-input"></div>
        </div>
        <div><label class="erp-form-label">Certificate No</label><input type="text" name="certificate_no" class="erp-input"></div>
        <div><label class="erp-form-label">Notes</label><textarea name="notes" rows="2" class="erp-input"></textarea></div>
        @endif

        @if($submodule === 'sub-contract')
        <div><label class="erp-form-label">Line</label><select name="line_id" class="erp-input"><option value="">—</option>@foreach($lines as $id=>$n)<option value="{{ $id }}">{{ $n }}</option>@endforeach</select></div>
        <div><label class="erp-form-label">Agency Name</label><input type="text" name="agency_name" class="erp-input" required></div>
        <div><label class="erp-form-label">Worker Name</label><input type="text" name="name" class="erp-input" required></div>
        <div class="grid grid-cols-2 gap-4">
            <div><label class="erp-form-label">Phone</label><input type="text" name="phone" class="erp-input"></div>
            <div><label class="erp-form-label">NID</label><input type="text" name="nid_number" class="erp-input"></div>
        </div>
        <div class="grid grid-cols-2 gap-4">
            <div><label class="erp-form-label">Start Date</label><input type="date" name="start_date" value="{{ old('start_date', $record->start_date?->toDateString()) }}" class="erp-input" required></div>
            <div><label class="erp-form-label">End Date</label><input type="date" name="end_date" class="erp-input"></div>
        </div>
        <div><label class="erp-form-label">Notes</label><textarea name="notes" rows="2" class="erp-input"></textarea></div>
        @endif

        @if($submodule === 'buyer-holiday')
        <div><label class="erp-form-label">Buyer</label><select name="buyer_id" class="erp-input" required><option value="">Select</option>@foreach($buyers as $id=>$n)<option value="{{ $id }}">{{ $n }}</option>@endforeach</select></div>
        <div><label class="erp-form-label">Holiday Name</label><input type="text" name="name" class="erp-input" required></div>
        <div><label class="erp-form-label">Date</label><input type="date" name="date" value="{{ old('date', $record->date?->toDateString()) }}" class="erp-input" required></div>
        <div><label class="erp-form-label">Description</label><textarea name="description" rows="2" class="erp-input"></textarea></div>
        <label class="flex items-center gap-2 text-sm"><input type="hidden" name="is_active" value="0"><input type="checkbox" name="is_active" value="1" checked class="rounded border-gray-300"> Active</label>
        @endif

        @if($submodule === 'salary-hold')
        <div><label class="erp-form-label">Payroll Period (optional)</label><select name="payroll_period_id" class="erp-input"><option value="">—</option>@foreach($periods as $id=>$n)<option value="{{ $id }}">{{ $n }}</option>@endforeach</select></div>
        <div><label class="erp-form-label">Reason</label><textarea name="reason" rows="2" class="erp-input" required></textarea></div>
        <div class="grid grid-cols-2 gap-4">
            <div><label class="erp-form-label">Hold From</label><input type="date" name="hold_from" value="{{ old('hold_from', $record->hold_from?->toDateString()) }}" class="erp-input" required></div>
            <div><label class="erp-form-label">Hold Until</label><input type="date" name="hold_until" class="erp-input"></div>
        </div>
        @endif

        @if($submodule === 'production-incentive')
        <div><label class="erp-form-label">Line</label><select name="line_id" class="erp-input" required>@foreach($lines as $id=>$n)<option value="{{ $id }}">{{ $n }}</option>@endforeach</select></div>
        <div class="grid grid-cols-2 gap-4">
            <div><label class="erp-form-label">Year</label><input type="number" name="period_year" value="{{ old('period_year', $record->period_year) }}" class="erp-input" required></div>
            <div><label class="erp-form-label">Month</label><input type="number" name="period_month" value="{{ old('period_month', $record->period_month) }}" class="erp-input" min="1" max="12" required></div>
        </div>
        <div class="grid grid-cols-2 gap-4">
            <div><label class="erp-form-label">Output Qty</label><input type="number" name="output_qty" value="{{ old('output_qty', $record->output_qty) }}" class="erp-input" min="0" required></div>
            <div><label class="erp-form-label">Rate (৳)</label><input type="number" step="0.01" name="incentive_rate" value="{{ old('incentive_rate', $record->incentive_rate) }}" class="erp-input" min="0" required></div>
        </div>
        <div><label class="erp-form-label">Notes</label><textarea name="notes" rows="2" class="erp-input"></textarea></div>
        @endif

        <button type="submit" class="erp-btn-primary">Save</button>
    </form>
</div>
@endsection
