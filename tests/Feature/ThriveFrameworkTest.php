<?php

use App\Models\ActivityLog;
use App\Models\GeneralSetting;
use App\Models\ThriveFrameworkSection;
use App\Models\ThriveFrameworkStage;
use App\Models\User;
use App\Models\WhoIHelpItem;
use App\Models\WhoIHelpSection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function (): void {
    expect(app()->environment('testing'))->toBeTrue()
        ->and(config('database.default'))->toBe('sqlite')
        ->and(config('database.connections.sqlite.database'))->toBe(':memory:')
        ->and(config('database.connections.sqlite.url'))->toBeEmpty();
    config(['database.connections' => ['sqlite' => config('database.connections.sqlite')]]);
    expect(DB::connection()->getDatabaseName())->toBe(':memory:')
        ->and(DB::connection()->getPdo()->getAttribute(PDO::ATTR_DRIVER_NAME))->toBe('sqlite');
    $this->withoutVite();
    Http::preventStrayRequests();
    foreach ([new User, new ActivityLog, new GeneralSetting] as $model) {
        frameworkFixtureTable($model);
    }
    foreach (['roles', 'permissions'] as $tableName) {
        Schema::create($tableName, function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('guard_name');
            $table->timestamps();
            $table->unique(['name', 'guard_name']);
        });
    }
    foreach (['roles' => 'role_id', 'permissions' => 'permission_id'] as $suffix => $key) {
        Schema::create('model_has_'.$suffix, function (Blueprint $table) use ($key): void {
            $table->unsignedBigInteger($key);
            $table->string('model_type');
            $table->unsignedBigInteger('model_id');
            $table->primary([$key, 'model_id', 'model_type']);
        });
    }
    Schema::create('role_has_permissions', function (Blueprint $table): void {
        $table->unsignedBigInteger('permission_id');
        $table->unsignedBigInteger('role_id');
        $table->primary(['permission_id', 'role_id']);
    });
    Schema::create('thrive_framework_sections', function (Blueprint $table): void {
        $table->id();
        $table->string('language', 10)->unique();
        $table->string('eyebrow');
        $table->string('heading');
        $table->unsignedTinyInteger('cards_per_row')->default(3);
        $table->text('description_one')->nullable();
        $table->text('description_two')->nullable();
        $table->boolean('is_active')->default(true);
        $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
        $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
        $table->timestamps();
    });
    Schema::create('thrive_framework_stages', function (Blueprint $table): void {
        $table->id();
        $table->foreignId('section_id')->constrained('thrive_framework_sections')->cascadeOnDelete();
        $table->string('letter', 10);
        $table->string('stage_label', 50)->nullable();
        $table->string('title');
        $table->text('description')->nullable();
        $table->unsignedInteger('sort_order')->default(0);
        $table->boolean('is_active')->default(true);
        $table->timestamps();
    });
    app(PermissionRegistrar::class)->forgetCachedPermissions();
    $this->frameworkAdmin = User::factory()->create(['is_active' => true]);
    $this->frameworkAdmin->assignRole(Role::findOrCreate('super-admin', 'web'));
    $this->actingAs($this->frameworkAdmin);
});

afterEach(function (): void {
    DB::disconnect('sqlite');
});

function frameworkFixtureTable(Model $model): void
{
    $columns = array_unique([...$model->getFillable(), 'email_verified_at', 'remember_token', 'created_by', 'updated_by', 'parent_admin_id', 'activation_token', 'activation_token_expires_at', 'consumed_activation_token_hash']);
    Schema::create($model->getTable(), function (Blueprint $table) use ($columns): void {
        $table->id();
        foreach ($columns as $column) {
            if (! in_array($column, ['id', 'created_at', 'updated_at'], true)) {
                $table->text($column)->nullable();
            }
        }
        $table->timestamps();
    });
}

