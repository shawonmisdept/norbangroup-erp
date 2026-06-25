<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\Hrm\ShiftRosterEntry;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;

class RosterController extends Controller
{
    public function index(Request $request)
    {
        $employee = $request->user('employee')->employee;

        $start = now()->startOfWeek(Carbon::SUNDAY);
        $end = $start->copy()->addDays(6);

        $entries = ShiftRosterEntry::query()
            ->with(['shift', 'line', 'roster'])
            ->where('employee_id', $employee->id)
            ->whereBetween('roster_date', [$start->toDateString(), $end->toDateString()])
            ->whereHas('roster', fn ($q) => $q
                ->where('factory_id', $employee->factory_id)
                ->where('status', 'published'))
            ->orderBy('roster_date')
            ->get()
            ->keyBy(fn ($e) => $e->roster_date->toDateString());

        $dates = collect(CarbonPeriod::create($start, $end))
            ->map(fn ($d) => $d->toDateString());

        return view('employee.roster.index', compact('employee', 'entries', 'dates', 'start', 'end'));
    }
}
