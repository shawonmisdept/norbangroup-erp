<?php

namespace App\Http\Controllers\Admin\Tms;

use App\Http\Controllers\Admin\Hrm\Concerns\ScopesHrmFactory;
use App\Http\Controllers\Controller;
use App\Models\Tms\TmsSetting;
use Illuminate\Http\Request;

class SettingsController extends Controller
{
    use ScopesHrmFactory;

    public function index(Request $request)
    {
        $factoryId = $request->user()->factory_id ?? $request->integer('factory_id') ?: null;

        $settings = null;
        if ($factoryId) {
            $this->authorizeFactoryAccess($request, $factoryId);
            $settings = TmsSetting::firstOrCreate(
                ['factory_id' => $factoryId],
                TmsSetting::defaultValues()
            );
        }

        return view('admin.tms.settings.index', [
            'settings'    => $settings,
            'factories'   => $this->factoryOptions($request),
            'factoryId'   => $factoryId,
            'otBasis'     => config('tms.ot_basis'),
            'weekdayLabels' => config('tms.weekday_labels'),
            'gpsProviders' => config('tms.gps_providers', []),
        ]);
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'factory_id'                  => ['required', 'exists:factories,id'],
            'office_start'                => ['required', 'date_format:H:i'],
            'office_end'                  => ['required', 'date_format:H:i'],
            'ot_basis'                    => ['required', 'in:global_office_time,employee_shift_end'],
            'company_night_bill'          => ['required', 'numeric', 'min:0'],
            'company_holiday_duty_bill'   => ['required', 'numeric', 'min:0'],
            'rental_ot_hourly_rate'       => ['required', 'numeric', 'min:0'],
            'rental_km_rate'              => ['required', 'numeric', 'min:0'],
            'weekend_days'                => ['nullable', 'array'],
            'weekend_days.*'              => ['integer', 'min:0', 'max:6'],
            'gps_tracking_enabled'        => ['nullable', 'boolean'],
            'gps_provider'                => ['nullable', 'in:none,device_api,browser'],
        ]);

        $this->authorizeFactoryAccess($request, (int) $validated['factory_id']);

        $weekendDays = collect($validated['weekend_days'] ?? [])
            ->map(fn ($day) => (int) $day)
            ->unique()
            ->sort()
            ->values()
            ->all();

        TmsSetting::updateOrCreate(
            ['factory_id' => $validated['factory_id']],
            [
                'office_start'                => $validated['office_start'] . ':00',
                'office_end'                  => $validated['office_end'] . ':00',
                'ot_basis'                    => $validated['ot_basis'],
                'company_night_bill'          => $validated['company_night_bill'],
                'company_holiday_duty_bill'   => $validated['company_holiday_duty_bill'],
                'rental_ot_hourly_rate'       => $validated['rental_ot_hourly_rate'],
                'rental_km_rate'              => $validated['rental_km_rate'],
                'weekend_days'                => $weekendDays ?: TmsSetting::defaultValues()['weekend_days'],
                'gps_tracking_enabled'        => $request->boolean('gps_tracking_enabled'),
                'gps_provider'                => $validated['gps_provider'] ?? 'none',
                'updated_by'                  => $request->user()->id,
            ]
        );

        return redirect()
            ->route('admin.tms.settings.index', ['factory_id' => $validated['factory_id']])
            ->with('success', 'TMS settings saved.');
    }
}
