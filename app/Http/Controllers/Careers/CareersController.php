<?php

namespace App\Http\Controllers\Careers;

use App\Http\Controllers\Controller;
use App\Models\AppSetting;
use App\Models\Factory;
use App\Models\Hrm\JobPosting;
use App\Models\Hrm\RecruitmentApplication;
use App\Services\Hrm\RecruitmentOtpService;
use App\Services\Hrm\RecruitmentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CareersController extends Controller
{
    public function __construct(
        private RecruitmentService $service,
        private RecruitmentOtpService $otp,
    ) {}

    public function index(Request $request)
    {
        $query = JobPosting::query()
            ->open()
            ->with(['factory', 'department', 'designation', 'workerCategory'])
            ->latest('published_at');

        if ($request->filled('factory_id')) {
            $query->where('factory_id', $request->factory_id);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        return view('careers.index', [
            'postings'  => $query->paginate(12)->withQueryString(),
            'openCount' => JobPosting::open()->count(),
            'factories' => Factory::where('is_active', true)->orderBy('name')->pluck('name', 'id'),
            'filters'   => $request->only(['factory_id', 'search']),
        ]);
    }

    public function show(JobPosting $posting)
    {
        if (! $posting->isOpen()) {
            abort(404);
        }

        $posting->load(['factory', 'department', 'designation', 'workerCategory']);

        return view('careers.show', compact('posting'));
    }

    public function apply(JobPosting $posting)
    {
        if (! $posting->isOpen()) {
            return redirect()->route('careers.index')
                ->with('error', 'This job is no longer accepting applications.');
        }

        $posting->load(['factory', 'department', 'designation']);

        return view('careers.apply', [
            'posting'         => $posting,
            'application'     => new RecruitmentApplication(),
            'genders'         => config('hrm.employee_options.genders', []),
            'referralSources' => config('hrm.recruitment_referral_sources', []),
            'isPublic'        => true,
            'otpEnabled'      => AppSetting::current()->recruitmentOtpEnabled(),
            'otpSendUrl'      => AppSetting::current()->recruitmentOtpEnabled()
                ? route('careers.otp.send', $posting)
                : null,
        ]);
    }

    public function sendOtp(Request $request, JobPosting $posting): JsonResponse
    {
        if (! AppSetting::current()->recruitmentOtpEnabled()) {
            return response()->json(['message' => 'Phone verification is currently disabled.'], 422);
        }

        if (! $posting->isOpen()) {
            return response()->json(['message' => 'This job is closed.'], 422);
        }

        $validated = $request->validate(['phone' => ['required', 'string', 'max:20']]);
        $result = $this->otp->send($validated['phone']);

        return response()->json($result);
    }

    public function storeApply(Request $request, JobPosting $posting)
    {
        $otpEnabled = AppSetting::current()->recruitmentOtpEnabled();

        $rules = [
            'name'              => ['required', 'string', 'max:200'],
            'phone'             => ['required', 'string', 'max:20'],
            'email'             => ['nullable', 'email', 'max:200'],
            'gender'            => ['nullable', 'in:' . implode(',', array_keys(config('hrm.employee_options.genders', [])))],
            'date_of_birth'     => ['nullable', 'date', 'before:today'],
            'nid_number'        => ['nullable', 'string', 'max:30'],
            'present_address'   => ['nullable', 'string', 'max:2000'],
            'permanent_address' => ['nullable', 'string', 'max:2000'],
            'photo'             => ['nullable', 'image', 'max:2048'],
            'nid_document'      => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
            'education_history' => ['nullable', 'array'],
            'employment_history'=> ['nullable', 'array'],
            'expected_salary'   => ['nullable', 'numeric', 'min:0'],
            'referral_source'   => ['nullable', 'string', 'max:100'],
        ];

        if ($otpEnabled) {
            $rules['otp'] = ['required', 'string', 'size:6'];
        }

        $validated = $request->validate($rules);

        $phoneVerified = false;

        if ($otpEnabled) {
            $this->otp->verify($validated['phone'], $validated['otp']);
            unset($validated['otp']);
            $phoneVerified = true;
        }

        $application = $this->service->submitApplication(
            $posting,
            $validated,
            'online',
            null,
            $request->file('photo'),
            $request->file('nid_document'),
            $phoneVerified,
        );

        return redirect()->route('careers.success', $application);
    }

    public function success(RecruitmentApplication $application)
    {
        return view('careers.success', compact('application'));
    }

    public function trackForm()
    {
        return view('careers.track');
    }

    public function track(Request $request)
    {
        $validated = $request->validate([
            'application_no' => ['required', 'string', 'max:30'],
            'phone'          => ['required', 'string', 'max:20'],
        ]);

        $application = $this->service->trackApplication(
            $validated['application_no'],
            $validated['phone'],
        );

        if (! $application) {
            return back()
                ->withInput()
                ->withErrors(['application_no' => 'No application found with these details.']);
        }

        $application->load(['jobPosting', 'factory', 'interviews']);
        $upcomingInterview = $application->upcomingInterview();

        return view('careers.track-result', compact('application', 'upcomingInterview'));
    }
}
