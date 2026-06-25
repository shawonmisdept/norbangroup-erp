<?php

namespace App\Services\Hrm;

use App\Models\Hrm\Employee;
use App\Models\Hrm\JobPosting;
use App\Models\Hrm\RecruitmentApplication;
use App\Models\Hrm\RecruitmentApplicationLog;
use App\Models\Hrm\RecruitmentInterview;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class RecruitmentService
{
    public function __construct(
        private HrmNotificationService $notifications,
        private RecruitmentMessagingService $messaging,
    ) {}

    public function generateApplicationNo(): string
    {
        $year = now()->format('Y');

        do {
            $no = 'APP-' . $year . '-' . str_pad((string) random_int(1, 99999), 5, '0', STR_PAD_LEFT);
        } while (RecruitmentApplication::where('application_no', $no)->exists());

        return $no;
    }

    public function submitApplication(
        JobPosting $posting,
        array $data,
        string $source = 'online',
        ?User $reviewer = null,
        ?UploadedFile $photo = null,
        ?UploadedFile $nidDocument = null,
        bool $phoneVerified = false,
    ): RecruitmentApplication {
        if (! $posting->isOpen() && $source === 'online') {
            throw ValidationException::withMessages([
                'job_posting_id' => 'This job posting is no longer accepting applications.',
            ]);
        }

        $this->assertNoDuplicateActiveApplication($posting->id, $data['phone']);

        $application = DB::transaction(function () use ($posting, $data, $source, $reviewer, $photo, $nidDocument, $phoneVerified) {
            $application = RecruitmentApplication::create([
                'application_no'      => $this->generateApplicationNo(),
                'job_posting_id'      => $posting->id,
                'factory_id'          => $posting->factory_id,
                'source'              => $source,
                'status'              => 'applied',
                'name'                => $data['name'],
                'phone'               => $this->normalizePhone($data['phone']),
                'phone_verified_at'   => $phoneVerified ? now() : null,
                'email'               => $data['email'] ?? null,
                'gender'              => $data['gender'] ?? null,
                'date_of_birth'       => $data['date_of_birth'] ?? null,
                'nid_number'          => $data['nid_number'] ?? null,
                'present_address'     => $data['present_address'] ?? null,
                'permanent_address'   => $data['permanent_address'] ?? null,
                'photo_path'          => $photo?->store('hrm/recruitment/photos', 'public'),
                'nid_document_path'   => $nidDocument?->store('hrm/recruitment/documents', 'public'),
                'education_history'   => $this->cleanHistoryRows($data['education_history'] ?? [], [
                    'degree', 'institution', 'board_or_university', 'passing_year', 'result',
                ]),
                'employment_history'  => $this->cleanHistoryRows($data['employment_history'] ?? [], [
                    'company_name', 'designation', 'department', 'joining_date', 'leaving_date', 'reason_for_leaving',
                ]),
                'expected_salary'     => $data['expected_salary'] ?? null,
                'referral_source'     => $data['referral_source'] ?? null,
                'notes'               => $data['notes'] ?? null,
                'reviewed_by'         => $reviewer?->id,
                'reviewed_at'         => $reviewer ? now() : null,
                'applied_at'          => now(),
            ]);

            RecruitmentApplicationLog::create([
                'application_id' => $application->id,
                'from_status'    => null,
                'to_status'      => 'applied',
                'notes'          => $source === 'online' ? 'Submitted via careers portal' : 'Created by HR',
                'user_id'        => $reviewer?->id,
            ]);

            return $application->load(['jobPosting', 'factory']);
        });

        if ($source === 'online') {
            $this->notifications->recruitmentApplicationSubmitted($application);
            $this->messaging->applicationReceived($application);
        }

        return $application;
    }

    public function scheduleInterview(
        RecruitmentApplication $application,
        array $data,
        User $user,
    ): RecruitmentInterview {
        $interview = RecruitmentInterview::create([
            'application_id' => $application->id,
            'scheduled_at'   => $data['scheduled_at'],
            'location'       => $data['location'] ?? null,
            'interview_type' => $data['interview_type'] ?? 'in_person',
            'result'         => 'pending',
            'panel_notes'    => $data['panel_notes'] ?? null,
            'scheduled_by'   => $user->id,
        ]);

        if (in_array($application->status, ['applied', 'screening'], true)) {
            $this->updateStatus($application, 'interview', $user, 'Interview scheduled.', notifyCandidate: false);
        }

        $this->messaging->interviewScheduled($interview);

        return $interview->load(['scheduler']);
    }

    public function completeInterview(
        RecruitmentInterview $interview,
        array $data,
        User $user,
    ): RecruitmentInterview {
        $interview->update([
            'result'       => $data['result'],
            'score'        => $data['score'] ?? null,
            'panel_notes'  => $data['panel_notes'] ?? $interview->panel_notes,
            'completed_at' => now(),
        ]);

        $application = $interview->application;

        if ($data['result'] === 'passed' && in_array($application->status, ['interview'], true)) {
            $this->updateStatus($application, 'selected', $user, 'Interview passed.');
        } elseif ($data['result'] === 'failed' && ! $application->isTerminal()) {
            $this->updateStatus($application, 'rejected', $user, null, 'Did not pass interview.');
        }

        return $interview->fresh(['scheduler', 'application']);
    }

    public function updateStatus(
        RecruitmentApplication $application,
        string $newStatus,
        User $user,
        ?string $notes = null,
        ?string $rejectionReason = null,
        bool $notifyCandidate = true,
    ): RecruitmentApplication {
        $allowed = array_keys(config('hrm.recruitment_statuses', []));

        if (! in_array($newStatus, $allowed, true)) {
            throw ValidationException::withMessages(['status' => 'Invalid status.']);
        }

        if ($application->isTerminal() && $application->status !== $newStatus) {
            throw ValidationException::withMessages(['status' => 'This application is already closed.']);
        }

        $from = $application->status;

        return DB::transaction(function () use ($application, $newStatus, $from, $user, $notes, $rejectionReason, $notifyCandidate) {
            $application->update([
                'status'           => $newStatus,
                'rejection_reason' => $newStatus === 'rejected' ? $rejectionReason : $application->rejection_reason,
                'reviewed_by'      => $user->id,
                'reviewed_at'      => now(),
            ]);

            if ($newStatus === 'hired' && $application->converted_employee_id) {
                $posting = $application->jobPosting;
                if ($posting) {
                    $posting->increment('openings_filled');
                    $posting->refreshAvailability();
                }
            }

            RecruitmentApplicationLog::create([
                'application_id' => $application->id,
                'from_status'                => $from,
                'to_status'                  => $newStatus,
                'notes'                      => $notes,
                'user_id'                    => $user->id,
            ]);

            $updated = $application->fresh(['jobPosting', 'factory', 'logs.user']);

            if ($notifyCandidate) {
                $this->messaging->statusUpdated($updated, $from, $notes);
            }

            return $updated;
        });
    }

    public function markConverted(RecruitmentApplication $application, Employee $employee, User $user): RecruitmentApplication
    {
        return DB::transaction(function () use ($application, $employee, $user) {
            if ($application->converted_employee_id) {
                return $application;
            }

            $from = $application->status;

            $application->update([
                'status'                => 'hired',
                'converted_employee_id' => $employee->id,
                'reviewed_by'           => $user->id,
                'reviewed_at'           => now(),
            ]);

            $application->jobPosting?->increment('openings_filled');
            $application->jobPosting?->refreshAvailability();

            RecruitmentApplicationLog::create([
                'application_id' => $application->id,
                'from_status'                => $from,
                'to_status'                  => 'hired',
                'notes'                      => 'Converted to employee ' . $employee->employee_code,
                'user_id'                    => $user->id,
            ]);

            return $application->fresh();
        });
    }

    /** @return array<string, mixed> */
    public function employeePrefillFromApplication(RecruitmentApplication $application): array
    {
        $application->loadMissing(['jobPosting']);

        return [
            'name'               => $application->name,
            'phone'              => $application->phone,
            'email'              => $application->email,
            'gender'             => $application->gender,
            'date_of_birth'      => $application->date_of_birth?->format('Y-m-d'),
            'nid_number'         => $application->nid_number,
            'present_address'    => $application->present_address,
            'permanent_address'  => $application->permanent_address,
            'factory_id'         => $application->factory_id,
            'department_id'      => $application->jobPosting?->department_id,
            'designation_id'     => $application->jobPosting?->designation_id,
            'worker_category_id' => $application->jobPosting?->worker_category_id,
            'status'             => 'probation',
            'joining_date'       => now()->toDateString(),
            'education_history'  => $application->education_history ?? [],
            'employment_history' => $application->employment_history ?? [],
        ];
    }

    /** @return Employee|null Former employee match by NID or phone (info only, no block) */
    public function findFormerEmployee(RecruitmentApplication $application): ?Employee
    {
        $query = Employee::query()
            ->whereIn('status', Employee::SEPARATED_STATUSES);

        if ($application->nid_number) {
            $byNid = (clone $query)->where('nid_number', $application->nid_number)->first();
            if ($byNid) {
                return $byNid;
            }
        }

        if ($application->phone) {
            return (clone $query)->where('phone', $application->phone)->first();
        }

        return null;
    }

    public function trackApplication(string $applicationNo, string $phone): ?RecruitmentApplication
    {
        return RecruitmentApplication::query()
            ->with(['jobPosting', 'factory'])
            ->where('application_no', strtoupper(trim($applicationNo)))
            ->where('phone', $this->normalizePhone($phone))
            ->first();
    }

    /** @return array<int, array<string, mixed>> */
    private function cleanHistoryRows(array $rows, array $fields): array
    {
        $clean = [];

        foreach (array_values($rows) as $row) {
            if (! is_array($row)) {
                continue;
            }

            $hasValue = false;
            foreach ($fields as $field) {
                if (filled($row[$field] ?? null)) {
                    $hasValue = true;
                    break;
                }
            }

            if ($hasValue) {
                $clean[] = array_intersect_key($row, array_flip($fields));
            }
        }

        return $clean;
    }

    private function assertNoDuplicateActiveApplication(int $postingId, string $phone): void
    {
        $exists = RecruitmentApplication::query()
            ->where('job_posting_id', $postingId)
            ->where('phone', $this->normalizePhone($phone))
            ->whereNotIn('status', ['rejected', 'withdrawn'])
            ->exists();

        if ($exists) {
            throw ValidationException::withMessages([
                'phone' => 'An active application already exists for this phone number on this job posting.',
            ]);
        }
    }

    private function normalizePhone(string $phone): string
    {
        return preg_replace('/\s+/', '', trim($phone)) ?? trim($phone);
    }
}
