<?php

namespace App\Services\Hrm;

use App\Models\Hrm\RecruitmentApplication;
use App\Models\Hrm\RecruitmentOfferLetter;
use App\Models\User;
use Illuminate\Support\Str;

class RecruitmentOfferService
{
    public function render(RecruitmentApplication $application, array $overrides = []): string
    {
        $application->loadMissing(['jobPosting.department', 'jobPosting.designation', 'factory']);

        $offeredSalary = $overrides['offered_salary'] ?? $application->expected_salary;
        $joiningDate = $overrides['joining_date'] ?? now()->addDays(7)->toDateString();

        $replacements = [
            '{{date}}'             => now()->format('d M Y'),
            '{{candidate_name}}'   => $application->name,
            '{{application_no}}'   => $application->application_no,
            '{{job_title}}'        => $application->jobPosting?->title ?? '',
            '{{factory_name}}'     => $application->factory?->name ?? '',
            '{{department}}'       => $application->jobPosting?->department?->name ?? '',
            '{{designation}}'      => $application->jobPosting?->designation?->name ?? $application->jobPosting?->title ?? '',
            '{{present_address}}'  => $application->present_address ?? '',
            '{{phone}}'            => $application->phone ?? '',
            '{{offered_salary}}'   => $offeredSalary
                ? 'Tk. ' . number_format((float) $offeredSalary, 0) . ' (Monthly)'
                : ($application->jobPosting?->salaryDisplay() ?? 'As per company policy'),
            '{{joining_date}}'     => \Carbon\Carbon::parse($joiningDate)->format('d M Y'),
        ];

        $template = config('hrm.recruitment_offer_template', '');

        return str_replace(array_keys($replacements), array_values($replacements), $template);
    }

    public function issue(
        RecruitmentApplication $application,
        User $issuer,
        array $data,
        RecruitmentService $recruitmentService,
    ): RecruitmentOfferLetter {
        $content = $this->render($application, [
            'offered_salary' => $data['offered_salary'] ?? null,
            'joining_date'   => $data['joining_date'] ?? null,
        ]);

        do {
            $referenceNo = 'OFR-' . strtoupper(Str::random(8));
        } while (RecruitmentOfferLetter::where('reference_no', $referenceNo)->exists());

        $letter = RecruitmentOfferLetter::create([
            'application_id' => $application->id,
            'reference_no'   => $referenceNo,
            'content'        => $content,
            'offered_salary' => $data['offered_salary'] ?? null,
            'joining_date'   => $data['joining_date'] ?? null,
            'notes'          => $data['notes'] ?? null,
            'issued_by'      => $issuer->id,
            'issued_at'      => now(),
        ]);

        if (! in_array($application->status, ['offered', 'hired'], true) && ! $application->isTerminal()) {
            $recruitmentService->updateStatus(
                $application,
                'offered',
                $issuer,
                'Offer letter issued.',
                notifyCandidate: true,
            );
        }

        return $letter->load(['application.jobPosting', 'application.factory', 'issuer']);
    }
}