/** @return array<string, mixed> */
function frameworkPayload(array $overrides = []): array
{
    return array_replace([
        'eyebrow' => 'MY SIGNATURE APPROACH', 'heading' => 'The THRIVE™ Framework',
        'is_active' => '1', 'stages_present' => '1',
        'stages' => [
            ['letter' => 'T', 'title' => 'Saved first stage', 'description' => 'Saved first description', 'stage_label' => 'STAGE A'],
            ['letter' => 'R', 'title' => 'Saved second stage', 'is_active' => '0'],
        ],
    ], $overrides);
}

function frameworkSaved(string $language = 'en'): ThriveFrameworkSection
{
    return ThriveFrameworkSection::query()->create([
        'language' => $language, 'eyebrow' => 'Saved eyebrow', 'heading' => 'Saved framework', 'is_active' => true,
    ]);
}

function frameworkLimitedAdmin(array $permissions): User
{
    $admin = User::factory()->create(['is_active' => true]);
    $role = Role::create(['name' => 'framework-editor-'.Str::random(8), 'guard_name' => 'web']);
    foreach (['admin.access', ...$permissions] as $name) {
        $role->permissions()->attach(Permission::findOrCreate($name, 'web'));
    }
    $admin->assignRole($role);

    return $admin;
}

test('framework section status uses radios with active default saved inactive and old input', function (): void {
    $document = new DOMDocument;
    $document->loadHTML($this->get(route('admin.thrive-framework.create'))->assertSuccessful()->getContent(), LIBXML_NOERROR | LIBXML_NOWARNING);
    $xpath = new DOMXPath($document);
    expect($xpath->query('//input[@name="is_active" and @type="radio"]')->length)->toBe(2)
        ->and($xpath->query('//input[@id="framework-status-1" and @checked and @required]')->length)->toBe(1)
        ->and($xpath->query('//select[@name="is_active"]')->length)->toBe(0);
    $section = frameworkSaved();
    $section->update(['is_active' => false]);
    $document->loadHTML($this->get(route('admin.thrive-framework.edit', $section))->assertSuccessful()->getContent(), LIBXML_NOERROR | LIBXML_NOWARNING);
    expect((new DOMXPath($document))->query('//input[@id="framework-status-0" and @checked]')->length)->toBe(1);
    $document->loadHTML($this->withSession(['_old_input' => ['is_active' => '1']])->get(route('admin.thrive-framework.edit', $section))->assertSuccessful()->getContent(), LIBXML_NOERROR | LIBXML_NOWARNING);
    expect((new DOMXPath($document))->query('//input[@id="framework-status-1" and @checked]')->length)->toBe(1);
});

test('framework listing actions use accessible icons and preserve action destinations', function (): void {
    $section = frameworkSaved();
    $document = new DOMDocument;
    $document->loadHTML($this->get(route('admin.thrive-framework.index'))->assertSuccessful()->getContent(), LIBXML_NOERROR | LIBXML_NOWARNING);
    $xpath = new DOMXPath($document);
    foreach (['View' => ['show', 'fa-eye'], 'Edit' => ['edit', 'fa-pen-to-square']] as $label => [$action, $icon]) {
        $link = $xpath->query('//a[@aria-label="'.$label.' THRIVE Framework"]')->item(0);
        expect($link->getAttribute('href'))->toBe(route('admin.thrive-framework.'.$action, $section))
            ->and(trim($link->textContent))->toBe('')
            ->and($xpath->query('.//i[contains(@class,"'.$icon.'") and @aria-hidden="true"]', $link)->length)->toBe(1);
    }
    $button = $xpath->query('//button[@aria-label="Delete THRIVE Framework" and @data-delete-confirm]')->item(0);
    expect($button)->not->toBeNull()
        ->and(trim($button->textContent))->toBe('')
        ->and($button->parentNode->getAttribute('action'))->toBe(route('admin.thrive-framework.destroy', $section))
        ->and($xpath->query('.//i[contains(@class,"fa-trash") and @aria-hidden="true"]', $button)->length)->toBe(1);
});

