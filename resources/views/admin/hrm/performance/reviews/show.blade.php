@extends('layouts.admin')

@section('title', 'Review #' . $review->id)

@section('breadcrumbs')
    <a href="{{ route('admin.hrm.performance.reviews.index') }}" class="hover:text-brand">Reviews</a>
    <span>/</span>
    <span class="text-gray-800 font-medium">#{{ $review->id }}</span>
@endsection

@section('admin-content')
@php
    $badge = match($review->status) {
        'pending_rating' => 'bg-amber-100 text-amber-800',
        'pending_hr'     => 'bg-blue-100 text-blue-800',
        'approved'       => 'bg-green-100 text-green-800',
        'blocked', 'draft' => 'bg-red-100 text-red-700',
        'rejected'       => 'bg-red-100 text-red-800',
        default          => 'bg-gray-100 text-gray-600',
    };
    $metrics = $review->auto_metrics ?? [];
@endphp

@include('partials.erp.page-header', [
    'title' => $review->employee->name . ' — ' . $review->cycleTypeLabel(),
    'subtitle' => $review->employee->employee_code . ' · ' . $review->period_from->format('d M Y') . ' – ' . $review->period_to->format('d M Y'),
])

<div class="flex flex-wrap items-center justify-end gap-2 mb-4">
    <a href="{{ route('admin.hrm.performance.reviews.index') }}" class="erp-btn-secondary">← Back</a>
</div>

@if($review->blocked_reason)
    <div class="erp-panel mb-4 border-amber-200 bg-amber-50">
        <div class="erp-panel-body text-sm text-amber-800">{{ $review->blocked_reason }}</div>
    </div>
@endif

@if($review->manual_fallback)
    <div class="erp-panel mb-4 border-orange-200 bg-orange-50">
        <div class="erp-panel-body text-sm text-orange-800">No attendance data for this period — manual fallback required for auto criteria.</div>
    </div>
@endif

