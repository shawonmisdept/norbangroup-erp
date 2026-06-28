<?php

namespace App\Http\Controllers\Admin\Tms;

use App\Http\Controllers\Admin\Hrm\Concerns\ScopesHrmFactory;
use App\Http\Controllers\Controller;
use App\Models\Tms\TmsDestination;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class DestinationController extends Controller
{
    use ScopesHrmFactory;

    public function index(Request $request)
    {
        $query = TmsDestination::query()->with('factory')->latest('id');
        $this->scopeToUserFactory($query, $request);

        if ($request->filled('factory_id')) {
            $query->where('factory_id', $request->factory_id);
        }

        return view('admin.tms.destinations.index', [
            'destinations' => $query->paginate(25)->withQueryString(),
            'factories'    => $this->factoryOptions($request),
            'filters'      => $request->only(['factory_id']),
        ]);
    }

    public function create(Request $request)
    {
        return view('admin.tms.destinations.form', [
            'destination' => new TmsDestination(['is_active' => true]),
            'factories'   => $this->factoryOptions($request),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $this->validateDestination($request);
        $this->authorizeFactoryAccess($request, (int) $validated['factory_id']);

        TmsDestination::create($validated + [
            'created_by' => $request->user()->id,
            'updated_by' => $request->user()->id,
        ]);

        return redirect()->route('admin.tms.destinations.index')->with('success', 'Destination created.');
    }

    public function edit(Request $request, TmsDestination $destination)
    {
        $this->authorizeFactoryAccess($request, $destination->factory_id);

        return view('admin.tms.destinations.form', [
            'destination' => $destination,
            'factories'   => $this->factoryOptions($request),
        ]);
    }

    public function update(Request $request, TmsDestination $destination)
    {
        $this->authorizeFactoryAccess($request, $destination->factory_id);
        $destination->update($this->validateDestination($request, $destination) + [
            'updated_by' => $request->user()->id,
        ]);

        return redirect()->route('admin.tms.destinations.index')->with('success', 'Destination updated.');
    }

    public function destroy(Request $request, TmsDestination $destination)
    {
        $this->authorizeFactoryAccess($request, $destination->factory_id);
        $destination->delete();

        return redirect()->route('admin.tms.destinations.index')->with('success', 'Destination deleted.');
    }

    private function validateDestination(Request $request, ?TmsDestination $destination = null): array
    {
        return $request->validate([
            'factory_id' => ['required', 'exists:factories,id'],
            'name'       => [
                'required', 'string', 'max:255',
                Rule::unique('tms_destinations', 'name')
                    ->where('factory_id', $request->input('factory_id'))
                    ->ignore($destination?->id),
            ],
            'address'   => ['nullable', 'string', 'max:500'],
            'is_active' => ['nullable', 'boolean'],
        ]) + ['is_active' => $request->boolean('is_active', true)];
    }
}
