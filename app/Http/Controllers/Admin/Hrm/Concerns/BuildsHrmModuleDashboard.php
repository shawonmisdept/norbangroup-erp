<?php

namespace App\Http\Controllers\Admin\Hrm\Concerns;

use Carbon\Carbon;
use Illuminate\Http\Request;

trait BuildsHrmModuleDashboard
{
    /** @return array{from: Carbon, to: Carbon, factoryId: int|null} */
    protected function dashboardFilters(Request $request): array
    {
        $from = Carbon::parse($request->input('from', now()->startOfMonth()->toDateString()))->startOfDay();
        $to = Carbon::parse($request->input('to', now()->toDateString()))->startOfDay();
        $requested = $request->filled('factory_id') ? (int) $request->factory_id : null;
        $factoryId = $this->resolveFactoryFilter($request, $requested);

        return compact('from', 'to', 'factoryId');
    }

    /** @return array<string, mixed> */
    protected function dashboardViewExtras(Request $request, array $filters): array
    {
        return [
            'factories'         => $this->factoryOptions($request),
            'scopedFactoryName' => $this->scopedFactoryName($request),
            'filters'           => [
                'factory_id' => $filters['factoryId'],
                'from'       => $filters['from']->toDateString(),
                'to'         => $filters['to']->toDateString(),
            ],
            'period_label'      => $filters['from']->format('d M Y') . ' — ' . $filters['to']->format('d M Y'),
        ];
    }
}
