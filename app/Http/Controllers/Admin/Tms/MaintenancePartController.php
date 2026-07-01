<?php

namespace App\Http\Controllers\Admin\Tms;

use App\Http\Controllers\Admin\Hrm\Concerns\ScopesHrmFactory;
use App\Http\Controllers\Controller;
use App\Models\Tms\TmsMaintenancePartCatalog;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class MaintenancePartController extends Controller
{
    use ScopesHrmFactory;

    public function index(Request $request)
    {
        $query = TmsMaintenancePartCatalog::query()->orderBy('name');
        $this->scopeToUserFactory($query, $request);

        if ($request->filled('factory_id')) {
            $query->where('factory_id', $request->factory_id);
        }

        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        if ($request->boolean('inactive')) {
            $query->where('is_active', false);
        } else {
            $query->where('is_active', true);
        }

        return view('admin.tms.maintenance.parts.index', [
            'parts'     => $query->paginate(25)->withQueryString(),
            'factories' => $this->factoryOptions($request),
            'units'     => config('tms.maintenance_item_units', []),
            'filters'   => $request->only(['factory_id', 'search', 'inactive']),
            'canManage' => $request->user()?->canManageTmsSubmodule('maintenance') ?? false,
        ]);
    }

    public function create(Request $request)
    {
        return view('admin.tms.maintenance.parts.form', [
            'part'      => new TmsMaintenancePartCatalog(['is_active' => true]),
            'factories' => $this->factoryOptions($request),
            'units'     => config('tms.maintenance_item_units', []),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $this->validatePart($request);
        $this->authorizeFactoryAccess($request, (int) $validated['factory_id']);

        TmsMaintenancePartCatalog::create($validated);

        return redirect()->route('admin.tms.maintenance.parts.index')->with('success', 'Part added.');
    }

    public function edit(Request $request, TmsMaintenancePartCatalog $part)
    {
        $this->authorizeFactoryAccess($request, $part->factory_id);

        return view('admin.tms.maintenance.parts.form', [
            'part'      => $part,
            'factories' => $this->factoryOptions($request),
            'units'     => config('tms.maintenance_item_units', []),
        ]);
    }

    public function update(Request $request, TmsMaintenancePartCatalog $part)
    {
        $this->authorizeFactoryAccess($request, $part->factory_id);

        $part->update($this->validatePart($request, $part));

        return redirect()->route('admin.tms.maintenance.parts.index')->with('success', 'Part updated.');
    }

    public function destroy(Request $request, TmsMaintenancePartCatalog $part)
    {
        $this->authorizeFactoryAccess($request, $part->factory_id);

        if ($part->billItems()->exists()) {
            $part->update(['is_active' => false]);

            return redirect()->route('admin.tms.maintenance.parts.index')
                ->with('success', 'Part deactivated (used on bills).');
        }

        $part->delete();

        return redirect()->route('admin.tms.maintenance.parts.index')->with('success', 'Part deleted.');
    }

    private function validatePart(Request $request, ?TmsMaintenancePartCatalog $part = null): array
    {
        return $request->validate([
            'factory_id'         => ['required', 'exists:factories,id'],
            'name'               => [
                'required', 'string', 'max:255',
                Rule::unique('tms_maintenance_part_catalog', 'name')
                    ->where('factory_id', $request->input('factory_id'))
                    ->ignore($part?->id),
            ],
            'unit'               => ['nullable', 'string', 'max:16'],
            'default_unit_price' => ['nullable', 'numeric', 'min:0'],
            'is_active'          => ['nullable', 'boolean'],
        ]) + ['is_active' => $request->boolean('is_active', true)];
    }
}
