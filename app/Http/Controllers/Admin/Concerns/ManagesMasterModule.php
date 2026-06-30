<?php

namespace App\Http\Controllers\Admin\Concerns;

use App\Models\Item;
use App\Services\MasterImageService;
use App\Support\RelationDisplay;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;

trait ManagesMasterModule
{
    abstract protected function masterModulesConfigKey(): string;

    abstract protected function masterRoutePrefix(): string;

    abstract protected function masterHubLabel(): string;

    abstract protected function masterPermissionNamespace(): string;

    public function hub()
    {
        $user = auth()->user();
        $groupsConfigKey = $this->masterModulesConfigKey() === 'hrm.modules'
            ? 'hrm.groups'
            : 'masters.groups';

        $groups = collect(config($groupsConfigKey))
            ->map(function (array $modules) use ($user) {
                return array_values(array_filter(
                    $modules,
                    fn (string $moduleKey) => $user->canViewMasterModule($this->masterPermissionNamespace(), $moduleKey)
                ));
            })
            ->filter(fn (array $modules) => $modules !== [])
            ->all();

        return view('admin.masters.hub', $this->masterViewData(compact('groups')));
    }

    public function index(Request $request, string $module)
    {
        $config = $this->moduleConfig($module);
        $model = $this->modelClass($config);

        $query = $model::query();

        if (! empty($config['with'])) {
            $query->with($config['with']);
        }

        $this->scopeMasterQuery($query, $request);

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

        return view('admin.masters.index', $this->masterViewData(compact('module', 'config', 'records')));
    }

    public function create(string $module)
    {
        $config = $this->moduleConfig($module);

        return view('admin.masters.create', $this->masterViewData([
            'module'    => $module,
            'config'    => $config,
            'relations' => $this->relationOptions($config),
        ]));
    }

    public function store(Request $request, string $module)
    {
        $config = $this->moduleConfig($module);
        $data = $this->normalizeData($request->validate($this->validationRules($config, $module)), $config);

        if ($request->hasFile('image')) {
            $data['image'] = MasterImageService::store($request->file('image'), 'masters/items');
        }

        $data = $this->applyBooleanFields($request, $config, $data);
        $data = $this->enforceMasterFactoryScope($request, $data);

        $record = $this->modelClass($config)::create($data);

        return redirect()->route("{$this->masterRoutePrefix()}.show", [$module, $record])
            ->with('success', $config['label'] . ' created successfully.');
    }

    public function show(string $module, int $id)
    {
        $config = $this->moduleConfig($module);
        $record = $this->findRecord($config, $id);

        return view('admin.masters.show', $this->masterViewData(compact('module', 'config', 'record')));
    }

    public function edit(string $module, int $id)
    {
        $config = $this->moduleConfig($module);
        $record = $this->findRecord($config, $id);

        return view('admin.masters.edit', $this->masterViewData([
            'module'    => $module,
            'config'    => $config,
            'record'    => $record,
            'relations' => $this->relationOptions($config),
        ]));
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

        $data = $this->applyBooleanFields($request, $config, $data);
        $data = $this->enforceMasterFactoryScope($request, $data);

        $record->update($data);

        return redirect()->route("{$this->masterRoutePrefix()}.show", [$module, $record])
            ->with('success', $config['label'] . ' updated successfully.');
    }

    public function destroy(string $module, int $id)
    {
        $config = $this->moduleConfig($module);
        $record = $this->findRecord($config, $id);
        $record->delete();

        return redirect()->route("{$this->masterRoutePrefix()}.index", $module)
            ->with('success', $config['label'] . ' deleted successfully.');
    }

    protected function masterViewData(array $data = []): array
    {
        return array_merge([
            'routePrefix'            => $this->masterRoutePrefix(),
            'hubLabel'               => $this->masterHubLabel(),
            'modulesConfigKey'       => $this->masterModulesConfigKey(),
            'masterNamespace'        => $this->masterPermissionNamespace(),
            'relationColumnsConfig'  => $this->relationColumnsConfigKey(),
        ], $data);
    }

    protected function relationColumnsConfigKey(): string
    {
        return $this->masterPermissionNamespace() === 'hrm'
            ? 'hrm.relation_columns'
            : 'masters.relation_columns';
    }

    private function moduleConfig(string $module): array
    {
        $config = config("{$this->masterModulesConfigKey()}.{$module}");

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

        $this->scopeMasterQuery($query, request());

        $record = $query->findOrFail($id);
        $this->authorizeMasterRecord($record);

        return $record;
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
            $displayWith = $meta['display_with'] ?? null;
            $query = $model::query();
            $table = (new $model)->getTable();

            if ($displayWith) {
                $relationName = Str::before($displayWith, '.');
                $query->with($relationName);

                if ($relationName === 'factory' && $table === 'departments') {
                    $query->join('factories', 'departments.factory_id', '=', 'factories.id')
                        ->orderBy('factories.name')
                        ->orderBy("{$table}.{$display}")
                        ->select("{$table}.*");
                } else {
                    $query->orderBy($display);
                }
            } else {
                $query->orderBy($display);
            }

            $this->scopeRelationQuery($query, $model);

            $options[$field] = $displayWith
                ? $query->get()->mapWithKeys(fn ($record) => [
                    $record->id => RelationDisplay::label($record, $display, $displayWith),
                ])
                : $query->pluck($display, 'id');
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

            if (($meta['type'] ?? '') === 'boolean') {
                $rules[$field] = ['nullable', 'boolean'];
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
                case 'time':
                    $fieldRules[] = 'date_format:H:i';
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

    private function applyBooleanFields(Request $request, array $config, array $data): array
    {
        foreach ($config['fields'] as $field => $meta) {
            if (($meta['type'] ?? '') === 'boolean') {
                $data[$field] = $request->boolean($field);
            }
        }

        return $data;
    }

    protected function scopeMasterQuery($query, Request $request): void
    {
        if ($this->masterPermissionNamespace() === 'hrm' && method_exists($this, 'scopeToUserFactory')) {
            $this->scopeToUserFactory($query, $request);
        }
    }

    protected function scopeRelationQuery($query, string $modelClass): void
    {
        if ($this->masterPermissionNamespace() !== 'hrm') {
            return;
        }

        $factoryId = auth()->user()?->factory_id;

        if (! $factoryId) {
            return;
        }

        $table = (new $modelClass)->getTable();

        if (Schema::hasColumn($table, 'factory_id')) {
            $query->where($table . '.factory_id', $factoryId);
        }
    }

    protected function enforceMasterFactoryScope(Request $request, array $data): array
    {
        if ($this->masterPermissionNamespace() !== 'hrm') {
            return $data;
        }

        if ($request->user()?->scopedFactoryId()) {
            $data['factory_id'] = $request->user()->scopedFactoryId();
        } elseif (isset($data['factory_id']) && method_exists($this, 'authorizeFactoryAccess')) {
            $this->authorizeFactoryAccess($request, (int) $data['factory_id']);
        }

        return $data;
    }

    protected function authorizeMasterRecord(Model $record): void
    {
        if ($this->masterPermissionNamespace() !== 'hrm') {
            return;
        }

        if (! isset($record->factory_id) || ! method_exists($this, 'authorizeFactoryAccess')) {
            return;
        }

        $this->authorizeFactoryAccess(request(), (int) $record->factory_id);
    }
}