test('framework listing enables Add New only without a record and returns after deletion', function (): void {
    $this->get(route('admin.thrive-framework.index'))->assertSuccessful()->assertSee('No THRIVE Framework has been saved yet.')
        ->assertSee(route('admin.thrive-framework.create'), false)->assertDontSee('Only one framework is allowed per configured language.');
    $section = frameworkSaved();
    $this->get(route('admin.thrive-framework.index'))->assertSuccessful()->assertSee('Saved framework')->assertSee('Total Stages')
        ->assertSee('aria-disabled="true"', false)->assertDontSee(route('admin.thrive-framework.create'), false);
    $this->get(route('admin.thrive-framework.create'))->assertRedirect(route('admin.thrive-framework.index'));
    $this->delete(route('admin.thrive-framework.destroy', $section))->assertRedirect(route('admin.thrive-framework.index'));
    $this->get(route('admin.thrive-framework.index'))->assertSuccessful()->assertSee(route('admin.thrive-framework.create'), false);
});

test('framework setup safely disables actions if either table is missing', function (string $table): void {
    Schema::drop($table);
    $this->get(route('admin.thrive-framework.index'))->assertSuccessful()->assertSee('THRIVE Framework setup is incomplete.')
        ->assertSee('aria-disabled="true"', false)->assertDontSee(route('admin.thrive-framework.create'), false);
    foreach (['create', 'show', 'edit'] as $action) {
        $this->get(route('admin.thrive-framework.'.$action, $action === 'create' ? [] : [123]))
            ->assertRedirect(route('admin.thrive-framework.index'));
    }
    $this->post(route('admin.thrive-framework.store'), frameworkPayload())->assertRedirect(route('admin.thrive-framework.index'));
    $this->put(route('admin.thrive-framework.update', 123), frameworkPayload())->assertRedirect(route('admin.thrive-framework.index'));
    $this->delete(route('admin.thrive-framework.destroy', 123))->assertRedirect(route('admin.thrive-framework.index'));
})->with(['parent missing' => ['thrive_framework_sections'], 'stages missing' => ['thrive_framework_stages']]);

test('framework creates Unicode content multiple stages ownership and exact activity entries', function (): void {
    $this->get(route('admin.thrive-framework.create'))->assertSuccessful()->assertSee('Framework Stages')
        ->assertSee('data-stage-template', false)->assertDontSee('Trace the Root');
    $this->post(route('admin.thrive-framework.store'), frameworkPayload(['description_one' => null, 'description_two' => null]))
        ->assertRedirect(route('admin.thrive-framework.index'))->assertSessionHasNoErrors();
    $section = ThriveFrameworkSection::query()->sole();
    expect($section->heading)->toBe('The THRIVE™ Framework')->and($section->language)->toBe(config('content_language.code'))
        ->and($section->description_one)->toBeNull()->and($section->description_two)->toBeNull()
        ->and((int) $section->created_by)->toBe($this->frameworkAdmin->id)
        ->and($section->stages->count())->toBe(2)->and($section->stages[0]->is_active)->toBeTrue()
        ->and($section->stages[1]->is_active)->toBeFalse()
        ->and($section->stages[0]->section->id)->toBe($section->id)
        ->and(ActivityLog::query()->where('module', 'thrive_framework')->where('action', 'created')->count())->toBe(3);
    $this->get(route('admin.thrive-framework.show', $section))->assertSuccessful()->assertSee('The THRIVE™ Framework')
        ->assertSee('Saved first stage')->assertSee('Saved first description')->assertSee('STAGE A')->assertSee('Saved second stage');
});

test('framework rejects duplicates through backend validation and unique schema', function (): void {
    frameworkSaved();
    $this->post(route('admin.thrive-framework.store'), frameworkPayload())->assertSessionHasErrors('eyebrow');
    expect(ThriveFrameworkSection::query()->count())->toBe(1);
    expect(fn () => frameworkSaved())->toThrow(QueryException::class);
});

test('framework validates required main fields', function (string $field): void {
    $this->post(route('admin.thrive-framework.store'), frameworkPayload([$field => null]))->assertSessionHasErrors($field);
    expect(ThriveFrameworkSection::query()->count())->toBe(0);
})->with(['eyebrow', 'heading', 'is_active']);

