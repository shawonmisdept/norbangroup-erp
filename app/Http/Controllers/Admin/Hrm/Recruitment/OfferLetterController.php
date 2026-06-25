<?php

namespace App\Http\Controllers\Admin\Hrm\Recruitment;

use App\Http\Controllers\Admin\Hrm\Concerns\ScopesHrmFactory;
use App\Http\Controllers\Controller;
use App\Models\Hrm\RecruitmentApplication;
use App\Models\Hrm\RecruitmentOfferLetter;
use App\Services\Hrm\RecruitmentOfferService;
use App\Services\Hrm\RecruitmentService;
use Illuminate\Http\Request;

class OfferLetterController extends Controller
{
    use ScopesHrmFactory;

    public function __construct(
        private RecruitmentOfferService $offerService,
        private RecruitmentService $recruitmentService,
    ) {}

    public function create(Request $request, RecruitmentApplication $application)
    {
        $this->ensureCanManage($request);
        $this->authorizeFactoryAccess($request, $application->factory_id);

        $application->load(['jobPosting.department', 'jobPosting.designation', 'factory', 'offerLetters']);

        return view('admin.hrm.recruitment.offer-letters.form', [
            'application' => $application,
            'preview'     => $this->offerService->render($application, [
                'offered_salary' => old('offered_salary', $application->expected_salary),
                'joining_date'   => old('joining_date', now()->addDays(7)->toDateString()),
            ]),
        ]);
    }

    public function store(Request $request, RecruitmentApplication $application)
    {
        $this->ensureCanManage($request);
        $this->authorizeFactoryAccess($request, $application->factory_id);

        if ($application->isTerminal()) {
            return back()->with('error', 'Cannot issue offer letter for a closed application.');
        }

        $validated = $request->validate([
            'offered_salary' => ['nullable', 'numeric', 'min:0'],
            'joining_date'   => ['nullable', 'date', 'after_or_equal:today'],
            'notes'          => ['nullable', 'string', 'max:2000'],
        ]);

        $letter = $this->offerService->issue(
            $application,
            $request->user(),
            $validated,
            $this->recruitmentService,
        );

        return redirect()->route('admin.hrm.recruitment.offer-letters.show', $letter)
            ->with('success', 'Offer letter issued.');
    }

    public function show(Request $request, RecruitmentOfferLetter $offerLetter)
    {
        $this->ensureCanView($request);
        $this->authorizeFactoryAccess($request, $offerLetter->application->factory_id);

        $offerLetter->load(['application.jobPosting', 'application.factory', 'issuer']);

        return view('admin.hrm.recruitment.offer-letters.show', [
            'letter'    => $offerLetter,
            'canManage' => $request->user()?->hasPermission('hrm.recruitment.applications.manage') ?? false,
        ]);
    }

    public function print(Request $request, RecruitmentOfferLetter $offerLetter)
    {
        $this->ensureCanView($request);
        $this->authorizeFactoryAccess($request, $offerLetter->application->factory_id);

        $offerLetter->load(['application.jobPosting', 'application.factory', 'issuer']);

        return view('admin.hrm.recruitment.offer-letters.print', [
            'letter' => $offerLetter,
        ]);
    }

    private function ensureCanView(Request $request): void
    {
        if (! $request->user()?->hasPermission('hrm.recruitment.applications.view')) {
            abort(403);
        }
    }

    private function ensureCanManage(Request $request): void
    {
        if (! $request->user()?->hasPermission('hrm.recruitment.applications.manage')) {
            abort(403);
        }
    }
}
