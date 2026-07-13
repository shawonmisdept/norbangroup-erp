<?php

namespace App\Services\Tms;

use App\Models\Tms\TmsFuelLog;
use App\Models\Tms\TmsVehicle;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class FuelReportService
{
    /** @return array<string, float|int> */
    public function summarize(Request $request, array $filters): array
    {
        $row = $this->baseQuery($request, $filters)
            ->selectRaw('
                COUNT(*) as entry_count,
                COALESCE(SUM(quantity), 0) as total_quantity,
                COALESCE(SUM(amount), 0) as total_amount,
                COALESCE(SUM(CASE WHEN paid_by = \'company\' THEN amount ELSE 0 END), 0) as company_amount,
                COALESCE(SUM(CASE WHEN paid_by = \'rental_party\' THEN amount ELSE 0 END), 0) as rental_party_amount
            ')
            ->first();

        return [
            'entry_count'          => (int) ($row->entry_count ?? 0),
            'total_quantity'       => (float) ($row->total_quantity ?? 0),
            'total_amount'         => (float) ($row->total_amount ?? 0),
            'company_amount'       => (float) ($row->company_amount ?? 0),
            'rental_party_amount'  => (float) ($row->rental_party_amount ?? 0),
        ];
    }

    /** @return Collection<int, object> */
    public function byVehicle(Request $request, array $filters): Collection
    {
        $rows = $this->baseQuery($request, $filters)
            ->selectRaw('
                vehicle_id,
                COUNT(*) as entry_count,
                COALESCE(SUM(quantity), 0) as total_quantity,
                COALESCE(SUM(amount), 0) as total_amount,
                COALESCE(SUM(CASE WHEN paid_by = \'company\' THEN amount ELSE 0 END), 0) as company_amount,
                COALESCE(SUM(CASE WHEN paid_by = \'rental_party\' THEN amount ELSE 0 END), 0) as rental_party_amount
            ')
            ->whereNotNull('vehicle_id')
            ->groupBy('vehicle_id')
            ->orderByDesc('total_amount')
            ->get();

        $vehicles = TmsVehicle::whereIn('id', $rows->pluck('vehicle_id'))->get()->keyBy('id');

        return $rows->map(function ($row) use ($vehicles) {
            $row->vehicle = $vehicles->get($row->vehicle_id);

            return $row;
        });
    }

    public function baseQuery(Request $request, array $filters): Builder
    {
        $query = TmsFuelLog::query();

        $this->applyFactory($query, $request, $filters);
        $this->applyDate($query, $filters, 'created_at');

        if (! empty($filters['vehicle_id'])) {
            $query->where('vehicle_id', $filters['vehicle_id']);
        }

        return $query;
    }

    private function applyFactory(Builder $query, Request $request, array $filters): void
    {
        if (! empty($filters['factory_id'])) {
            $query->where('factory_id', $filters['factory_id']);
        } elseif ($request->user()?->scopedFactoryId()) {
            $query->where('factory_id', $request->user()->scopedFactoryId());
        }
    }

    private function applyDate(Builder $query, array $filters, string $column): void
    {
        if (! empty($filters['from'])) {
            $query->whereDate($column, '>=', $filters['from']);
        }
        if (! empty($filters['to'])) {
            $query->whereDate($column, '<=', $filters['to']);
        }
    }
}
