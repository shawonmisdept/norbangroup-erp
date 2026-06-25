<?php

namespace App\Services\Hrm;

use App\Models\Hrm\JobPosting;
use App\Models\Hrm\RecruitmentApplication;
use App\Models\Hrm\RecruitmentInterview;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class RecruitmentDashboardService
{
    /** @return array<string, mixed> */
    public function build(User $user, ?int $factoryId, Carbon $from, Carbon $to): array
    {
        $appQuery = RecruitmentApplication::query()->whereBetween('applied_at', [$from, $to->copy()->endOfDay()]);
        $postingQuery = JobPosting::query();

        if ($factoryId) {
            $appQuery->where('factory_id', $factoryId);
            $postingQuery->where('factory_id', $factoryId);
        } elseif ($user->factory_id) {
            $appQuery->where('factory_id', $user->factory_id);
            $postingQuery->where('factory_id', $user->factory_id);
        }

        $pipeline = (clone $appQuery)
            ->select('status', DB::raw('count(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status');

        $openPostings = (clone $postingQuery)->open()->count();
        $totalApplications = (clone $appQuery)->count();
        $hired = (int) ($pipeline['hired'] ?? 0);
        $offered = (int) ($pipeline['offered'] ?? 0);
        $rejected = (int) ($pipeline['rejected'] ?? 0);

        $conversionRate = $totalApplications > 0
            ? round(($hired / $totalApplications) * 100, 1)
            : 0;

        $avgDaysToHire = null;
        $hiredApps = (clone $appQuery)
            ->where('status', 'hired')
            ->whereNotNull('reviewed_at')
            ->get(['applied_at', 'reviewed_at']);

        if ($hiredApps->isNotEmpty()) {
            $avgDaysToHire = round($hiredApps->avg(
                fn (RecruitmentApplication $app) => $app->applied_at->diffInDays($app->reviewed_at)
            ), 1);
        }

        $fillStats = (clone $postingQuery)
            ->where('status', 'open')
            ->selectRaw('SUM(slots) as total_slots, SUM(openings_filled) as total_filled')
            ->first();

        $totalSlots = (int) ($fillStats->total_slots ?? 0);
        $totalFilled = (int) ($fillStats->total_filled ?? 0);
        $fillRate = $totalSlots > 0 ? round(($totalFilled / $totalSlots) * 100, 1) : 0;

        $upcomingInterviews = RecruitmentInterview::query()
            ->where('result', 'pending')
            ->whereBetween('scheduled_at', [now(), now()->addDays(7)])
            ->whereHas('application', function ($q) use ($factoryId, $user) {
                if ($factoryId) {
                    $q->where('factory_id', $factoryId);
                } elseif ($user->factory_id) {
                    $q->where('factory_id', $user->factory_id);
                }
            })
            ->with(['application.jobPosting'])
            ->orderBy('scheduled_at')
            ->limit(8)
            ->get();

        $recentApplications = (clone $appQuery)
            ->with(['jobPosting', 'factory'])
            ->latest('applied_at')
            ->limit(8)
            ->get();

        $topPostings = (clone $postingQuery)
            ->withCount(['applications' => fn ($q) => $q->whereBetween('applied_at', [$from, $to->copy()->endOfDay()])])
            ->orderByDesc('applications_count')
            ->limit(5)
            ->get();

        return [
            'period_label'         => $from->format('d M Y') . ' — ' . $to->format('d M Y'),
            'open_postings'        => $openPostings,
            'total_applications'   => $totalApplications,
            'hired'                => $hired,
            'offered'              => $offered,
            'rejected'             => $rejected,
            'conversion_rate'      => $conversionRate,
            'avg_days_to_hire'     => $avgDaysToHire ? round((float) $avgDaysToHire, 1) : null,
            'fill_rate'            => $fillRate,
            'pipeline'             => $pipeline,
            'upcoming_interviews'  => $upcomingInterviews,
            'recent_applications'  => $recentApplications,
            'top_postings'         => $topPostings,
        ];
    }

    /** @return \Illuminate\Database\Eloquent\Builder<RecruitmentApplication> */
    public function filteredApplicationsQuery(User $user, array $filters)
    {
        $query = RecruitmentApplication::query()
            ->with(['jobPosting', 'factory'])
            ->latest('applied_at');

        if ($user->factory_id) {
            $query->where('factory_id', $user->factory_id);
        } elseif (! empty($filters['factory_id'])) {
            $query->where('factory_id', $filters['factory_id']);
        }

        foreach (['status', 'source', 'job_posting_id'] as $field) {
            if (! empty($filters[$field])) {
                $query->where($field, $filters[$field]);
            }
        }

        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('application_no', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('nid_number', 'like', "%{$search}%");
            });
        }

        if (! empty($filters['from'])) {
            $query->whereDate('applied_at', '>=', $filters['from']);
        }

        if (! empty($filters['to'])) {
            $query->whereDate('applied_at', '<=', $filters['to']);
        }

        return $query;
    }
}
