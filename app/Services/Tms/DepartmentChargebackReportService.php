<?php

namespace App\Services\Tms;

use App\Models\Tms\TmsTransportRequest;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class DepartmentChargebackReportService
{
    /** @return Collection<int, object> */
    public function build(Request $request, array $filters): Collection
    {
        $query = $this->baseQuery($request, $filters);

        return $query
            ->select([
                DB::raw('COALESCE(departments.id, 0) as department_id'),
                DB::raw("COALESCE(departments.name, 'Unassigned') as department_name"),
                DB::raw('COUNT(tms_transport_requests.id) as trip_count'),
                DB::raw('COALESCE(SUM(tms_transport_requests.passenger_count), 0) as passenger_count'),
                DB::raw('COALESCE(SUM(tms_trip_logs.total_driver_pay), 0) as driver_pay_total'),
                DB::raw('COALESCE(SUM(tms_trip_logs.ot_hours), 0) as ot_hours_total'),
            ])
            ->groupBy('departments.id', 'departments.name')
            ->orderBy('department_name')
            ->get();
    }

    /** @return Builder<TmsTransportRequest> */
    private function baseQuery(Request $request, array $filters): Builder
    {
        $query = TmsTransportRequest::query()
            ->join('hrm_employees', 'hrm_employees.id', '=', 'tms_transport_requests.employee_id')
            ->leftJoin('departments', 'departments.id', '=', 'hrm_employees.department_id')
            ->join('tms_trip_logs', 'tms_trip_logs.id', '=', 'tms_transport_requests.trip_log_id')
            ->where('tms_transport_requests.status', 'completed');

        if ($request->user()?->factory_id) {
            $query->where('tms_transport_requests.factory_id', $request->user()->factory_id);
        }

        if (! empty($filters['factory_id'])) {
            $query->where('tms_transport_requests.factory_id', $filters['factory_id']);
        }

        if (! empty($filters['department_id'])) {
            if ((int) $filters['department_id'] === 0) {
                $query->whereNull('hrm_employees.department_id');
            } else {
                $query->where('hrm_employees.department_id', $filters['department_id']);
            }
        }

        if (! empty($filters['from'])) {
            $query->whereDate('tms_transport_requests.pickup_at', '>=', $filters['from']);
        }

        if (! empty($filters['to'])) {
            $query->whereDate('tms_transport_requests.pickup_at', '<=', $filters['to']);
        }

        return $query;
    }
}
