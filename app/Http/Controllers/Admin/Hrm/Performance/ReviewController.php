<?php

namespace App\Http\Controllers\Admin\Hrm\Performance;

use App\Http\Controllers\Admin\Hrm\Concerns\ScopesHrmFactory;
use App\Http\Controllers\Controller;
use App\Models\Hrm\Employee;
use App\Models\Hrm\PerformanceCycle;
use App\Models\Hrm\PerformanceReview;
use App\Services\Hrm\PerformanceReviewService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReviewController extends Controller
{
    use ScopesHrmFactory;

    public function __construct(private PerformanceReviewService $service) {}

    public function index(Request $request)
    {
        $this->ensureCanView($request);

        $query = PerformanceReview::query()
            ->with(['employee', 'cycle', 'reportingTo'])
            ->latest('id');

        $this->scopeToUserFactory($query, $request);

        if ($request->filled('factory_id')) {
            $query->where('factory_id', $request->factory_id);
        }

        if ($request->filled('cycle_id')) {
            $query->where('cycle_id', $request->cycle_id);
        }

        if ($request->filled('cycle_type')) {
            $query->where('cycle_type', $request->cycle_type);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->boolean('pending_rating')) {
            $query->where('status', 'pending_rating');
        }

        if ($request->boolean('pending_hr')) {
            $query->where('status', 'pending_hr');
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('employee', fn ($q) => $q
                ->where('name', 'like', "%{$search}%")
                ->orWhere('employee_code', 'like', "%{$search}%"));
        }

        $statsQuery = PerformanceReview::query();
        $this->scopeToUserFactory($statsQuery, $request);

        return view('admin.hrm.performance.reviews.index', [
            'reviews'           => $query->paginate(20)->withQueryString(),
            'factories'         => $this->factoryOptions($request),
            'scopedFactoryName' => $this->scopedFactoryName($request),
            'cycleTypes'        => PerformanceCycle::CYCLE_TYPES,
            'statuses'          => PerformanceReview::STATUSES,
            'filters'           => $request->only(['factory_id', 'cycle_id', 'cycle_type', 'status', 'search', 'pending_rating', 'pending_hr']),
            'pendingRating'     => (clone $statsQuery)->where('status', 'pending_rating')->count(),
            'pendingHr'         => (clone $statsQuery)->where('status', 'pending_hr')->count(),
            'canRate'           => $request->user()?->canRatePerformance() ?? false,
            'canManage'         => $request->user()?->hasPermission('hrm.performance.manage') ?? false,
            'canApprove'        => $request->user()?->canApprovePerformance() ?? false,
        ]);
    }

    public function show(Request $request, PerformanceReview $review)
    {
        $this->ensureCanView($request);
        $this->authorizeFactoryAccess($request, $review->factory_id);

        $review->load([
            'employee.factory', 'employee.designation', 'cycle', 'template.criteria',
            'scores', 'reportingTo', 'ratedByUser', 'ratedOnBehalfOf',
            'hrApprovedByUser', 'hrRejectedByUser', 'createdByUser',
        ]);

        return view('admin.hrm.performance.reviews.show', [
            'review'      => $review,
            'canRate'     => $request->user()?->canRatePerformance() && $review->isPendingRating(),
            'canManage'   => $request->user()?->hasPermission('hrm.performance.manage') ?? false,
            'canApprove'  => $request->user()?->canApprovePerformance() && $review->isPendingHr(),
            'reportingOptions' => $this->reportingOptions($request),
            'minimumPass' => config('hrm.performance.minimum_pass_score', 60),
        ]);
    }

    public function assignReporting(Request $request, PerformanceReview $review)
    {
        $this->ensureCanManage($request);
        $this->authorizeFactoryAccess($request, $review->factory_id);

        $validated = $request->validate([
            'reporting_to_id' => ['required', 'exists:hrm_employees,id'],
        ]);

        $this->service->assignReportingPerson($review, (int) $validated['reporting_to_id']);

        return back()->with('success', 'Reporting person assigned. Review is ready for rating.');
    }

    public function rate(Request $request, PerformanceReview $review)
    {
        if (! $request->user()?->canRatePerformance()) {
            abort(403, 'You do not have permission to rate performance reviews.');
        }

        $this->authorizeFactoryAccess($request, $review->factory_id);

        $validated = $request->validate([
            'scores'                    => ['required', 'array'],
            'scores.*'                  => ['nullable', 'numeric', 'min:0', 'max:100'],
            'on_behalf_of_employee_id'  => ['nullable', 'exists:hrm_employees,id'],
            'rating_notes'              => ['nullable', 'string', 'max:5000'],
            'probation_recommendation'  => ['nullable', 'string', 'max:5000'],
            'apply_confirmation'        => ['nullable', 'boolean'],
        ]);

        $this->service->submitRating(
            $review,
            $validated['scores'],
            $request->user(),
            $validated['on_behalf_of_employee_id'] ?? null,
            $validated['rating_notes'] ?? null,
            $validated['probation_recommendation'] ?? null,
            $request->boolean('apply_confirmation'),
        );

        return redirect()->route('admin.hrm.performance.reviews.show', $review)
            ->with('success', 'Rating submitted for HR approval.');
    }

    public function approve(Request $request, PerformanceReview $review)
    {
        if (! $request->user()?->canApprovePerformance()) {
            abort(403, 'You do not have permission to approve performance reviews.');
        }

        $this->authorizeFactoryAccess($request, $review->factory_id);

        $this->service->approve($review, $request->user());

        return back()->with('success', 'Review approved and recorded in service history.');
    }

    public function reject(Request $request, PerformanceReview $review)
    {
        if (! $request->user()?->canApprovePerformance()) {
            abort(403, 'You do not have permission to reject performance reviews.');
        }

        $this->authorizeFactoryAccess($request, $review->factory_id);

        $validated = $request->validate([
            'hr_rejection_reason' => ['required', 'string', 'max:2000'],
        ]);

        $this->service->reject($review, $request->user(), $validated['hr_rejection_reason']);

        return back()->with('success', 'Review rejected.');
    }

    public function cancel(Request $request, PerformanceReview $review)
    {
        $this->ensureCanManage($request);
        $this->authorizeFactoryAccess($request, $review->factory_id);

        $this->service->cancel($review);

        return back()->with('success', 'Review cancelled.');
    }

    public function recalculate(Request $request, PerformanceReview $review)
    {
        $this->ensureCanManage($request);
        $this->authorizeFactoryAccess($request, $review->factory_id);

        $this->service->recalculateAutoScores($review);

        return back()->with('success', 'Auto metrics recalculated.');
    }

    public function export(Request $request): StreamedResponse
    {
        $this->ensureCanView($request);

        $query = PerformanceReview::query()->with(['employee', 'cycle'])->latest('id');
        $this->scopeToUserFactory($query, $request);

        if ($request->filled('factory_id')) {
            $query->where('factory_id', $request->factory_id);
        }

        if ($request->filled('cycle_id')) {
            $query->where('cycle_id', $request->cycle_id);
        }

        $filename = 'performance-reviews-' . now()->format('Y-m-d') . '.csv';

        return response()->streamDownload(function () use ($query) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Code', 'Name', 'Cycle Type', 'Period From', 'Period To', 'Score', 'Status']);

            $query->chunk(200, function ($rows) use ($handle) {
                foreach ($rows as $row) {
                    fputcsv($handle, [
                        $row->employee?->employee_code,
                        $row->employee?->name,
                        $row->cycleTypeLabel(),
                        $row->period_from->format('Y-m-d'),
                        $row->period_to->format('Y-m-d'),
                        $row->overall_score,
                        $row->statusLabel(),
                    ]);
                }
            });

            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    /** @return array<int, string> */
    private function reportingOptions(Request $request): array
    {
        $query = Employee::query()
            ->whereIn('status', ['active', 'probation'])
            ->orderBy('name');

        $this->scopeToUserFactory($query, $request);

        return $query->get(['id', 'employee_code', 'name'])
            ->mapWithKeys(fn (Employee $e) => [$e->id => $e->employee_code . ' — ' . $e->name])
            ->all();
    }

    private function ensureCanView(Request $request): void
    {
        if (! $request->user()?->hasPermission('hrm.performance.view')) {
            abort(403, 'You do not have permission to view performance reviews.');
        }
    }

    private function ensureCanManage(Request $request): void
    {
        if (! $request->user()?->hasPermission('hrm.performance.manage')) {
            abort(403, 'You do not have permission to manage performance reviews.');
        }
    }
}