test('framework optional descriptions and all stages can be omitted', function (): void {
    $this->post(route('admin.thrive-framework.store'), ['eyebrow' => 'Only required', 'heading' => 'Only heading', 'is_active' => '0'])
        ->assertSessionHasNoErrors()->assertRedirect(route('admin.thrive-framework.index'));
    expect(ThriveFrameworkSection::query()->sole()->is_active)->toBeFalse()->and(ThriveFrameworkStage::query()->count())->toBe(0);
});

test('framework updates reorders removes and adds stages without replacing retained IDs', function (): void {
    $this->post(route('admin.thrive-framework.store'), frameworkPayload())->assertSessionHasNoErrors();
    $section = ThriveFrameworkSection::query()->sole();
    [$first, $second] = $section->stages->all();
    $third = $section->stages()->create(['letter' => 'V', 'title' => 'Will be removed', 'sort_order' => 2]);
    $this->put(route('admin.thrive-framework.update', $section), frameworkPayload([
        'description_one' => 'Updated first paragraph', 'description_two' => 'Updated second paragraph', 'is_active' => '0',
        'stages' => [
            ['id' => $second->id, 'letter' => 'R', 'title' => 'Updated second', 'is_active' => '1'],
            ['id' => $first->id, 'letter' => 'T', 'title' => 'Updated first', 'is_active' => '0'],
            ['letter' => 'E', 'title' => 'Newly added'],
        ],
    ]))->assertRedirect(route('admin.thrive-framework.index'))->assertSessionHasNoErrors();
    $stages = $section->fresh()->stages;
    expect($stages->pluck('id')->take(2)->all())->toBe([$second->id, $first->id])
        ->and($stages->pluck('sort_order')->all())->toBe([0, 1, 2])
        ->and($stages[0]->title)->toBe('Updated second')->and($stages[1]->is_active)->toBeFalse()
        ->and(ThriveFrameworkStage::query()->find($third->id))->toBeNull()
        ->and(ActivityLog::query()->where('module', 'thrive_framework')->where('action', 'status_changed')->count())->toBe(3)
        ->and(ActivityLog::query()->where('subject_type', ThriveFrameworkStage::class)->where('action', 'deleted')->count())->toBe(1);
    $this->get(route('admin.thrive-framework.edit', $section))->assertSuccessful()->assertSee('Updated first')
        ->assertSee('Updated second paragraph')->assertSee('data-move="up"', false);
});

test('framework preserves stages when repeater is absent and clears only explicit empty submissions', function (): void {
    $section = frameworkSaved();
    $stage = $section->stages()->create(['letter' => 'T', 'title' => 'Keep me']);
    $main = ['eyebrow' => 'Updated', 'heading' => 'Updated', 'is_active' => '1'];
    $this->put(route('admin.thrive-framework.update', $section), $main)->assertSessionHasNoErrors();
    expect($stage->fresh())->not->toBeNull();
    $this->put(route('admin.thrive-framework.update', $section), [...$main, 'stages_present' => '1'])->assertSessionHasNoErrors();
    expect($section->stages()->count())->toBe(0);
});

test('framework rejects malformed repeater values without deleting stages', function (mixed $value): void {
    $section = frameworkSaved();
    $section->stages()->create(['letter' => 'T', 'title' => 'Keep me']);
    $this->put(route('admin.thrive-framework.update', $section), frameworkPayload(['stages' => $value]))->assertSessionHasErrors('stages');
    expect($section->stages()->count())->toBe(1);
})->with(['null' => [null], 'string' => ['broken']]);

