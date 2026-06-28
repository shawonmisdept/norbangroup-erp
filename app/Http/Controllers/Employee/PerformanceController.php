<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\Hrm\PerformanceReview;
use Illuminate\Http\Request;

class PerformanceController extends Controller
{
    public function index(Request $request)
    {
        $employee = $request->user('employee')->employee;

        $reviews = PerformanceReview::query()
            ->with(['cycle', 'scores'])
            ->where('employee_id', $employee->id)
            ->whereNotIn('status', ['draft', 'blocked', 'cancelled'])
            ->latest('id')
            ->paginate(12);

        $latestApproved = PerformanceReview::query()
            ->where('employee_id', $employee->id)
            ->where('status', 'approved')
            ->latest('hr_approved_at')
            ->first();

        return view('employee.performance.index', compact('employee', 'reviews', 'latestApproved'));
    }

    public function show(Request $request, PerformanceReview $review)
    {
        [$employee, $review] = $this->resolveReview($request, $review);

        $minimumPass = (float) config('hrm.performance.minimum_pass_score', 60);
        $showScores = in_array($review->status, ['approved', 'rejected'], true);

        return view('employee.performance.show', compact('employee', 'review', 'minimumPass', 'showScores'));
    }

    /** @return array{0: \App\Models\Hrm\Employee, 1: PerformanceReview} */
    private function resolveReview(Request $request, PerformanceReview $review): array
    {
        $employee = $request->user('employee')->employee;

        if ($review->employee_id !== $employee->id) {
            abort(403);
        }

        if (in_array($review->status, ['draft', 'blocked', 'cancelled'], true)) {
            abort(404);
        }

        $review->load(['cycle', 'scores', 'reportingTo', 'employee.designation']);

        return [$employee, $review];
    }
}
