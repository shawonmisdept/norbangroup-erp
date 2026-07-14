<?php

namespace App\Http\Controllers\Admin\Tms;

use App\Http\Controllers\Admin\Hrm\Concerns\ScopesHrmFactory;
use App\Http\Controllers\Controller;
use App\Models\Tms\TmsGpsPosition;
use App\Models\Tms\TmsSetting;
use App\Models\Tms\TmsVehicle;
use App\Services\Tms\TmsGpsService;
use Illuminate\Http\Request;

class GpsController extends Controller
{
    use ScopesHrmFactory;

    public function __construct(
        private TmsGpsService $gpsService,
    ) {}

    public function index(Request $request)
    {
        $factoryId = $request->user()->factory_id ?? $request->integer('factory_id') ?: null;

        $settings = TmsSetting::current();
        $positions = collect();
        $vehicles = [];

        if ($factoryId) {
            $this->authorizeFactoryAccess($request, $factoryId);

            $query = TmsGpsPosition::query()
                ->with(['vehicle', 'tripLog'])
                ->where('factory_id', $factoryId)
                ->latest('recorded_at');

            if ($request->filled('vehicle_id')) {
                $query->where('vehicle_id', $request->vehicle_id);
            }

            $positions = $query->paginate(25)->withQueryString();

            $vehicles = TmsVehicle::query()
                ->where('factory_id', $factoryId)
                ->orderBy('name')
                ->get()
                ->mapWithKeys(fn ($v) => [$v->id => $v->displayLabel()])
                ->all();
        }

        return view('admin.tms.gps.index', [
            'settings'  => $settings,
            'positions' => $positions,
            'vehicles'  => $vehicles,
            'factories' => $this->factoryOptions($request),
            'factoryId' => $factoryId,
            'providers' => config('tms.gps_providers', []),
            'filters'   => $request->only(['factory_id', 'vehicle_id']),
            'canManage' => $request->user()?->canManageTmsSubmodule('settings') ?? false,
        ]);
    }
}
