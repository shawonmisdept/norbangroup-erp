<?php

namespace App\Http\Controllers\Admin\Hrm;

use App\Http\Controllers\Admin\Hrm\Concerns\ScopesHrmFactory;
use App\Http\Controllers\Controller;
use App\Models\Hrm\HrLetterTemplate;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class LetterTemplateController extends Controller
{
    use ScopesHrmFactory;

    public function index(Request $request)
    {
        $this->ensureCanView($request);

        $query = HrLetterTemplate::query()->with('factory')->orderBy('name');

        $scopedFactoryId = $request->user()?->scopedFactoryId();
        if ($scopedFactoryId) {
            $query->where(fn ($q) => $q
                ->whereNull('factory_id')
                ->orWhere('factory_id', $scopedFactoryId));
        }

        if ($request->filled('factory_id')) {
            $query->where(fn ($q) => $q
                ->whereNull('factory_id')
                ->orWhere('factory_id', $request->factory_id));
        }

        if ($request->filled('letter_type')) {
            $query->where('letter_type', $request->letter_type);
        }

        return view('admin.hrm.letters.templates.index', [
            'templates'   => $query->paginate(25)->withQueryString(),
            'factories'   => $this->factoryOptions($request),
            'letterTypes' => config('hrm.letter_types', []),
            'filters'     => $request->only(['factory_id', 'letter_type']),
            'canManage'   => $request->user()?->hasPermission('hrm.employees.letters.manage') ?? false,
        ]);
    }

    public function create(Request $request)
    {
        $this->ensureCanManage($request);

        return view('admin.hrm.letters.templates.form', [
            'template'    => new HrLetterTemplate(['is_active' => true]),
            'factories'   => $this->factoryOptions($request),
            'letterTypes' => config('hrm.letter_types', []),
        ]);
    }

    public function store(Request $request)
    {
        $this->ensureCanManage($request);

        $validated = $this->validateTemplate($request);
        if ($validated['factory_id']) {
            $this->authorizeFactoryAccess($request, (int) $validated['factory_id']);
        }

        HrLetterTemplate::create($validated);

        return redirect()->route('admin.hrm.letter-templates.index')
            ->with('success', 'Letter template created.');
    }

    public function edit(Request $request, HrLetterTemplate $letterTemplate)
    {
        $this->ensureCanManage($request);
        $this->authorizeTemplateAccess($request, $letterTemplate);

        return view('admin.hrm.letters.templates.form', [
            'template'    => $letterTemplate,
            'factories'   => $this->factoryOptions($request),
            'letterTypes' => config('hrm.letter_types', []),
        ]);
    }

    public function update(Request $request, HrLetterTemplate $letterTemplate)
    {
        $this->ensureCanManage($request);
        $this->authorizeTemplateAccess($request, $letterTemplate);

        $validated = $this->validateTemplate($request, $letterTemplate);
        if ($validated['factory_id']) {
            $this->authorizeFactoryAccess($request, (int) $validated['factory_id']);
        }

        $letterTemplate->update($validated);

        return redirect()->route('admin.hrm.letter-templates.index')
            ->with('success', 'Letter template updated.');
    }

    public function destroy(Request $request, HrLetterTemplate $letterTemplate)
    {
        $this->ensureCanManage($request);
        $this->authorizeTemplateAccess($request, $letterTemplate);

        if ($letterTemplate->issuedLetters()->exists()) {
            return back()->with('error', 'Cannot delete a template that has issued letters.');
        }

        $letterTemplate->delete();

        return redirect()->route('admin.hrm.letter-templates.index')
            ->with('success', 'Letter template deleted.');
    }

    /** @return array<string, mixed> */
    private function validateTemplate(Request $request, ?HrLetterTemplate $template = null): array
    {
        $validated = $request->validate([
            'factory_id'  => ['nullable', 'exists:factories,id'],
            'code'        => [
                'required', 'string', 'max:40', 'alpha_dash',
                Rule::unique('hrm_hr_letter_templates', 'code')->ignore($template?->id),
            ],
            'name'        => ['required', 'string', 'max:255'],
            'letter_type' => ['required', Rule::in(array_keys(config('hrm.letter_types', [])))],
            'body'        => ['required', 'string', 'max:20000'],
            'is_active'   => ['sometimes', 'boolean'],
        ]);

        $validated['factory_id'] = $validated['factory_id'] ?: null;
        $validated['is_active'] = $request->boolean('is_active', true);

        return $validated;
    }

    private function authorizeTemplateAccess(Request $request, HrLetterTemplate $template): void
    {
        if ($template->factory_id) {
            $this->authorizeFactoryAccess($request, $template->factory_id);
        }
    }

    private function ensureCanView(Request $request): void
    {
        if (! $request->user()?->hasPermission('hrm.employees.letters.view')) {
            abort(403, 'You do not have permission to view HR letter templates.');
        }
    }

    private function ensureCanManage(Request $request): void
    {
        if (! $request->user()?->hasPermission('hrm.employees.letters.manage')) {
            abort(403, 'You do not have permission to manage HR letter templates.');
        }
    }
}
