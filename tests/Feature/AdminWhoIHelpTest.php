<?php

use App\Models\ActivityLog;
use App\Models\User;
use App\Models\WhoIHelpSection;
use App\Services\Authorization\PermissionSyncService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

uses(LazilyRefreshDatabase::class);

beforeEach(function (): void {
    $this->withoutVite();
    app(PermissionRegistrar::class)->forgetCachedPermissions();
    app(PermissionSyncService::class)->sync();
    $this->superAdmin = User::factory()->create(['is_active' => true]);
    $this->superAdmin->assignRole(Role::findOrCreate('super-admin', 'web'));
});

function whoIHelpCrudAdmin(array $permissions): User
{
    $role = Role::findOrCreate('who-i-help-crud-'.Str::random(8), 'web');
    $role->syncPermissions(['admin.access', ...$permissions]);
    $admin = User::factory()->create(['is_active' => true]);
    $admin->assignRole($role);

    return $admin;
}

function whoIHelpCrudPayload(array $overrides = []): array
{
    return array_replace([
        'eyebrow' => 'WHO I HELP',
        'heading' => 'Saved heading',
        'description' => 'Saved description',
        'bottom_message' => 'Saved bottom message',
        'is_active' => '1',
        'items_present' => '1',
        'items' => [
            ['title' => 'First saved card', 'is_active' => '1'],
            ['title' => 'Second saved card', 'is_active' => '0'],
        ],
    ], $overrides);
}

function savedWhoIHelpSection(User $admin, array $attributes = [], array $items = []): WhoIHelpSection
{
    $section = WhoIHelpSection::query()->create(array_replace([
        'language' => 'en',
        'eyebrow' => 'Existing eyebrow',
        'heading' => 'Existing heading',
        'description' => 'Existing description',
        'bottom_message' => 'Existing bottom message',
        'is_active' => true,
        'created_by' => $admin->id,
        'updated_by' => $admin->id,
    ], $attributes));

    foreach ($items as $sortOrder => $item) {
        $section->items()->create(array_replace([
            'title' => 'Card '.($sortOrder + 1),
            'sort_order' => $sortOrder,
            'is_active' => true,
        ], $item));
    }

    return $section->fresh('items');
}

test('listing shows saved records only and Add New is enabled only while no section exists', function (): void {
    $admin = whoIHelpCrudAdmin(['who-i-help.view', 'who-i-help.create']);

    $this->actingAs($admin)
        ->get(route('admin.who-i-help.index'))
        ->assertSuccessful()
        ->assertSee('No Who I Help section has been saved yet.')
        ->assertSee(route('admin.who-i-help.create'), false)
        ->assertDontSee('aria-disabled="true"', false);

    $section = savedWhoIHelpSection($this->superAdmin, items: [['title' => 'Persisted card']]);

    $this->actingAs($admin)
        ->get(route('admin.who-i-help.index'))
        ->assertSuccessful()
        ->assertSee($section->eyebrow)
        ->assertSee($section->heading)
        ->assertSee('1')
        ->assertSee('aria-disabled="true"', false)
        ->assertDontSee(route('admin.who-i-help.create'), false);
});

test('create form loads defaults without persisting them and permissions are enforced', function (): void {
    $creator = whoIHelpCrudAdmin(['who-i-help.create']);

    $this->actingAs($creator)
        ->get(route('admin.who-i-help.create'))
        ->assertSuccessful()
        ->assertSee('WHO I HELP')
        ->assertSee("When Life Feels Overwhelming, It's Time to Understand Yourself.");

    expect(WhoIHelpSection::query()->doesntExist())->toBeTrue();

    $this->actingAs(whoIHelpCrudAdmin(['who-i-help.view']))
        ->get(route('admin.who-i-help.create'))
        ->assertForbidden();
});

test('only eyebrow heading and section status are required while optional values and zero cards save', function (): void {
    $response = $this->actingAs($this->superAdmin)
        ->from(route('admin.who-i-help.create'))
        ->post(route('admin.who-i-help.store'), whoIHelpCrudPayload([
            'eyebrow' => '   ',
            'heading' => '   ',
            'is_active' => null,
            'description' => '',
            'bottom_message' => '',
            'items' => [],
        ]));

    $response->assertRedirect(route('admin.who-i-help.create'));
    expect(WhoIHelpSection::query()->doesntExist())->toBeTrue();

    $this->actingAs($this->superAdmin)
        ->post(route('admin.who-i-help.store'), whoIHelpCrudPayload([
            'description' => '  ',
            'bottom_message' => '',
            'items' => [],
        ]))
        ->assertRedirect(route('admin.who-i-help.index'));

    $section = WhoIHelpSection::query()->sole();
    expect($section->description)->toBeNull()
        ->and($section->bottom_message)->toBeNull()
        ->and($section->items()->count())->toBe(0);
});

test('store rejects a duplicate configured-language section and ignores blank new cards', function (): void {
    savedWhoIHelpSection($this->superAdmin);

    $this->actingAs($this->superAdmin)
        ->from(route('admin.who-i-help.create'))
        ->post(route('admin.who-i-help.store'), whoIHelpCrudPayload())
        ->assertRedirect(route('admin.who-i-help.create'))
        ->assertSessionHasErrors('eyebrow');

    expect(WhoIHelpSection::query()->count())->toBe(1);

    WhoIHelpSection::query()->delete();
    $this->actingAs($this->superAdmin)
        ->post(route('admin.who-i-help.store'), whoIHelpCrudPayload([
            'items' => [
                ['title' => '   ', 'is_active' => '0'],
                ['title' => 'Non-empty new card'],
            ],
        ]))
        ->assertRedirect(route('admin.who-i-help.index'));

    $section = WhoIHelpSection::query()->with('items')->sole();
    expect($section->items->pluck('title')->all())->toBe(['Non-empty new card'])
        ->and($section->items->first()->is_active)->toBeTrue();
});

