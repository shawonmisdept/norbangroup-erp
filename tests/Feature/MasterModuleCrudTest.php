<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MasterModuleCrudTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    /** @var array<string, int> */
    private array $recordIds = [];

    protected function setUp(): void
    {
        parent::setUp();

        $role = Role::create([
            'name'        => 'Test Admin',
            'permissions' => ['masters.view', 'masters.manage', 'orders.view'],
        ]);

        $this->admin = User::create([
            'name'     => 'Test Admin',
            'email'    => 'admin-e2e@test.com',
            'password' => 'password',
            'role_id'  => $role->id,
        ]);
    }

    public function test_master_hub_requires_auth(): void
    {
        $this->get(route('admin.masters.hub'))->assertRedirect(route('login'));
    }

    public function test_viewer_cannot_manage_masters(): void
    {
        $viewerRole = Role::create([
            'name'        => 'Viewer Only',
            'permissions' => ['masters.view'],
        ]);

        $viewer = User::create([
            'name'     => 'Viewer User',
            'email'    => 'viewer-e2e@test.com',
            'password' => 'password',
            'role_id'  => $viewerRole->id,
        ]);

        $this->actingAs($viewer)
            ->get(route('admin.masters.hub'))
            ->assertOk();

        $this->actingAs($viewer)
            ->get(route('admin.masters.create', 'factories'))
            ->assertForbidden();

        $this->actingAs($viewer)
            ->post(route('admin.masters.store', 'factories'), ['name' => 'X', 'is_active' => 1])
            ->assertForbidden();
    }

    public function test_all_seventeen_master_modules_crud_end_to_end(): void
    {
        Storage::fake('public');

        $this->actingAs($this->admin)
            ->get(route('admin.masters.hub'))
            ->assertOk()
            ->assertSee('Master Data Registry');

        $createOrder = [
            'factories', 'departments', 'designations', 'buyers', 'brands', 'seasons', 'classes',
            'items', 'colors', 'sizes', 'material-types', 'materials', 'fabrications',
            'compositions', 'fabric-types', 'gsms', 'sample-types',
        ];

        foreach ($createOrder as $module) {
            $this->runModuleCreateFlow($module);
        }

        foreach ($createOrder as $module) {
            $this->runModuleReadAndUpdateFlow($module);
        }

        foreach (array_reverse($createOrder) as $module) {
            $this->runModuleDeleteFlow($module);
        }

        $this->assertCount(17, $this->recordIds);
    }

    private function runModuleCreateFlow(string $module): void
    {
        $label = config("masters.modules.{$module}.label");

        $this->actingAs($this->admin)
            ->get(route('admin.masters.index', $module))
            ->assertOk()
            ->assertSee(config("masters.modules.{$module}.label_plural"));

        $this->actingAs($this->admin)
            ->get(route('admin.masters.create', $module))
            ->assertOk()
            ->assertSee('New ' . $label);

        $payload = $this->buildPayload($module);

        $response = $this->actingAs($this->admin)
            ->post(route('admin.masters.store', $module), $payload);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $modelClass = config("masters.modules.{$module}.model");
        $record = $modelClass::where('name', $payload['name'])->first();

        $this->assertNotNull($record, "Failed to create {$module} record");
        $this->assertNotEmpty($record->code);
        $this->assertTrue($record->is_active);

        $this->recordIds[$module] = $record->id;

        $this->actingAs($this->admin)
            ->get(route('admin.masters.show', [$module, $record->id]))
            ->assertOk()
            ->assertSee($payload['name'])
            ->assertSee($record->code);
    }

    private function runModuleReadAndUpdateFlow(string $module): void
    {
        $id = $this->recordIds[$module];
        $modelClass = config("masters.modules.{$module}.model");
        $record = $modelClass::findOrFail($id);

        $this->actingAs($this->admin)
            ->get(route('admin.masters.index', $module) . '?search=' . urlencode($record->name))
            ->assertOk()
            ->assertSee($record->name);

        $this->actingAs($this->admin)
            ->get(route('admin.masters.edit', [$module, $id]))
            ->assertOk()
            ->assertSee('Edit');

        $updatedName = $record->name . ' Updated';
        $payload = $this->buildPayload($module, 'Updated');
        $payload['name'] = $updatedName;
        $payload['is_active'] = 0;

        $this->actingAs($this->admin)
            ->put(route('admin.masters.update', [$module, $id]), $payload)
            ->assertRedirect(route('admin.masters.show', [$module, $id]))
            ->assertSessionHas('success');

        $record->refresh();
        $this->assertSame($updatedName, $record->name);
        $this->assertFalse($record->is_active);
    }

    private function runModuleDeleteFlow(string $module): void
    {
        $id = $this->recordIds[$module];
        $modelClass = config("masters.modules.{$module}.model");

        $this->actingAs($this->admin)
            ->delete(route('admin.masters.destroy', [$module, $id]))
            ->assertRedirect(route('admin.masters.index', $module))
            ->assertSessionHas('success');

        $this->assertNull($modelClass::find($id));
    }

    private function buildPayload(string $module, string $suffix = 'A'): array
    {
        $label = config("masters.modules.{$module}.label");
        $payload = [
            'name'      => "E2E {$label} {$suffix}",
            'is_active' => 1,
        ];

        return match ($module) {
            'factories' => array_merge($payload, [
                'address' => 'Dhaka, Bangladesh',
                'phone'   => '01700000001',
            ]),
            'departments' => array_merge($payload, [
                'factory_id' => $this->recordIds['factories'],
            ]),
            'designations' => array_merge($payload, [
                'department_id' => $this->recordIds['departments'],
            ]),
            'buyers' => array_merge($payload, [
                'company' => 'E2E Company',
                'email'   => 'buyer-e2e@example.com',
                'phone'   => '01700000002',
                'country' => 'Bangladesh',
            ]),
            'brands' => array_merge($payload, [
                'buyer_id' => $this->recordIds['buyers'],
            ]),
            'seasons' => array_merge($payload, [
                'year'       => 2026,
                'start_date' => '2026-01-01',
                'end_date'   => '2026-12-31',
            ]),
            'classes' => array_merge($payload, [
                'buyer_id' => $this->recordIds['buyers'],
            ]),
            'items' => $this->buildItemPayload($payload),
            'colors' => array_merge($payload, [
                'hex_code' => '#FF5733',
            ]),
            'sizes' => array_merge($payload, [
                'sort_order' => 10,
            ]),
            'materials' => array_merge($payload, [
                'material_type_id' => $this->recordIds['material-types'],
                'unit'             => 'kg',
            ]),
            'gsms' => array_merge($payload, [
                'value' => 180,
            ]),
            default => $payload,
        };
    }

    private function buildItemPayload(array $payload): array
    {
        $payload['image'] = UploadedFile::fake()->image('item.jpg', 600, 600);

        return $payload;
    }
}
