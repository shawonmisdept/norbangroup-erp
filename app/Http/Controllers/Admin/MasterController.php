<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Item;
use App\Services\MasterImageService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class MasterController extends Controller
{
    public function hub()
    {
        $user = auth()->user();

        $groups = collect(config('masters.groups'))
            ->map(function (array $modules) use ($user) {
                return array_values(array_filter(
                    $modules,
                    fn (string $moduleKey) => $user->canViewMaster($moduleKey)
                ));
            })
            ->filter(fn (array $modules) => $modules !== [])
            ->all();

        return view('admin.masters.hub', compact('groups'));
    }

    public function index(Request $request, string $module)
    {
        $config = $this->moduleConfig($module);
        $model = $this->modelClass($config);

        $query = $model::query();

        if (! empty($config['with'])) {
            $query->with($config['with']);
        }

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search, $config) {
                foreach ($config['search'] ?? ['name'] as $column) {
                    $q->orWhere($column, 'like', "%{$search}%");
                }
            });
        }

        foreach ($config['default_order'] ?? ['name' => 'asc'] as $column => $direction) {
            $query->orderBy($column, $direction);
        }

        $records = $query->paginate(15)->withQueryString();

        return view('admin.masters.index', compact('module', 'config', 'records'));
    }

    public function create(string $module)
    {
        $config = $this->moduleConfig($module);

        return view('admin.masters.create', [
            'module'    => $module,
            'config'    => $config,
            'relations' => $this->relationOptions($config),
        ]);
    }

    public function store(Request $request, string $module)
    {
        $config = $this->moduleConfig($module);
        $data = $this->normalizeData($request->validate($this->validationRules($config, $module)), $config);

        if ($request->hasFile('image')) {
            $data['image'] = MasterImageService::store($request->file('image'), 'masters/items');
        }

        $data['is_active'] = $request->boolean('is_active');

        $record = $this->modelClass($config)::create($data);

        return redirect()->route('admin.masters.show', [$module, $record])
            ->with('success', $config['label'] . ' created successfully.');
    }

    public function show(string $module, int $id)
    {
        $config = $this->moduleConfig($module);
        $record = $this->findRecord($config, $id);

        return view('admin.masters.show', compact('module', 'config', 'record'));
    }

    public function edit(string $module, int $id)
    {
        $config = $this->moduleConfig($module);
        $record = $this->findRecord($config, $id);

        return view('admin.masters.edit', [
            'module'    => $module,
            'config'    => $config,
            'record'    => $record,
            'relations' => $this->relationOptions($config),
        ]);
    }

    public function update(Request $request, string $module, int $id)
    {
        $config = $this->moduleConfig($module);
        $record = $this->findRecord($config, $id);
        $data = $this->normalizeData($request->validate($this->validationRules($config, $module, $record)), $config);

        if ($request->hasFile('image')) {
            $data['image'] = MasterImageService::store(
                $request->file('image'),
                'masters/items',
                400,
                $record instanceof Item ? $record->image : null
            );
        }

        $data['is_active'] = $request->boolean('is_active');

        $record->update($data);

        return redirect()->route('admin.masters.show', [$module, $record])
            ->with('success', $config['label'] . ' updated successfully.');
    }

    public function destroy(string $module, int $id)
    {
        $config = $this->moduleConfig($module);
        $record = $this->findRecord($config, $id);
        $record->delete();

        return redirect()->route('admin.masters.index', $module)
            ->with('success', $config['label'] . ' deleted successfully.');
    }

    private function moduleConfig(string $module): array
    {
        $config = config("masters.modules.{$module}");

        if (! $config) {
            abort(404);
        }

        return $config;
    }

    private function modelClass(array $config): string
    {
        return $config['model'];
    }

    private function findRecord(array $config, int $id): Model
    {
        $query = $this->modelClass($config)::query();

        if (! empty($config['with'])) {
            $query->with($config['with']);
        }

        return $query->findOrFail($id);
    }

    private function relationOptions(array $config): array
    {
        $options = [];

        foreach ($config['fields'] as $field => $meta) {
            if (($meta['type'] ?? '') !== 'relation') {
                continue;
            }

            $model = $meta['relation'];
            $display = $meta['display'] ?? 'name';
            $options[$field] = $model::orderBy($display)->pluck($display, 'id');
        }

        return $options;
    }

    private function validationRules(array $config, string $module, ?Model $record = null): array
    {
        $rules = [];

        foreach ($config['fields'] as $field => $meta) {
            if ($field === 'image') {
                $rules['image'] = ['nullable', 'image', 'max:5120'];
                continue;
            }

            $fieldRules = [];

            if ($meta['required'] ?? false) {
                $fieldRules[] = 'required';
            } else {
                $fieldRules[] = 'nullable';
            }

            switch ($meta['type']) {
                case 'email':
                    $fieldRules[] = 'email';
                    $fieldRules[] = 'max:255';
                    break;
                case 'textarea':
                    $fieldRules[] = 'string';
                    $fieldRules[] = 'max:2000';
                    break;
                case 'select':
                    $fieldRules[] = 'string';
                    $fieldRules[] = 'max:255';
                    if (! empty($meta['options'])) {
                        $fieldRules[] = Rule::in($meta['options']);
                    }
                    break;
                case 'number':
                    $fieldRules[] = 'integer';
                    if (isset($meta['min'])) {
                        $fieldRules[] = 'min:' . $meta['min'];
                    }
                    break;
                case 'date':
                    $fieldRules[] = 'date';
                    break;
                case 'relation':
                    $table = (new $meta['relation'])->getTable();
                    $fieldRules[] = Rule::exists($table, 'id');
                    break;
                default:
                    $fieldRules[] = 'string';
                    $fieldRules[] = 'max:255';
                    break;
            }

            $rules[$field] = $fieldRules;
        }

        $rules['is_active'] = ['nullable', 'boolean'];

        return $rules;
    }

    private function normalizeData(array $data, array $config): array
    {
        foreach ($config['fields'] as $field => $meta) {
            if (($meta['type'] ?? '') === 'relation' && array_key_exists($field, $data) && $data[$field] === '') {
                $data[$field] = null;
            }
        }

        return $data;
    }
}