test('view edit update ordering and stable card IDs work through CRUD routes', function (): void {
    $section = savedWhoIHelpSection($this->superAdmin, items: [
        ['title' => 'First'],
        ['title' => 'Second'],
    ]);
    $first = $section->items[0];
    $second = $section->items[1];

    $this->actingAs($this->superAdmin)
        ->get(route('admin.who-i-help.show', $section->id))
        ->assertSuccessful()
        ->assertSee('Existing heading')
        ->assertSee('First');

    $this->actingAs($this->superAdmin)
        ->put(route('admin.who-i-help.update', $section->id), whoIHelpCrudPayload([
            'heading' => 'Updated heading',
            'items' => [
                ['id' => $second->id, 'title' => 'Second updated', 'is_active' => '0'],
                ['id' => $first->id, 'title' => 'First updated', 'is_active' => '1'],
                ['title' => 'New card'],
            ],
        ]))
        ->assertRedirect(route('admin.who-i-help.index'));

    $section->refresh()->load('items');
    expect($section->heading)->toBe('Updated heading')
        ->and($section->items->pluck('id')->take(2)->all())->toBe([$second->id, $first->id])
        ->and($section->items->pluck('title')->all())->toBe(['Second updated', 'First updated', 'New card'])
        ->and($section->items->pluck('sort_order')->all())->toBe([0, 1, 2]);
});

test('blank existing cards and explicit remove-all safely remove cards while omitted marker preserves them', function (): void {
    $section = savedWhoIHelpSection($this->superAdmin, items: [['title' => 'Keep'], ['title' => 'Clear']]);
    $keep = $section->items[0];
    $clear = $section->items[1];

    $this->actingAs($this->superAdmin)
        ->put(route('admin.who-i-help.update', $section->id), whoIHelpCrudPayload([
            'items' => [
                ['id' => $keep->id, 'title' => 'Keep', 'is_active' => '1'],
                ['id' => $clear->id, 'title' => '   ', 'is_active' => '1'],
            ],
        ]))
        ->assertRedirect(route('admin.who-i-help.index'));

    expect($section->items()->pluck('title')->all())->toBe(['Keep']);

    $payload = whoIHelpCrudPayload();
    unset($payload['items'], $payload['items_present']);
    $payload['heading'] = 'Section-only update';
    $this->actingAs($this->superAdmin)
        ->put(route('admin.who-i-help.update', $section->id), $payload)
        ->assertRedirect(route('admin.who-i-help.index'));

    expect($section->fresh()->heading)->toBe('Section-only update')
        ->and($section->items()->pluck('title')->all())->toBe(['Keep']);

    $this->actingAs($this->superAdmin)
        ->put(route('admin.who-i-help.update', $section->id), whoIHelpCrudPayload(['items' => []]))
        ->assertRedirect(route('admin.who-i-help.index'));

    expect($section->items()->doesntExist())->toBeTrue();
});

test('cross-section IDs are rejected and delete removes only the selected section with activity logs', function (): void {
    $section = savedWhoIHelpSection($this->superAdmin, items: [['title' => 'English card']]);
    $other = savedWhoIHelpSection($this->superAdmin, ['language' => 'ur'], [['title' => 'Foreign card']]);

    $this->actingAs($this->superAdmin)
        ->from(route('admin.who-i-help.edit', $section->id))
        ->put(route('admin.who-i-help.update', $section->id), whoIHelpCrudPayload([
            'items' => [['id' => $other->items->first()->id, 'title' => 'Invalid', 'is_active' => '1']],
        ]))
        ->assertRedirect(route('admin.who-i-help.edit', $section->id))
        ->assertSessionHasErrors('items.0.id');

    expect($section->items()->value('title'))->toBe('English card')
        ->and($other->items()->value('title'))->toBe('Foreign card');

    $this->actingAs($this->superAdmin)
        ->delete(route('admin.who-i-help.destroy', $section->id))
        ->assertRedirect(route('admin.who-i-help.index'));

    expect(WhoIHelpSection::query()->whereKey($section->id)->doesntExist())->toBeTrue()
        ->and(WhoIHelpSection::query()->whereKey($other->id)->exists())->toBeTrue()
        ->and(ActivityLog::query()->where('module', 'who_i_help')->where('action', 'deleted')->exists())->toBeTrue();
});

test('frontend remains hidden when a section has no active non-empty cards', function (): void {
    savedWhoIHelpSection($this->superAdmin, items: [['title' => '   ', 'is_active' => true], ['title' => 'Inactive', 'is_active' => false]]);

    $this->get(route('frontend.home'))
        ->assertSuccessful()
        ->assertDontSee('front-who-i-help', false);
});

test('the effective test connection is isolated SQLite memory', function (): void {
    expect(config('database.default'))->toBe('sqlite')
        ->and(config('database.connections.sqlite.database'))->toBe(':memory:');
});