test('framework ignores empty new rows but validates partially completed and existing empty rows', function (): void {
    $this->post(route('admin.thrive-framework.store'), frameworkPayload(['stages' => [
        ['letter' => ' ', 'title' => '', 'stage_label' => null, 'description' => '', 'is_active' => '1'],
    ]]))->assertSessionHasNoErrors();
    $section = ThriveFrameworkSection::query()->sole();
    expect($section->stages()->count())->toBe(0);
    $this->put(route('admin.thrive-framework.update', $section), frameworkPayload(['stages' => [
        ['letter' => 'T'], ['title' => 'Missing letter'], ['description' => 'Only description'],
    ]]))->assertSessionHasErrors(['stages.0.title', 'stages.1.letter', 'stages.2.letter', 'stages.2.title']);
    $this->get(route('admin.thrive-framework.edit', $section))->assertSuccessful()->assertSee('Missing letter')->assertSee('Only description');
    $stage = $section->stages()->create(['letter' => 'T', 'title' => 'Existing']);
    $this->put(route('admin.thrive-framework.update', $section), frameworkPayload(['stages' => [['id' => $stage->id]]]))
        ->assertSessionHasErrors(['stages.0.title', 'stages.0.letter']);
    expect($stage->fresh()->title)->toBe('Existing');
});

test('framework validates lengths duplicate IDs and nested fields', function (): void {
    $section = frameworkSaved();
    $stage = $section->stages()->create(['letter' => 'T', 'title' => 'Existing']);
    $this->put(route('admin.thrive-framework.update', $section), frameworkPayload(['stages' => [
        ['id' => $stage->id, 'letter' => str_repeat('T', 11), 'title' => str_repeat('x', 256), 'stage_label' => str_repeat('s', 51)],
        ['id' => $stage->id, 'letter' => 'R', 'title' => 'Duplicate', 'is_active' => 'invalid'],
    ]]))->assertSessionHasErrors(['stages.0.letter', 'stages.0.title', 'stages.0.stage_label', 'stages.0.id', 'stages.1.id', 'stages.1.is_active']);
});

test('framework rejects foreign stage IDs and rolls back section stage and activity changes', function (): void {
    $section = frameworkSaved();
    $foreign = frameworkSaved('ur')->stages()->create(['letter' => 'R', 'title' => 'Foreign']);
    $stage = $section->stages()->create(['letter' => 'T', 'title' => 'Original']);
    $count = ActivityLog::query()->count();
    $this->put(route('admin.thrive-framework.update', $section), frameworkPayload(['stages' => [
        ['id' => $stage->id, 'letter' => 'T', 'title' => 'Would change'],
        ['id' => $foreign->id, 'letter' => 'R', 'title' => 'Stolen'],
    ]]))->assertSessionHasErrors('stages.1.id');
    expect($section->fresh()->heading)->toBe('Saved framework')->and($stage->fresh()->title)->toBe('Original')
        ->and($foreign->fresh()->title)->toBe('Foreign')->and(ActivityLog::query()->count())->toBe($count);
});

test('framework deletes parent and dependent stages with one log per subject', function (): void {
    $this->post(route('admin.thrive-framework.store'), frameworkPayload())->assertSessionHasNoErrors();
    $section = ThriveFrameworkSection::query()->sole();
    $this->delete(route('admin.thrive-framework.destroy', $section))->assertRedirect(route('admin.thrive-framework.index'));
    expect(ThriveFrameworkSection::query()->count())->toBe(0)->and(ThriveFrameworkStage::query()->count())->toBe(0)
        ->and(ActivityLog::query()->where('module', 'thrive_framework')->where('action', 'deleted')->count())->toBe(3);
});

test('framework uses configured language and refuses other language IDs', function (): void {
    $other = frameworkSaved('ur');
    $this->get(route('admin.thrive-framework.index'))->assertSuccessful()->assertDontSee('Saved eyebrow')
        ->assertSee(route('admin.thrive-framework.create'), false);
    foreach (['show', 'edit'] as $action) {
        $this->get(route('admin.thrive-framework.'.$action, $other))->assertNotFound();
    }
    $this->put(route('admin.thrive-framework.update', $other), frameworkPayload())->assertNotFound();
    $this->delete(route('admin.thrive-framework.destroy', $other))->assertNotFound();
    $this->post(route('admin.thrive-framework.store'), frameworkPayload(['language' => 'ur']))->assertSessionHasNoErrors();
    expect(ThriveFrameworkSection::query()->where('language', 'en')->count())->toBe(1)
        ->and(ThriveFrameworkSection::query()->where('language', 'ur')->count())->toBe(1);
});