<div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
    <div class="lg:col-span-2 space-y-4">
        <div class="erp-panel">
            <div class="erp-panel-head flex justify-between">
                <h2 class="text-xs font-semibold text-gray-700 uppercase">Review Summary</h2>
                <span class="erp-badge {{ $badge }}">{{ $review->statusLabel() }}</span>
            </div>
            <div class="erp-panel-body grid grid-cols-2 md:grid-cols-3 gap-4 text-sm">
                <div><p class="text-[10px] text-gray-400 uppercase">Overall Score</p><p class="text-2xl font-bold {{ $review->overall_score !== null && $review->overall_score >= $minimumPass ? 'text-green-600' : 'text-gray-800' }}">{{ $review->overall_score !== null ? number_format($review->overall_score, 1) . '%' : '—' }}</p></div>
                <div><p class="text-[10px] text-gray-400 uppercase">Reporting To</p><p>{{ $review->reportingTo?->name ?? '—' }}</p></div>
                <div><p class="text-[10px] text-gray-400 uppercase">Factory</p><p>{{ $review->employee->factory?->name ?? '—' }}</p></div>
                <div><p class="text-[10px] text-gray-400 uppercase">Designation</p><p>{{ $review->employee->designation?->name ?? '—' }}</p></div>
                <div><p class="text-[10px] text-gray-400 uppercase">Cycle</p><p><a href="{{ route('admin.hrm.performance.cycles.show', $review->cycle) }}" class="text-brand">{{ $review->cycle?->name }}</a></p></div>
                <div><p class="text-[10px] text-gray-400 uppercase">Min Pass</p><p>{{ $minimumPass }}%</p></div>
            </div>
        </div>

        @if(!empty($metrics))
            <div class="erp-panel">
                <div class="erp-panel-head"><h2 class="text-xs font-semibold text-gray-700 uppercase">Auto Metrics</h2></div>
                <div class="erp-panel-body grid grid-cols-2 md:grid-cols-4 gap-3 text-sm">
                    <div><p class="text-[10px] text-gray-400">Working Days</p><p class="font-medium">{{ $metrics['working_days'] ?? '—' }}</p></div>
                    <div><p class="text-[10px] text-gray-400">Present</p><p class="font-medium">{{ $metrics['present_days'] ?? '—' }}</p></div>
                    <div><p class="text-[10px] text-gray-400">Late Days</p><p class="font-medium">{{ $metrics['late_days'] ?? '—' }}</p></div>
                    <div><p class="text-[10px] text-gray-400">Leave (paid)</p><p class="font-medium">{{ $metrics['leave_days'] ?? '—' }}</p></div>
                    @if($canManage && !$review->isApproved())
                        <div class="col-span-full">
                            <form method="POST" action="{{ route('admin.hrm.performance.reviews.recalculate', $review) }}">@csrf
                                <button type="submit" class="erp-btn-secondary !py-1 !px-3 text-xs">Recalculate Auto Metrics</button>
                            </form>
                        </div>
                    @endif
                </div>
            </div>
        @endif

        <div class="erp-panel">
            <div class="erp-panel-head"><h2 class="text-xs font-semibold text-gray-700 uppercase">Score Breakdown</h2></div>
            <div class="overflow-x-auto">
                <table class="erp-table text-sm">
                    <thead><tr><th>Criterion</th><th>Type</th><th>Weight</th><th>Score</th><th>Weighted</th></tr></thead>
                    <tbody>
                        @foreach($review->scores as $score)
                            <tr>
                                <td>{{ $score->label }}</td>
                                <td><span class="erp-badge {{ $score->criterion_type === 'auto' ? 'bg-blue-100 text-blue-700' : 'bg-purple-100 text-purple-700' }} text-[10px]">{{ ucfirst($score->criterion_type) }}{{ $score->is_auto ? '' : ' (manual)' }}</span></td>
                                <td>{{ number_format($score->weight, 0) }}%</td>
                                <td>{{ $score->score !== null ? number_format($score->score, 1) : '—' }}</td>
                                <td>{{ $score->score !== null ? number_format($score->weightedContribution(), 2) : '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        @if($canRate)
            <div class="erp-panel">
                <div class="erp-panel-head"><h2 class="text-xs font-semibold text-gray-700 uppercase">Submit Rating</h2></div>
                <form method="POST" action="{{ route('admin.hrm.performance.reviews.rate', $review) }}" class="erp-panel-body space-y-4">
                    @csrf
                    @foreach($review->scores as $score)
                        @if($score->criterion_type === 'manual' || ($review->manual_fallback && $score->criterion_type === 'auto' && $score->score === null))
                            <div>
                                <label class="erp-form-label">{{ $score->label }} (0–100) *</label>
                                <input type="number" name="scores[{{ $score->code }}]" value="{{ old('scores.'.$score->code, $score->score) }}" class="erp-input w-32" min="0" max="100" step="0.1" required>
                                @error('scores.'.$score->code)<p class="text-red-600 text-xs">{{ $message }}</p>@enderror
                            </div>
                        @endif
                    @endforeach

                    @if($canManage && $review->reportingTo)
                        <div>
                            <label class="erp-form-label">Rate on behalf of (HR proxy)</label>
                            <select name="on_behalf_of_employee_id" class="erp-input">
                                <option value="">— Self / direct entry —</option>
                                <option value="{{ $review->reporting_to_id }}">{{ $review->reportingTo->employee_code }} — {{ $review->reportingTo->name }}</option>
                            </select>
                        </div>
                    @endif

                    @if($review->cycle_type === 'probation_6m')
                        <div>
                            <label class="erp-form-label">Probation Recommendation</label>
                            <textarea name="probation_recommendation" rows="2" class="erp-input" placeholder="Confirm / extend probation / training needed…">{{ old('probation_recommendation') }}</textarea>
                        </div>
                        <label class="flex items-center gap-2 text-sm">
                            <input type="checkbox" name="apply_confirmation" value="1" {{ old('apply_confirmation') ? 'checked' : '' }}>
                            Suggest confirmation on HR approval (if score ≥ {{ $minimumPass }}%)
                        </label>
                    @endif

                    <div>
                        <label class="erp-form-label">Rating Notes</label>
                        <textarea name="rating_notes" rows="2" class="erp-input">{{ old('rating_notes') }}</textarea>
                    </div>

                    <button type="submit" class="erp-btn-primary">Submit for HR Approval</button>
                </form>
            </div>
        @endif

        @if($review->probation_recommendation)
            <div class="erp-panel">
                <div class="erp-panel-head"><h2 class="text-xs font-semibold text-gray-700 uppercase">Probation Recommendation</h2></div>
                <div class="erp-panel-body text-sm">{{ $review->probation_recommendation }}</div>
            </div>
        @endif
    </div>

    <div class="space-y-4">
        @if(in_array($review->status, ['draft', 'blocked']) && $canManage)
            <div class="erp-panel">
                <div class="erp-panel-head"><h2 class="text-xs font-semibold text-gray-700 uppercase">Assign Reporting Person</h2></div>
                <form method="POST" action="{{ route('admin.hrm.performance.reviews.assign-reporting', $review) }}" class="erp-panel-body space-y-3">
                    @csrf
                    <select name="reporting_to_id" class="erp-input !text-xs" required>
                        <option value="">Select reporting person</option>
                        @foreach($reportingOptions as $id => $label)
                            <option value="{{ $id }}" {{ $review->reporting_to_id == $id ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                    <button type="submit" class="erp-btn-primary w-full !py-2 text-xs">Assign & Enable Rating</button>
                </form>
            </div>
        @endif

        @if($canApprove)
            <div class="erp-panel">
                <div class="erp-panel-head"><h2 class="text-xs font-semibold text-gray-700 uppercase">HR Approval</h2></div>
                <div class="erp-panel-body space-y-3">
                    @if($review->apply_confirmation && $review->passedMinimumScore())
                        <p class="text-xs text-green-700 bg-green-50 p-2 rounded">Confirmation will be applied on approval.</p>
                    @elseif($review->cycle_type === 'probation_6m' && !$review->passedMinimumScore())
                        <p class="text-xs text-amber-700 bg-amber-50 p-2 rounded">Score below {{ $minimumPass }}% — recommendation only, no auto confirmation.</p>
                    @endif
                    <form method="POST" action="{{ route('admin.hrm.performance.reviews.approve', $review) }}">@csrf
                        <button type="submit" class="erp-btn-primary w-full !py-2 text-xs">Approve Review</button>
                    </form>
                    <form method="POST" action="{{ route('admin.hrm.performance.reviews.reject', $review) }}" class="space-y-2">
                        @csrf
                        <textarea name="hr_rejection_reason" rows="2" class="erp-input !text-xs" placeholder="Rejection reason…" required></textarea>
                        <button type="submit" class="erp-btn-secondary w-full !py-2 text-xs text-red-600">Reject</button>
                    </form>
                </div>
            </div>
        @endif

        @if($review->rated_at)
            <div class="erp-panel">
                <div class="erp-panel-head"><h2 class="text-xs font-semibold text-gray-700 uppercase">Rating Audit</h2></div>
                <div class="erp-panel-body text-xs space-y-2 text-gray-600">
                    <p>Rated by: {{ $review->ratedByUser?->name ?? '—' }}</p>
                    @if($review->ratedOnBehalfOf)<p>On behalf of: {{ $review->ratedOnBehalfOf->name }}</p>@endif
                    <p>At: @portalDateTime($review->rated_at)</p>
                </div>
            </div>
        @endif

        @if($canManage && !$review->isApproved())
            <div class="erp-panel border-red-100">
                <div class="erp-panel-head"><h2 class="text-xs font-semibold text-red-700 uppercase">Danger Zone</h2></div>
                <div class="erp-panel-body">
                    <form method="POST" action="{{ route('admin.hrm.performance.reviews.cancel', $review) }}" data-confirm="Cancel this review?">
                        @csrf @method('DELETE')
                        <button type="submit" class="erp-btn-secondary w-full !py-2 text-xs !text-red-600 !border-red-200 hover:!bg-red-50">Cancel Review</button>
                    </form>
                </div>
            </div>
        @endif
    </div>
</div>
@endsection
