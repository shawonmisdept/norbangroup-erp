<?php

namespace App\Services\Hrm;

use App\Models\Department;
use App\Models\Designation;
use App\Models\Hrm\JobPosting;
use App\Models\Hrm\JobPostingLog;
use App\Models\Hrm\RecruitmentApplication;
use App\Models\User;
use App\Support\HtmlSanitizer;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class JobPostingService
{
    /** @return array<string, mixed> */
    public function validatePosting(Request $request, ?JobPosting $posting = null): array
    {
        $statuses = array_keys(JobPosting::STATUSES);
        $genders = array_keys(config('hrm.recruitment_posting_genders', []));
        $shifts = array_keys(config('hrm.job_posting_shift_types', []));

        $validated = $request->validate([
            'factory_id'         => ['required', 'exists:factories,id'],
            'department_id'      => ['nullable', 'exists:departments,id'],
            'designation_id'     => ['nullable', 'exists:designations,id'],
            'worker_category_id' => ['nullable', 'exists:hrm_worker_categories,id'],
            'title'              => ['required', 'string', 'max:200'],
            'title_bn'           => ['nullable', 'string', 'max:200'],
            'description'        => ['nullable', 'string', 'max:50000'],
            'description_bn'     => ['nullable', 'string', 'max:50000'],
            'requirements'       => ['nullable', 'string', 'max:50000'],
            'skills_expertise'   => ['nullable', 'string', 'max:50000'],
            'responsibilities'   => ['nullable', 'string', 'max:50000'],
            'employment_status'  => ['nullable', 'string', 'max:50000'],
            'salary_text'        => [
                Rule::requiredIf(fn () => ! $request->boolean('salary_negotiable')),
                'nullable',
                'string',
                'max:500',
            ],
            'salary_negotiable'  => ['nullable', 'boolean'],
            'benefits'           => ['nullable', 'string', 'max:50000'],
            'meta_description'   => ['nullable', 'string', 'max:500'],
            'shift_type'         => ['nullable', 'in:' . implode(',', $shifts)],
            'min_age'            => ['nullable', 'integer', 'min:14', 'max:70'],
            'max_age'            => ['nullable', 'integer', 'min:14', 'max:70', 'gte:min_age'],
            'required_gender'    => ['nullable', 'in:' . implode(',', $genders)],
            'rehire_eligible'    => ['nullable', 'boolean'],
            'is_internal'        => ['nullable', 'boolean'],
            'slots'              => [
                'required',
                'integer',
                'min:1',
                'max:9999',
                Rule::when($posting, fn () => 'min:' . max(1, (int) $posting->openings_filled)),
            ],
            'status'             => ['required', 'in:' . implode(',', $statuses)],
            'closes_at'          => ['nullable', 'date'],
            'template_key'       => ['nullable', 'string', 'max:50'],
        ]);

        $validated['salary_negotiable'] = $request->boolean('salary_negotiable');
        $validated['rehire_eligible'] = $request->boolean('rehire_eligible');
        $validated['is_internal'] = $request->boolean('is_internal');
        $validated['salary_text'] = trim(strip_tags($validated['salary_text'] ?? '')) ?: null;

        foreach (['description', 'description_bn', 'requirements', 'skills_expertise', 'responsibilities', 'employment_status', 'benefits'] as $field) {
            $validated[$field] = HtmlSanitizer::clean($validated[$field] ?? null);
        }

        $this->assertFactoryScopedMasters($validated);

        if ($validated['status'] === 'open' && ! empty($validated['closes_at'])) {
            $closesAt = Carbon::parse($validated['closes_at'])->startOfDay();
            if ($closesAt->isPast()) {
                throw ValidationException::withMessages([
                    'closes_at' => 'Closing date must be today or in the future for open postings.',
                ]);
            }
        }

        return $validated;
    }

    public function resolveStatusForSave(array $validated, User $user, ?JobPosting $existing = null): array
    {
        if ($validated['status'] !== 'open') {
            return $validated;
        }

        if (! config('hrm.recruitment_posting_settings.require_approval', false)) {
            return $validated;
        }

        if ($user->canApproveRecruitmentPostings()) {
            $validated['approved_at'] = now();
            $validated['approved_by'] = $user->id;

            return $validated;
        }

        $validated['status'] = 'pending_approval';

        return $validated;
    }

    public function createPosting(array $validated, User $user): JobPosting
    {
        $validated = $this->resolveStatusForSave($validated, $user);

        if ($validated['status'] === 'open' && empty($validated['published_at'])) {
            $validated['published_at'] = now();
        }

        $posting = JobPosting::create([
            ...$validated,
            'created_by' => $user->id,
        ]);

        $this->log($posting, 'created', 'Job posting created.', $user);

        if ($posting->status === 'pending_approval') {
            $this->log($posting, 'submitted', 'Submitted for approval.', $user);
        }

        return $posting;
    }

    public function updatePosting(JobPosting $posting, array $validated, User $user): JobPosting
    {
        $wasOpen = $posting->status === 'open';

        if ($validated['status'] === 'open' && ! $wasOpen) {
            $validated = $this->resolveStatusForSave($validated, $user, $posting);

            if ($validated['status'] === 'open' && ! $posting->published_at) {
                $validated['published_at'] = now();
            }
        }

        $posting->update($validated);

        $this->log($posting, 'updated', 'Job posting updated.', $user);

        if (! $wasOpen && $posting->fresh()->status === 'pending_approval') {
            $this->log($posting->fresh(), 'submitted', 'Submitted for approval.', $user);
        }

        return $posting->fresh();
    }

    public function publish(JobPosting $posting, User $user): JobPosting
    {
        if (! in_array($posting->status, ['draft', 'closed', 'pending_approval'], true)) {
            throw ValidationException::withMessages(['status' => 'This posting cannot be published in its current state.']);
        }

        if ($posting->closes_at?->isPast()) {
            throw ValidationException::withMessages(['closes_at' => 'Update the closing date before publishing.']);
        }

        if (config('hrm.recruitment_posting_settings.require_approval', false) && ! $user->canApproveRecruitmentPostings()) {
            $posting->update(['status' => 'pending_approval']);

            $this->log($posting->fresh(), 'submitted', 'Submitted for approval.', $user);

            return $posting->fresh();
        }

        $posting->update([
            'status'       => 'open',
            'published_at' => $posting->published_at ?? now(),
            'approved_at'  => now(),
            'approved_by'  => $user->id,
        ]);

        $this->log($posting->fresh(), 'published', 'Job posting published.', $user);

        return $posting->fresh();
    }

    public function approve(JobPosting $posting, User $user): JobPosting
    {
        if ($posting->status !== 'pending_approval') {
            throw ValidationException::withMessages(['status' => 'Only pending postings can be approved.']);
        }

        if ($posting->closes_at?->isPast()) {
            throw ValidationException::withMessages(['closes_at' => 'Update the closing date before approval.']);
        }

        $posting->update([
            'status'       => 'open',
            'published_at' => $posting->published_at ?? now(),
            'approved_at'  => now(),
            'approved_by'  => $user->id,
        ]);

        $this->log($posting->fresh(), 'approved', 'Job posting approved and published.', $user);

        return $posting->fresh();
    }

    public function close(JobPosting $posting, User $user, ?string $notes = null): JobPosting
    {
        if ($posting->status === 'closed') {
            return $posting;
        }

        $posting->update(['status' => 'closed']);
        $this->log($posting->fresh(), 'closed', $notes ?? 'Job posting closed.', $user);

        return $posting->fresh();
    }

    public function reopen(JobPosting $posting, User $user): JobPosting
    {
        if ($posting->status !== 'closed') {
            throw ValidationException::withMessages(['status' => 'Only closed postings can be reopened.']);
        }

        if ($posting->openings_filled >= $posting->slots) {
            throw ValidationException::withMessages(['slots' => 'Increase slots before reopening — all positions are filled.']);
        }

        if ($posting->closes_at?->isPast()) {
            throw ValidationException::withMessages(['closes_at' => 'Update the closing date before reopening.']);
        }

        if (config('hrm.recruitment_posting_settings.require_approval', false) && ! $user->canApproveRecruitmentPostings()) {
            $posting->update(['status' => 'pending_approval']);
            $this->log($posting->fresh(), 'submitted', 'Reopen submitted for approval.', $user);

            return $posting->fresh();
        }

        $posting->update([
            'status'       => 'open',
            'published_at' => $posting->published_at ?? now(),
            'approved_at'  => now(),
            'approved_by'  => $user->id,
        ]);

        $this->log($posting->fresh(), 'reopened', 'Job posting reopened.', $user);

        return $posting->fresh();
    }

    public function duplicate(JobPosting $posting, User $user): JobPosting
    {
        $copy = $posting->replicate([
            'openings_filled',
            'page_views',
            'published_at',
            'approved_at',
            'approved_by',
            'created_by',
        ]);

        $copy->fill([
            'title'           => $posting->title . ' (Copy)',
            'status'          => 'draft',
            'openings_filled' => 0,
            'page_views'      => 0,
            'published_at'    => null,
            'approved_at'     => null,
            'approved_by'     => null,
            'created_by'      => $user->id,
        ]);
        $copy->save();

        $this->log($copy, 'duplicated', 'Duplicated from posting #' . $posting->id . '.', $user, ['source_id' => $posting->id]);

        return $copy;
    }

    /** @param  array<int>  $factoryIds */
    public function bulkCreate(array $validated, array $factoryIds, User $user): int
    {
        $created = 0;

        DB::transaction(function () use ($validated, $factoryIds, $user, &$created) {
            foreach ($factoryIds as $factoryId) {
                $payload = $validated;
                $payload['factory_id'] = (int) $factoryId;
                $payload['department_id'] = null;
                $payload['designation_id'] = null;

                $posting = $this->createPosting($payload, $user);
                $this->log($posting, 'bulk_created', 'Created via bulk posting.', $user, ['factory_id' => $factoryId]);
                $created++;
            }
        });

        return $created;
    }

    public function recordPageView(JobPosting $posting): void
    {
        $posting->increment('page_views');
    }

    public function closeExpiredPostings(): int
    {
        $expired = JobPosting::query()
            ->where('status', 'open')
            ->whereNotNull('closes_at')
            ->where('closes_at', '<=', now())
            ->get();

        foreach ($expired as $posting) {
            $posting->update(['status' => 'closed']);
            $this->log($posting, 'auto_closed', 'Automatically closed after deadline.');
        }

        return $expired->count();
    }

    /** @return array<string, mixed> */
    public function analytics(JobPosting $posting): array
    {
        $applications = $posting->applications()->count();
        $hired = $posting->applications()->where('status', 'hired')->count();
        $views = (int) $posting->page_views;
        $conversionRate = $views > 0 ? round(($applications / $views) * 100, 1) : null;

        $hiredApps = $posting->applications()
            ->where('status', 'hired')
            ->whereNotNull('reviewed_at')
            ->get(['applied_at', 'reviewed_at']);

        $avgDaysToHire = $hiredApps->isNotEmpty()
            ? round($hiredApps->avg(fn (RecruitmentApplication $app) => $app->applied_at->diffInDays($app->reviewed_at)), 1)
            : null;

        return [
            'page_views'        => $views,
            'applications'      => $applications,
            'hired'             => $hired,
            'conversion_rate'   => $conversionRate,
            'avg_days_to_hire'  => $avgDaysToHire,
            'remaining_slots'   => $posting->remainingSlots(),
        ];
    }

    /** @return array<string, int> */
    public function pipelineStats(JobPosting $posting): array
    {
        return $posting->applications()
            ->select('status', DB::raw('count(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status')
            ->map(fn ($v) => (int) $v)
            ->all();
    }

    /** @return array<string, mixed> */
    public function templateDefaults(?string $key): array
    {
        if (! $key) {
            return [];
        }

        return config("hrm.job_posting_templates.{$key}", []);
    }

    /** @return array{departments: array<int, string>, designations: array<int, string>} */
    public function formOptionsForFactory(int $factoryId): array
    {
        $departments = Department::query()
            ->where('is_active', true)
            ->where('factory_id', $factoryId)
            ->orderBy('name')
            ->get(['id', 'name', 'native_name', 'factory_id']);

        $designations = Designation::query()
            ->where('is_active', true)
            ->whereHas('department', fn ($q) => $q->where('factory_id', $factoryId))
            ->with('department.factory')
            ->orderBy('name')
            ->get(['id', 'name', 'native_name', 'department_id']);

        return [
            'departments'  => $departments->mapWithKeys(fn (Department $d) => [$d->id => $d->displayLabel()])->all(),
            'designations' => $designations->mapWithKeys(fn (Designation $d) => [$d->id => $d->displayLabel()])->all(),
        ];
    }

    /** @return array<string, mixed> */
    public function indexFilterOptions(Request $request, array $factoryOptions): array
    {
        $factoryId = $request->filled('factory_id') ? (int) $request->factory_id : null;

        if (! $factoryId && count($factoryOptions) === 1) {
            $factoryId = (int) array_key_first($factoryOptions);
        }

        $departmentQuery = Department::query()->where('is_active', true)->orderBy('name');
        $designationQuery = Designation::query()->where('is_active', true)->orderBy('name');

        if ($factoryId) {
            $departmentQuery->where('factory_id', $factoryId);
            $designationQuery->whereHas('department', fn ($q) => $q->where('factory_id', $factoryId));
        }

        return [
            'departments'      => $departmentQuery->get()->mapWithKeys(fn (Department $d) => [$d->id => $d->displayLabel()])->all(),
            'designations'     => $designationQuery->get()->mapWithKeys(fn (Designation $d) => [$d->id => $d->displayLabel()])->all(),
            'workerCategories' => \App\Models\Hrm\WorkerCategory::where('is_active', true)->orderBy('name')->pluck('name', 'id')->all(),
            'shiftTypes'       => config('hrm.job_posting_shift_types', []),
        ];
    }

    public function applyIndexFilters($query, Request $request): void
    {
        if ($request->filled('factory_id')) {
            $query->where('factory_id', $request->factory_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('department_id')) {
            $query->where('department_id', $request->department_id);
        }

        if ($request->filled('designation_id')) {
            $query->where('designation_id', $request->designation_id);
        }

        if ($request->filled('worker_category_id')) {
            $query->where('worker_category_id', $request->worker_category_id);
        }

        if ($request->filled('shift_type')) {
            $query->where('shift_type', $request->shift_type);
        }

        if ($request->boolean('closing_soon')) {
            $query->whereNotNull('closes_at')
                ->whereBetween('closes_at', [now(), now()->addDays(7)]);
        }

        if ($request->boolean('has_applications')) {
            $query->whereHas('applications');
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('title_bn', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }
    }

    public function acceptsApplications(JobPosting $posting, string $source = 'online'): bool
    {
        if ($source === 'online') {
            return $posting->isPubliclyOpen();
        }

        if (config('hrm.recruitment_posting_settings.block_manual_on_closed', true)) {
            return $posting->isOpen();
        }

        return $posting->status !== 'closed';
    }

    private function assertFactoryScopedMasters(array $validated): void
    {
        if (! empty($validated['department_id'])) {
            $valid = Department::where('id', $validated['department_id'])
                ->where('factory_id', $validated['factory_id'])
                ->exists();

            if (! $valid) {
                throw ValidationException::withMessages(['department_id' => 'Department does not belong to the selected factory.']);
            }
        }

        if (! empty($validated['designation_id'])) {
            $valid = Designation::query()
                ->where('id', $validated['designation_id'])
                ->whereHas('department', fn ($q) => $q->where('factory_id', $validated['factory_id']))
                ->exists();

            if (! $valid) {
                throw ValidationException::withMessages(['designation_id' => 'Designation does not belong to the selected factory.']);
            }
        }
    }

    private function log(JobPosting $posting, string $action, ?string $notes, ?User $user = null, array $meta = []): void
    {
        JobPostingLog::create([
            'job_posting_id' => $posting->id,
            'action'         => $action,
            'notes'          => $notes,
            'meta'           => $meta ?: null,
            'user_id'        => $user?->id,
        ]);
    }
}
