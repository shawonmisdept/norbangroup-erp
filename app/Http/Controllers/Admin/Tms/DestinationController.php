<?php

namespace App\Http\Controllers\Admin\Tms;

use App\Http\Controllers\Controller;
use App\Models\Tms\TmsDestination;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class DestinationController extends Controller
{
    public function index(Request $request)
    {
        $destinations = TmsDestination::query()
            ->shared()
            ->paginate(25)
            ->withQueryString();

        return view('admin.tms.destinations.index', [
            'destinations' => $destinations,
        ]);
    }

    public function create()
    {
        return view('admin.tms.destinations.form', [
            'destination' => new TmsDestination(['is_active' => true]),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $this->validateDestination($request);
        $factoryId = TmsDestination::anchorFactoryId();

        if (! $factoryId) {
            throw ValidationException::withMessages([
                'name' => 'No active unit found to anchor destinations.',
            ]);
        }

        TmsDestination::create($validated + [
            'factory_id' => $factoryId,
            'created_by' => $request->user()->id,
            'updated_by' => $request->user()->id,
        ]);

        return redirect()
            ->route('admin.tms.destinations.index')
            ->with('success', 'Destination created for all units.');
    }

    public function edit(TmsDestination $destination)
    {
        return view('admin.tms.destinations.form', [
            'destination' => $destination,
        ]);
    }

    public function update(Request $request, TmsDestination $destination)
    {
        $destination->update($this->validateDestination($request, $destination) + [
            'updated_by' => $request->user()->id,
        ]);

        return redirect()
            ->route('admin.tms.destinations.index')
            ->with('success', 'Destination updated for all units.');
    }

    public function destroy(TmsDestination $destination)
    {
        $destination->delete();

        return redirect()
            ->route('admin.tms.destinations.index')
            ->with('success', 'Destination deleted.');
    }

    private function validateDestination(Request $request, ?TmsDestination $destination = null): array
    {
        return $request->validate([
            'name' => [
                'required', 'string', 'max:255',
                Rule::unique('tms_destinations', 'name')
                    ->whereNull('deleted_at')
                    ->ignore($destination?->id),
            ],
            'address'   => ['nullable', 'string', 'max:500'],
            'is_active' => ['nullable', 'boolean'],
        ]) + ['is_active' => $request->boolean('is_active', true)];
    }
}