test('framework permissions are registered under Website and protected on every route', function (): void {
    expect(config('admin_modules.modules.thrive-framework.group'))->toBe('Website');
    foreach (['index' => 'view', 'create' => 'create', 'store' => 'create', 'show' => 'view', 'edit' => 'edit', 'update' => 'edit', 'destroy' => 'delete'] as $action => $permission) {
        $route = app('router')->getRoutes()->getByName('admin.thrive-framework.'.$action);
        expect($route->gatherMiddleware())->toContain('auth', 'admin-access', 'permission:thrive-framework.'.$permission);
    }
    $section = frameworkSaved();
    $this->actingAs(frameworkLimitedAdmin(['thrive-framework.view']));
    $this->get(route('admin.thrive-framework.index'))->assertSuccessful()->assertSee('THRIVE Framework')
        ->assertDontSee(route('admin.thrive-framework.create'), false);
    $this->get(route('admin.thrive-framework.show', $section))->assertSuccessful();
    $this->get(route('admin.thrive-framework.create'))->assertForbidden();
    $this->post(route('admin.thrive-framework.store'), frameworkPayload())->assertForbidden();
    $this->get(route('admin.thrive-framework.edit', $section))->assertForbidden();
    $this->put(route('admin.thrive-framework.update', $section), frameworkPayload())->assertForbidden();
    $this->delete(route('admin.thrive-framework.destroy', $section))->assertForbidden();
    $this->actingAs(frameworkLimitedAdmin(['home-hero.view']));
    $this->get(route('admin.thrive-framework.index'))->assertForbidden();
});

test('framework defaults layout to three and content edits preserve the saved layout', function (): void {
    expect((new ThriveFrameworkSection)->cards_per_row)->toBe(3);
    $this->post(route('admin.thrive-framework.store'), frameworkPayload())->assertSessionHasNoErrors();
    $section = ThriveFrameworkSection::query()->sole();
    expect($section->cards_per_row)->toBe(3);
    $section->update(['cards_per_row' => 4]);
    $this->put(route('admin.thrive-framework.update', $section), frameworkPayload())->assertSessionHasNoErrors();
    expect($section->fresh()->cards_per_row)->toBe(4);
});

test('framework layout selection saves on its dedicated page and displays in read-only view', function (int $columns): void {
    $this->get(route('admin.thrive-framework.create'))->assertSuccessful()->assertDontSee('name="cards_per_row"', false);
    $this->post(route('admin.thrive-framework.store'), frameworkPayload())->assertSessionHasNoErrors();
    $section = ThriveFrameworkSection::query()->sole();
    $this->get(route('admin.thrive-framework.index'))->assertSuccessful()->assertSee(route('admin.thrive-framework.homepage-layout-settings'), false);
    $this->put(route('admin.thrive-framework.homepage-layout-settings.update'), ['cards_per_row' => $columns])->assertRedirect(route('admin.thrive-framework.homepage-layout-settings'))->assertSessionHasNoErrors();
    expect($section->fresh()->cards_per_row)->toBe($columns);
    $document = new DOMDocument;
    $section->refresh();
    $document->loadHTML($this->get(route('admin.thrive-framework.homepage-layout-settings'))->assertSuccessful()->getContent(), LIBXML_NOERROR | LIBXML_NOWARNING);
    $xpath = new DOMXPath($document);
    expect($xpath->query('//select[@name="cards_per_row"]/option[@value="'.$columns.'" and @selected]')->length)->toBe(1);
    $this->get(route('admin.thrive-framework.show', $section))->assertSuccessful()->assertSee('Cards Per Row')->assertSee($columns.' Cards');
    $this->get(route('admin.thrive-framework.edit', $section))->assertSuccessful()->assertDontSee('name="cards_per_row"', false);
    $this->put(route('admin.thrive-framework.homepage-layout-settings.update'), ['cards_per_row' => $columns === 3 ? 4 : 3])->assertSessionHasNoErrors();
    expect($section->fresh()->cards_per_row)->toBe($columns === 3 ? 4 : 3);
})->with([3, 4]);

