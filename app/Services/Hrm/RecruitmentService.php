<?php

namespace App\Services\Hrm;

use App\Models\Hrm\Employee;
use App\Models\Hrm\JobPosting;
use App\Models\Hrm\RecruitmentApplication;
use App\Models\Hrm\RecruitmentApplicationLog;
use App\Models\Hrm\RecruitmentInterview;
use App\Models\Hrm\RecruitmentOfferLetter;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class RecruitmentService
{
    public function __construct(
        private HrmNotificationService $notifications,
        private RecruitmentMessagingService $messaging,
        private JobPostingService $postings,
        private EmployeeServiceHistoryService $serviceHistory,
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
        ?UploadedFile $cv = null,
        bool $phoneVerified = false,
    ): RecruitmentApplication {
        if (! $this->postings->acceptsApplications($posting, $source)) {
            throw ValidationException::withMessages([
                'job_posting_id' => 'This job posting is no longer accepting applications.',
            ]);
        }

        $this->assertNoDuplicateActiveApplication($posting->id, $data['phone']);

        $application = DB::transaction(function () use ($posting, $data, $source, $reviewer, $photo, $nidDocument, $cv, $phoneVerified) {
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
                'cv_path'             => $cv?->store('hrm/recruitment/cv', 'public'),
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

    public function updateApplication(
        RecruitmentApplication $application,
        JobPosting $posting,
        array $data,
        User $user,
        ?UploadedFile $photo = null,
        ?UploadedFile $nidDocument = null,
        ?UploadedFile $cv = null,
    ): RecruitmentApplication {
        if (! $application->canEdit()) {
            throw ValidationException::withMessages([
                'application' => 'This application is linked to an employee and cannot be edited.',
            ]);
        }

        $this->assertNoDuplicateActiveApplication($posting->id, $data['phone'], $application->id);

        return DB::transaction(function () use ($application, $posting, $data, $user, $photo, $nidDocument, $cv) {
            $updates = [
                'job_posting_id'     => $posting->id,
                'factory_id'         => $posting->factory_id,
                'source'             => $data['source'] ?? $application->source,
                'name'               => $data['name'],
                'phone'              => $this->normalizePhone($data['phone']),
                'email'              => $data['email'] ?? null,
                'gender'             => $data['gender'] ?? null,
                'date_of_birth'      => $data['date_of_birth'] ?? null,
                'nid_number'         => $data['nid_number'] ?? null,
                'present_address'    => $data['present_address'] ?? null,
                'permanent_address'  => $data['permanent_address'] ?? null,
                'education_history'  => $this->cleanHistoryRows($data['education_history'] ?? [], [
                    'degree', 'institution', 'board_or_university', 'passing_year', 'result',
                ]),
                'employment_history' => $this->cleanHistoryRows($data['employment_history'] ?? [], [
                    'company_name', 'designation', 'department', 'joining_date', 'leaving_date', 'reason_for_leaving',
                ]),
                'expected_salary'    => $data['expected_salary'] ?? null,
                'referral_source'    => $data['referral_source'] ?? null,
                'notes'              => $data['notes'] ?? null,
                'reviewed_by'        => $user->id,
                'reviewed_at'        => now(),
            ];

            if ($photo) {
                $this->deleteApplicationFile($application->photo_path);
                $updates['photo_path'] = $photo->store('hrm/recruitment/photos', 'public');
            }

            if ($nidDocument) {
                $this->deleteApplicationFile($application->nid_document_path);
                $updates['nid_document_path'] = $nidDocument->store('hrm/recruitment/documents', 'public');
            }

            if ($cv) {
                $this->deleteApplicationFile($application->cv_path);
                $updates['cv_path'] = $cv->store('hrm/recruitment/cv', 'public');
            }

            $application->update($updates);

            RecruitmentApplicationLog::create([
                'application_id' => $application->id,
                'from_status'    => $application->status,
                'to_status'      => $application->status,
                'notes'          => 'Application details updated by HR.',
                'user_id'        => $user->id,
            ]);

            return $application->fresh(['jobPosting', 'factory']);
        });
    }

    public function deleteApplication(RecruitmentApplication $application): void
    {
        if (! $application->canDelete()) {
            throw ValidationException::withMessages([
                'application' => 'This application is linked to an employee and cannot be deleted.',
            ]);
        }

        DB::transaction(function () use ($application) {
            if ($application->status === 'hired' && $application->jobPosting) {
                $posting = $application->jobPosting;
                if ($posting->openings_filled > 0) {
                    $posting->decrement('openings_filled');
                    $posting->refreshAvailability();
                }
            }

            foreach (['photo_path', 'nid_document_path', 'cv_path'] as $field) {
                $this->deleteApplicationFile($application->{$field});
            }

            $application->delete();
        });
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

        if ($newStatus === 'hired' && ! $application->converted_employee_id) {
            return DB::transaction(function () use ($application, $from, $user, $notes, $rejectionReason, $notifyCandidate) {
                $employee = $this->createEmployeeFromApplication($application, $user);
                $this->linkConvertedEmployee(
                    $application,
                    $employee,
                    $user,
                    $from,
                    $notes ?? ('Auto-enrolled as employee ' . $employee->employee_code),
                );

                $updated = $application->fresh(['jobPosting', 'factory', 'logs.user', 'convertedEmployee']);

                if ($notifyCandidate) {
                    $this->messaging->statusUpdated($updated, $from, $notes);
                }

                return $updated;
            });
        }

        return DB::transaction(function () use ($application, $newStatus, $from, $user, $notes, $rejectionReason, $notifyCandidate) {
            $application->update([
                'status'           => $newStatus,
                'rejection_reason' => $newStatus === 'rejected' ? $rejectionReason : $application->rejection_reason,
                'reviewed_by'      => $user->id,
                'reviewed_at'      => now(),
            ]);

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

            $this->linkConvertedEmployee(
                $application,
                $employee,
                $user,
                $from,
                'Converted to employee ' . $employee->employee_code,
            );

            return $application->fresh(['convertedEmployee']);
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
            'joining_date'       => $this->resolveJoiningDate($application),
            'education_history'  => $application->education_history ?? [],
            'employment_history' => $application->employment_history ?? [],
        ];
    }

    public function createEmployeeFromApplication(RecruitmentApplication $application, User $user): Employee
    {
        if (! $user->hasPermission('hrm.employees.manage')) {
            throw ValidationException::withMessages([
                'status' => 'Employee manage permission is required to enroll on Hired status.',
            ]);
        }

        $application->loadMissing(['jobPosting', 'offerLetters']);

        $this->assertNoActiveEmployeeConflict($application);

        $prefill = $this->employeePrefillFromApplication($application);
        $educationHistory = $prefill['education_history'] ?? [];
        $employmentHistory = $prefill['employment_history'] ?? [];
        unset($prefill['education_history'], $prefill['employment_history']);

        $prefill['employee_code'] = $this->generateEmployeeCode($application);
        $prefill['photo'] = $this->copyApplicationFile($application->photo_path, 'photos');
        $prefill['nid_document'] = $this->copyApplicationFile($application->nid_document_path, 'nid');

        $employee = Employee::create($prefill);
        $this->serviceHistory->recordEnrollment($employee);
        $this->syncEducationHistories($employee, $educationHistory);
        $this->syncEmploymentHistories($employee, $employmentHistory);

        return $employee->fresh();
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
            ->with(['jobPosting', 'factory', 'offerLetters'])
            ->where('application_no', strtoupper(trim($applicationNo)))
            ->where('phone', $this->normalizePhone($phone))
            ->first();
    }

    public function respondToOffer(
        RecruitmentApplication $application,
        RecruitmentOfferLetter $offerLetter,
        string $response,
        ?string $declineReason = null,
    ): RecruitmentApplication {
        if ($application->status !== 'offered') {
            throw ValidationException::withMessages(['response' => 'This application is not awaiting an offer response.']);
        }

        if ($offerLetter->application_id !== $application->id) {
            throw ValidationException::withMessages(['response' => 'Invalid offer letter.']);
        }

        if (! $offerLetter->isPendingResponse()) {
            throw ValidationException::withMessages(['response' => 'This offer has already been responded to.']);
        }

        if (! in_array($response, ['accepted', 'declined'], true)) {
            throw ValidationException::withMessages(['response' => 'Invalid response.']);
        }

        return DB::transaction(function () use ($application, $offerLetter, $response, $declineReason) {
            $offerLetter->update([
                'response'       => $response,
                'responded_at'   => now(),
                'decline_reason' => $response === 'declined' ? $declineReason : null,
            ]);

            $from = $application->status;
            $notes = $response === 'accepted'
                ? 'Candidate accepted offer ' . $offerLetter->reference_no . '.'
                : 'Candidate declined offer ' . $offerLetter->reference_no . '.';

            if ($response === 'declined') {
                $application->update([
                    'status'           => 'withdrawn',
                    'rejection_reason' => $declineReason,
                    'reviewed_at'      => now(),
                ]);
            }

            RecruitmentApplicationLog::create([
                'application_id' => $application->id,
                'from_status'    => $from,
                'to_status'      => $response === 'declined' ? 'withdrawn' : $from,
                'notes'          => $notes,
                'user_id'        => null,
            ]);

            $updated = $application->fresh(['jobPosting', 'factory', 'offerLetters']);

            if ($response === 'declined') {
                $this->messaging->statusUpdated($updated, $from, $declineReason);
            }

            $this->notifications->offerResponded($updated, $offerLetter->fresh(), $response);

            return $updated;
        });
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

    private function assertNoDuplicateActiveApplication(int $postingId, string $phone, ?int $excludeApplicationId = null): void
    {
        $query = RecruitmentApplication::query()
            ->where('job_posting_id', $postingId)
            ->where('phone', $this->normalizePhone($phone))
            ->whereNotIn('status', ['rejected', 'withdrawn']);

        if ($excludeApplicationId) {
            $query->where('id', '!=', $excludeApplicationId);
        }

        if ($query->exists()) {
            throw ValidationException::withMessages([
                'phone' => 'An active application already exists for this phone number on this job posting.',
            ]);
        }
    }

    private function deleteApplicationFile(?string $path): void
    {
        if ($path) {
            Storage::disk('public')->delete($path);
        }
    }

    private function normalizePhone(string $phone): string
    {
        return preg_replace('/\s+/', '', trim($phone)) ?? trim($phone);
    }

    private function linkConvertedEmployee(
        RecruitmentApplication $application,
        Employee $employee,
        User $user,
        string $fromStatus,
        string $notes,
    ): void {
        $wasAlreadyConverted = (bool) $application->converted_employee_id;

        $application->update([
            'status'                => 'hired',
            'converted_employee_id' => $employee->id,
            'reviewed_by'           => $user->id,
            'reviewed_at'           => now(),
        ]);

        if (! $wasAlreadyConverted) {
            $application->jobPosting?->increment('openings_filled');
            $application->jobPosting?->refreshAvailability();
        }

        RecruitmentApplicationLog::create([
            'application_id' => $application->id,
            'from_status'    => $fromStatus,
            'to_status'      => 'hired',
            'notes'          => $notes,
            'user_id'        => $user->id,
        ]);
    }

    private function resolveJoiningDate(RecruitmentApplication $application): string
    {
        $application->loadMissing('offerLetters');

        $offerJoiningDate = $application->offerLetters
            ->sortByDesc('issued_at')
            ->first(fn (RecruitmentOfferLetter $letter) => $letter->joining_date !== null)
            ?->joining_date;

        return $offerJoiningDate?->toDateString() ?? now()->toDateString();
    }

    private function generateEmployeeCode(RecruitmentApplication $application): string
    {
        $base = str_replace('-', '', $application->application_no);

        if (! Employee::where('employee_code', $base)->exists()) {
            return $base;
        }

        for ($suffix = 2; $suffix <= 99; $suffix++) {
            $code = Str::limit($base, 27, '') . '-' . $suffix;

            if (! Employee::where('employee_code', $code)->exists()) {
                return $code;
            }
        }

        throw ValidationException::withMessages([
            'status' => 'Could not generate a unique employee code from this application.',
        ]);
    }

    private function assertNoActiveEmployeeConflict(RecruitmentApplication $application): void
    {
        if (! $application->phone && ! $application->nid_number) {
            return;
        }

        $query = Employee::query()
            ->whereNotIn('status', Employee::SEPARATED_STATUSES);

        $query->where(function ($builder) use ($application) {
            if ($application->phone) {
                $builder->where('phone', $application->phone);
            }

            if ($application->nid_number) {
                if ($application->phone) {
                    $builder->orWhere('nid_number', $application->nid_number);
                } else {
                    $builder->where('nid_number', $application->nid_number);
                }
            }
        });

        $existing = $query->first();

        if ($existing) {
            throw ValidationException::withMessages([
                'status' => 'An active employee already exists with this phone or NID (' . $existing->employee_code . '). Use Convert to Employee to review details first.',
            ]);
        }
    }

    private function copyApplicationFile(?string $sourcePath, string $folder): ?string
    {
        if (! $sourcePath || ! Storage::disk('public')->exists($sourcePath)) {
            return null;
        }

        $extension = pathinfo($sourcePath, PATHINFO_EXTENSION) ?: 'bin';
        $destination = sprintf('employees/%s/%s.%s', $folder, uniqid('rec_', true), $extension);

        Storage::disk('public')->copy($sourcePath, $destination);

        return $destination;
    }

    /** @param array<int, array<string, mixed>> $rows */
    private function syncEducationHistories(Employee $employee, array $rows): void
    {
        foreach (array_values($rows) as $index => $row) {
            if (! is_array($row) || $this->historyRowIsEmpty($row, ['degree', 'institution', 'board_or_university', 'passing_year', 'result'])) {
                continue;
            }

            $employee->educationHistories()->create([
                'degree'              => $row['degree'] ?? null,
                'institution'         => $row['institution'] ?? null,
                'board_or_university' => $row['board_or_university'] ?? null,
                'passing_year'        => $row['passing_year'] ?? null,
                'result'              => $row['result'] ?? null,
                'sort_order'          => $index,
            ]);
        }
    }

    /** @param array<int, array<string, mixed>> $rows */
    private function syncEmploymentHistories(Employee $employee, array $rows): void
    {
        foreach (array_values($rows) as $index => $row) {
            if (! is_array($row) || $this->historyRowIsEmpty($row, ['company_name', 'designation', 'department', 'joining_date', 'leaving_date', 'reason_for_leaving'])) {
                continue;
            }

            $employee->employmentHistories()->create([
                'company_name'       => $row['company_name'] ?? null,
                'designation'        => $row['designation'] ?? null,
                'department'         => $row['department'] ?? null,
                'joining_date'       => $row['joining_date'] ?? null,
                'leaving_date'       => $row['leaving_date'] ?? null,
                'reason_for_leaving' => $row['reason_for_leaving'] ?? null,
                'sort_order'         => $index,
            ]);
        }
    }

    /** @param array<string, mixed> $row */
    private function historyRowIsEmpty(array $row, array $fields): bool
    {
        foreach ($fields as $field) {
            if (filled($row[$field] ?? null)) {
                return false;
            }
        }

        return true;
    }
}
