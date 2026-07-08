<?php

namespace App\Http\Controllers\Admin\Tms;

use App\Http\Controllers\Admin\Hrm\Concerns\ScopesHrmFactory;
use App\Http\Controllers\Controller;
use App\Models\Tms\TmsVehicle;
use App\Models\Tms\TmsVehiclePaperRenewal;
use App\Services\Tms\VehiclePaperService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class VehiclePaperController extends Controller
{
    use ScopesHrmFactory;

    public function __construct(
        private VehiclePaperService $paperService,
    ) {}

    public function index(Request $request)
    {
        $perPage = $this->resolvePerPage($request);
        $vehicles = $this->resolveVehicles($request, $perPage);

        $viewData = $this->viewData($request, $vehicles, $perPage);

        if ($request->ajax()) {
            return view('admin.tms.vehicles.partials.papers-results', $viewData);
        }

        return view('admin.tms.vehicles.papers-index', $viewData);
    }

    public function print(Request $request)
    {
        $vehicles = $this->resolveVehicles($request);

        return view('admin.tms.vehicles.papers-print', [
            'vehicles'     => $vehicles,
            'filters'      => $request->only(['factory_id', 'paper_status', 'search']),
            'paperService' => $this->paperService,
            'printedAt'    => now(),
        ]);
    }

    public function storeRenewal(Request $request, TmsVehicle $vehicle)
    {
        $this->authorizeFactoryAccess($request, $vehicle->factory_id);

        $validated = $request->validate([
            'paper_type'      => ['required', Rule::in(array_keys(config('tms.paper_types', [])))],
            'new_expires_at'  => ['required', 'date'],
            'cost'            => ['nullable', 'numeric', 'min:0'],
            'receipt_number'  => ['nullable', 'string', 'max:64'],
            'notes'           => ['nullable', 'string', 'max:2000'],
            'document'        => ['nullable', 'file', 'max:5120', 'mimes:pdf,jpg,jpeg,png'],
        ]);

        $this->paperService->recordRenewal(
            $vehicle,
            $validated,
            $request->user(),
            $request->file('document'),
        );

        return redirect()
            ->route('admin.tms.vehicles.show', $vehicle)
            ->with('success', config('tms.paper_types.' . $validated['paper_type'], 'Paper') . ' renewal recorded.');
    }

    public function downloadDocument(Request $request, TmsVehiclePaperRenewal $renewal)
    {
        $this->authorizeFactoryAccess($request, $renewal->factory_id);

        if (! $renewal->hasDocument()) {
            abort(404);
        }

        $extension = pathinfo($renewal->document_path, PATHINFO_EXTENSION) ?: 'bin';

        return Storage::disk('public')->download(
            $renewal->document_path,
            "vehicle-{$renewal->vehicle_id}-{$renewal->paper_type}-renewal.{$extension}",
        );
    }

    private function buildVehiclePapersQuery(Request $request): Builder
    {
        $query = TmsVehicle::query()
            ->with(['factory', 'allocatedEmployee.designation', 'primaryDriver.employee'])
            ->orderBy('factory_id')
            ->orderBy('name');

        $this->scopeToUserFactory($query, $request);

        if ($request->filled('factory_id')) {
            $query->where('factory_id', $request->factory_id);
        }

        if ($request->filled('search')) {
            $term = $request->search;
            $query->where(function ($q) use ($term) {
                $q->where('name', 'like', "%{$term}%")
                    ->orWhere('reg_number', 'like', "%{$term}%");
            });
        }

        return $query;
    }

    private function resolvePerPage(Request $request): int
    {
        $perPage = (int) $request->query('per_page', 25);

        return array_key_exists($perPage, $this->perPageOptions()) ? $perPage : 25;
    }

    /** @return array<int, string> */
    private function perPageOptions(): array
    {
        return [10 => '10', 25 => '25', 50 => '50', 100 => '100'];
    }

    private function resolveVehicles(Request $request, ?int $perPage = null): Collection|LengthAwarePaginator
    {
        $query = $this->buildVehiclePapersQuery($request);

        if ($request->filled('paper_status')) {
            $status = $request->paper_status;
            $filtered = $query->get()->filter(
                fn (TmsVehicle $vehicle) => $this->paperService->worstStatusForVehicle($vehicle) === $status
            )->values();

            if ($perPage === null) {
                return $filtered;
            }

            $page = max(1, (int) $request->get('page', 1));

            return new LengthAwarePaginator(
                $filtered->slice(($page - 1) * $perPage, $perPage)->values(),
                $filtered->count(),
                $perPage,
                $page,
                ['path' => $request->url(), 'query' => $request->query()],
            );
        }

        if ($perPage === null) {
            return $query->get();
        }

        return $query->paginate($perPage)->withQueryString();
    }

    /** @return array<string, mixed> */
    private function viewData(Request $request, Collection|LengthAwarePaginator $vehicles, int $perPage): array
    {
        return [
            'vehicles'       => $vehicles,
            'paginator'      => $vehicles,
            'factories'      => $this->factoryOptions($request),
            'paperTypes'     => config('tms.paper_types'),
            'filters'        => $request->only(['factory_id', 'paper_status', 'search', 'per_page']),
            'perPageOptions' => $this->perPageOptions(),
            'statuses'       => [
                VehiclePaperService::STATUS_EXPIRED => 'Expired',
                VehiclePaperService::STATUS_URGENT  => 'Urgent (≤30 days)',
                VehiclePaperService::STATUS_WARNING => 'Warning (≤60 days)',
                VehiclePaperService::STATUS_MISSING => 'Missing dates',
                VehiclePaperService::STATUS_OK      => 'All OK',
            ],
            'paperService' => $this->paperService,
        ];
    }
}