test('framework rejects unsupported values on the dedicated layout route', function (mixed $value): void {
    $section = frameworkSaved();
    $this->put(route('admin.thrive-framework.homepage-layout-settings.update'), ['cards_per_row' => $value])->assertSessionHasErrors('cards_per_row');
    expect($section->fresh()->cards_per_row)->toBe(3);
})->with(['null' => [null], 'zero' => [0], 'two' => [2], 'five' => [5], 'fraction' => [3.5], 'text' => ['four'], 'array' => [[3]]]);

test('framework layout page preserves old selection', function (): void {
    frameworkSaved();
    $document = new DOMDocument;
    $document->loadHTML($this->withSession(['_old_input' => ['cards_per_row' => 4]])->get(route('admin.thrive-framework.homepage-layout-settings'))->assertSuccessful()->getContent(), LIBXML_NOERROR | LIBXML_NOWARNING);
    expect((new DOMXPath($document))->query('//select[@name="cards_per_row"]/option[@value="4" and @selected]')->length)->toBe(1);
});

test('framework layout page handles missing setup and absent records without creating content', function (string $state): void {
    if ($state === 'missing table') {
        Schema::drop('thrive_framework_sections');
    }
    if ($state === 'missing column') {
        Schema::table('thrive_framework_sections', function (Blueprint $table): void {
            $table->dropColumn('cards_per_row');
        });
    }
    $this->get(route('admin.thrive-framework.homepage-layout-settings'))->assertSuccessful()->assertDontSee('Save Settings');
    $this->put(route('admin.thrive-framework.homepage-layout-settings.update'), ['cards_per_row' => 4])->assertRedirect(route('admin.thrive-framework.homepage-layout-settings'));
    if ($state !== 'missing table') {
        expect(ThriveFrameworkSection::query()->count())->toBe(0);
    }
})->with(['no record', 'missing table', 'missing column']);

test('framework layout changes only the configured section layout and records activity', function (): void {
    $section = frameworkSaved();
    $foreign = frameworkSaved('ur');
    $stage = $section->stages()->create(['letter' => 'T', 'title' => 'Keep this stage']);
    $beforeStage = $stage->fresh()->getAttributes();
    $count = ActivityLog::query()->count();
    $this->put(route('admin.thrive-framework.homepage-layout-settings.update'), [
        'cards_per_row' => 4, 'heading' => 'Must not change', 'stages_present' => '1', 'language' => 'ur',
    ])->assertSessionHasNoErrors()->assertSessionHas('status');
    expect($section->fresh()->cards_per_row)->toBe(4)->and($section->fresh()->heading)->toBe('Saved framework')
        ->and($foreign->fresh()->cards_per_row)->toBe(3)->and($stage->fresh()->getAttributes())->toBe($beforeStage)
        ->and(ActivityLog::query()->count())->toBe($count + 1);
    $this->put(route('admin.thrive-framework.update', $section), frameworkPayload(['cards_per_row' => 3]))->assertSessionHasNoErrors();
    expect($section->fresh()->cards_per_row)->toBe(4);
});

test('framework layout page and save require edit permission', function (): void {
    frameworkSaved();
    $this->actingAs(frameworkLimitedAdmin(['thrive-framework.view']));
    $this->get(route('admin.thrive-framework.index'))->assertSuccessful()->assertDontSee(route('admin.thrive-framework.homepage-layout-settings'), false);
    $this->get(route('admin.thrive-framework.homepage-layout-settings'))->assertForbidden();
    $this->put(route('admin.thrive-framework.homepage-layout-settings.update'), ['cards_per_row' => 4])->assertForbidden();
});

test('additive layout default preserves existing records in an isolated schema fixture', function (): void {
    Schema::table('thrive_framework_sections', function (Blueprint $table): void {
        $table->dropColumn('cards_per_row');
    });
    $section = new ThriveFrameworkSection(['language' => 'en', 'eyebrow' => 'Preserved eyebrow', 'heading' => 'Preserved heading']);
    unset($section->cards_per_row);
    $section->save();
    Schema::table('thrive_framework_sections', function (Blueprint $table): void {
        $table->unsignedTinyInteger('cards_per_row')->default(3);
    });
    expect($section->fresh()->cards_per_row)->toBe(3)->and($section->fresh()->heading)->toBe('Preserved heading');
});

