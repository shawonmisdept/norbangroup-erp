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
                ['office_start' => '09:00:00', 'office_end' => '17:00:00', 'ot_basis' => 'global_office_time']
            );
        }

        return view('admin.tms.settings.index', [
            'settings'  => $settings,
            'factories' => $this->factoryOptions($request),
            'factoryId' => $factoryId,
            'otBasis'   => config('tms.ot_basis'),
        ]);
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'factory_id'   => ['required', 'exists:factories,id'],
            'office_start' => ['required', 'date_format:H:i'],
            'office_end'   => ['required', 'date_format:H:i'],
            'ot_basis'     => ['required', 'in:global_office_time,employee_shift_end'],
        ]);

        $this->authorizeFactoryAccess($request, (int) $validated['factory_id']);

        TmsSetting::updateOrCreate(
            ['factory_id' => $validated['factory_id']],
            [
                'office_start' => $validated['office_start'] . ':00',
                'office_end'   => $validated['office_end'] . ':00',
                'ot_basis'     => $validated['ot_basis'],
                'updated_by'   => $request->user()->id,
            ]
        );

        return redirect()
            ->route('admin.tms.settings.index', ['factory_id' => $validated['factory_id']])
            ->with('success', 'TMS settings saved.');
    }
}