test('framework does not suppress unrelated schema errors', function (): void {
    Schema::table('thrive_framework_sections', function (Blueprint $table): void {
        $table->renameColumn('eyebrow', 'missing_eyebrow');
    });
    $this->withoutExceptionHandling();
    expect(fn () => $this->post(route('admin.thrive-framework.store'), frameworkPayload()))->toThrow(QueryException::class);
});

test('framework requires the explicit repeater marker and preserves inactive stages on edit', function (): void {
    $section = frameworkSaved();
    $stage = $section->stages()->create(['letter' => 'T', 'title' => 'Inactive stage', 'is_active' => false]);
    $this->put(route('admin.thrive-framework.update', $section), [
        'eyebrow' => 'Saved', 'heading' => 'Saved', 'is_active' => '1', 'stages' => [],
    ])->assertSessionHasErrors('stages_present');
    expect($stage->fresh())->not->toBeNull();
    $this->flushSession();
    $response = $this->get(route('admin.thrive-framework.edit', $section))->assertSuccessful();
    $document = new DOMDocument;
    $document->loadHTML($response->getContent(), LIBXML_NOERROR | LIBXML_NOWARNING);
    $xpath = new DOMXPath($document);
    expect($xpath->query('//select[@name="stages[0][is_active]"]/option[@value="0" and @selected]')->length)->toBe(1);
});

test('framework does not limit stage content to six cards and escapes saved text', function (): void {
    $stages = array_map(fn (int $number): array => ['letter' => (string) $number, 'title' => 'Dynamic stage '.$number], range(1, 8));
    $this->post(route('admin.thrive-framework.store'), frameworkPayload([
        'description_one' => '<script>untrusted()</script>', 'description_two' => 'Saved paragraph two', 'stages' => $stages,
    ]))->assertSessionHasNoErrors();
    $section = ThriveFrameworkSection::query()->sole();
    expect($section->stages()->count())->toBe(8);
    $this->get(route('admin.thrive-framework.show', $section))->assertSuccessful()
        ->assertSee('&lt;script&gt;untrusted()&lt;/script&gt;', false)->assertDontSee('<script>untrusted()</script>', false)
        ->assertSee('Saved paragraph two')->assertSee('Dynamic stage 8');
});

test('existing Who I Help CRUD remains independent of framework records', function (): void {
    frameworkFixtureTable(new WhoIHelpSection);
    frameworkFixtureTable(new WhoIHelpItem);
    frameworkSaved();
    $payload = [
        'eyebrow' => 'Independent Who I Help', 'heading' => 'Existing module heading', 'is_active' => '1',
        'description' => 'Existing description', 'bottom_message' => 'Existing message', 'items_present' => '1',
        'items' => [['title' => 'Independent help card', 'is_active' => '1']],
    ];
    $this->post(route('admin.who-i-help.store'), $payload)->assertSessionHasNoErrors()->assertRedirect(route('admin.who-i-help.index'));
    $section = WhoIHelpSection::query()->sole();
    $this->get(route('admin.who-i-help.index'))->assertSuccessful()->assertSee('Independent Who I Help');
    $this->get(route('admin.who-i-help.show', $section))->assertSuccessful()->assertSee('Independent help card');
    $this->put(route('admin.who-i-help.update', $section), [...$payload, 'heading' => 'Updated existing module'])
        ->assertSessionHasNoErrors()->assertRedirect(route('admin.who-i-help.index'));
    $this->delete(route('admin.who-i-help.destroy', $section))->assertRedirect(route('admin.who-i-help.index'));
    expect(WhoIHelpSection::query()->count())->toBe(0)->and(WhoIHelpItem::query()->count())->toBe(0)
        ->and(ThriveFrameworkSection::query()->count())->toBe(1);
});
