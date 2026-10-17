<?php

use App\Models\ActivityLog;
use App\Models\GeneralSetting;
use App\Models\User;
use App\Models\WorkTogetherCard;
use App\Models\WorkTogetherSection;
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
        workTogetherFixtureTable($model);
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
    Schema::create('work_together_sections', function (Blueprint $table): void {
        $table->id();
        $table->string('language', 10)->unique();
        $table->string('eyebrow');
        $table->string('heading');
        $table->text('description')->nullable();
        $table->text('bottom_message')->nullable();
        $table->string('bottom_button_text')->nullable();
        $table->string('bottom_button_url', 2048)->nullable();
        $table->unsignedTinyInteger('cards_per_row')->default(3);
        $table->boolean('is_active')->default(true);
        $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
        $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
        $table->timestamps();
    });
    Schema::create('work_together_cards', function (Blueprint $table): void {
        $table->id();
        $table->foreignId('section_id')->constrained('work_together_sections')->cascadeOnDelete();
        $table->string('title');
        $table->text('description')->nullable();
        $table->text('additional_details')->nullable();
        $table->string('button_text')->nullable();
        $table->string('button_url', 2048)->nullable();
        $table->string('layout_type')->default('standard');
        $table->unsignedInteger('sort_order')->default(0);
        $table->boolean('is_active')->default(true);
        $table->timestamps();
    });
    app(PermissionRegistrar::class)->forgetCachedPermissions();
    $this->workTogetherAdmin = User::factory()->create(['is_active' => true]);
    $this->workTogetherAdmin->assignRole(Role::findOrCreate('super-admin', 'web'));
    $this->actingAs($this->workTogetherAdmin);
});

afterEach(function (): void {
    DB::disconnect('sqlite');
});

function workTogetherFixtureTable(Model $model): void
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
function workTogetherPayload(array $overrides = []): array
{
    return array_replace([
        'eyebrow' => 'WORK WITH ME', 'heading' => 'How We Can Work Together™', 'is_active' => '1',
        'description' => 'Saved section description', 'bottom_message' => 'Saved bottom message',
        'bottom_button_text' => 'Book a conversation', 'bottom_button_url' => '/contact',
        'cards_present' => '1',
        'cards' => [
            ['title' => 'First saved card', 'description' => 'First card description', 'additional_details' => 'First extra details',
                'button_text' => 'Explore first', 'button_url' => 'https://example.test/programme', 'layout_type' => 'standard'],
            ['title' => 'Second saved card', 'layout_type' => 'full_width', 'is_active' => '0'],
        ],
    ], $overrides);
}

function workTogetherSaved(string $language = 'en'): WorkTogetherSection
{
    return WorkTogetherSection::query()->create(['language' => $language, 'eyebrow' => 'Saved eyebrow', 'heading' => 'Saved heading', 'is_active' => true]);
}

function workTogetherLimitedAdmin(array $permissions): User
{
    $admin = User::factory()->create(['is_active' => true]);
    $role = Role::create(['name' => 'workTogether-editor-'.Str::random(8), 'guard_name' => 'web']);
    foreach (['admin.access', ...$permissions] as $name) {
        $role->permissions()->attach(Permission::findOrCreate($name, 'web'));
    }
    $admin->assignRole($role);

    return $admin;
}

test('work together routes sidebar and permission definitions follow Website conventions', function (): void {
    expect(config('admin_modules.modules.work-together.group'))->toBe('Website');
    foreach (['index' => 'view', 'create' => 'create', 'store' => 'create', 'show' => 'view', 'edit' => 'edit', 'update' => 'edit', 'destroy' => 'delete'] as $action => $permission) {
        expect(app('router')->getRoutes()->getByName('admin.work-together.'.$action)->gatherMiddleware())
            ->toContain('auth', 'admin-access', 'permission:work-together.'.$permission);
    }
    $html = $this->get(route('admin.work-together.index'))->assertSuccessful()->getContent();
    $document = new DOMDocument;
    $document->loadHTML($html, LIBXML_NOERROR | LIBXML_NOWARNING);
    expect((new DOMXPath($document))->query('//*[@id="websiteManagementMenu"]//a[@href="'.route('admin.work-together.index').'"]')->length)->toBe(1);
});

test('work together listing singleton Add New and icon actions behave correctly', function (): void {
    $this->get(route('admin.work-together.index'))->assertSuccessful()->assertSee(route('admin.work-together.create'), false)->assertSee('Total Cards')->assertSee('Cards Per Row');
    $section = workTogetherSaved();
    $this->get(route('admin.work-together.index'))->assertSuccessful()->assertSee('Saved eyebrow')->assertSee('aria-disabled="true"', false)
        ->assertDontSee(route('admin.work-together.create'), false)->assertSee('fa-eye', false)->assertSee('fa-pen-to-square', false)->assertSee('data-delete-confirm', false);
    $this->get(route('admin.work-together.create'))->assertRedirect(route('admin.work-together.index'));
    $this->delete(route('admin.work-together.destroy', $section))->assertRedirect(route('admin.work-together.index'));
    $this->get(route('admin.work-together.index'))->assertSuccessful()->assertSee(route('admin.work-together.create'), false);
});

test('work together create uses radio status and dynamic empty card template without saving defaults', function (): void {
    $this->get(route('admin.work-together.create'))->assertSuccessful()->assertSee('Section Details')->assertSee('Cards Management')
        ->assertSee('data-work-card-template', false)->assertSee('name="cards_present"', false)->assertSee('type="radio"', false)
        ->assertSee('Full Width')->assertSee('Add Card');
    expect(WorkTogetherSection::query()->count())->toBe(0);
});

test('work together saves Unicode optional content ownership and both layouts with exact create logs', function (int $columns): void {
    $this->post(route('admin.work-together.store'), workTogetherPayload(['cards_per_row' => $columns]))
        ->assertRedirect(route('admin.work-together.index'))->assertSessionHasNoErrors();
    $section = WorkTogetherSection::query()->sole();
    $this->put(route('admin.work-together.homepage-layout-settings.update'), ['cards_per_row' => $columns])->assertSessionHasNoErrors();
    $section->refresh();
    expect($section->heading)->toBe('How We Can Work Together™')->and($section->cards_per_row)->toBe($columns)
        ->and((int) $section->created_by)->toBe($this->workTogetherAdmin->id)->and($section->language)->toBe('en')
        ->and($section->cards->count())->toBe(2)->and($section->cards[0]->layout_type)->toBe('standard')
        ->and($section->cards[1]->layout_type)->toBe('full_width')->and($section->cards[1]->is_active)->toBeFalse()
        ->and($section->cards[0]->section->id)->toBe($section->id)
        ->and(ActivityLog::query()->where('module', 'work_together')->where('action', 'created')->count())->toBe(3);
    $this->get(route('admin.work-together.show', $section))->assertSuccessful()->assertSee('Saved bottom message')->assertSee('First extra details')
        ->assertSee('Explore first')->assertSee('https://example.test/programme')->assertSee('Full Width')->assertSee('Standard')
        ->assertDontSee('href="https://example.test/programme"', false)->assertSee('Book a conversation');
    $this->get(route('admin.work-together.edit', $section))->assertSuccessful()->assertSee('First saved card')
        ->assertSee('id="work-status-1"', false)->assertSee('data-card-field="id"', false);
})->with([3, 4]);

test('work together card status radios and layout selects retain saved inactive values', function (): void {
    $this->post(route('admin.work-together.store'), workTogetherPayload())->assertSessionHasNoErrors();
    $section = WorkTogetherSection::query()->sole();
    $document = new DOMDocument;
    $document->loadHTML($this->get(route('admin.work-together.edit', $section))->assertSuccessful()->getContent(), LIBXML_NOERROR | LIBXML_NOWARNING);
    $xpath = new DOMXPath($document);
    expect($xpath->query('//input[@name="cards[1][is_active]" and @value="0" and @checked]')->length)->toBe(1)
        ->and($xpath->query('//select[@name="cards[1][layout_type]"]/option[@value="full_width" and @selected]')->length)->toBe(1)
        ->and($xpath->query('//label[@for="work-card-1-is_active-0"]')->length)->toBe(1)
        ->and($xpath->query('//input[@name="cards[1][id]"]')->item(0)->getAttribute('value'))->toBe((string) $section->cards[1]->id);
});

test('work together enforces one configured-language record in backend and database', function (): void {
    workTogetherSaved();
    $this->post(route('admin.work-together.store'), workTogetherPayload())->assertSessionHasErrors('eyebrow');
    expect(WorkTogetherSection::query()->count())->toBe(1);
    expect(fn () => workTogetherSaved())->toThrow(QueryException::class);
});

test('work together validates required section fields', function (string $field): void {
    $this->post(route('admin.work-together.store'), workTogetherPayload([$field => null]))->assertSessionHasErrors($field);
    expect(WorkTogetherSection::query()->count())->toBe(0)->and(ActivityLog::query()->count())->toBe(0);
})->with(['eyebrow', 'heading', 'is_active']);

test('work together permits optional nulls and independent CTA halves without generating links', function (): void {
    $this->post(route('admin.work-together.store'), ['eyebrow' => 'Only required', 'heading' => 'Only heading', 'is_active' => '0',
        'bottom_button_text' => 'Text only', 'cards_present' => '1', 'cards' => [
            ['title' => 'Optional card', 'description' => null, 'additional_details' => null, 'button_url' => '/contact'],
        ]])->assertSessionHasNoErrors();
    $section = WorkTogetherSection::query()->sole();
    expect($section->description)->toBeNull()->and($section->bottom_message)->toBeNull()->and($section->bottom_button_url)->toBeNull()
        ->and($section->cards_per_row)->toBe(3)->and($section->is_active)->toBeFalse()
        ->and($section->cards[0]->layout_type)->toBe('standard')->and($section->cards[0]->is_active)->toBeTrue();
    $this->get(route('admin.work-together.show', $section))->assertSuccessful()->assertSee('Text only')->assertSee('—');
});

test('work together updates reorders adds and removes cards while preserving IDs and audit history', function (): void {
    $this->post(route('admin.work-together.store'), workTogetherPayload())->assertSessionHasNoErrors();
    $section = WorkTogetherSection::query()->sole();
    [$first, $second] = $section->cards->all();
    $third = $section->cards()->create(['title' => 'Remove this card', 'sort_order' => 2]);
    $this->put(route('admin.work-together.update', $section), workTogetherPayload([
        'heading' => 'Updated section heading', 'cards_per_row' => 4, 'is_active' => '0',
        'cards' => [
            ['id' => $second->id, 'title' => 'Reordered second', 'layout_type' => 'standard', 'is_active' => '1'],
            ['id' => $first->id, 'title' => 'Updated first', 'layout_type' => 'full_width', 'is_active' => '0', 'additional_details' => 'Updated extra'],
            ['title' => 'Newly added'],
        ],
    ]))->assertSessionHasNoErrors()->assertRedirect(route('admin.work-together.index'));
    $cards = $section->fresh()->cards;
    expect($cards->pluck('id')->take(2)->all())->toBe([$second->id, $first->id])
        ->and($cards->pluck('sort_order')->all())->toBe([0, 1, 2])->and($cards[1]->layout_type)->toBe('full_width')
        ->and($cards[1]->additional_details)->toBe('Updated extra')->and($cards[1]->is_active)->toBeFalse()
        ->and(WorkTogetherCard::query()->find($third->id))->toBeNull()
        ->and(ActivityLog::query()->where('module', 'work_together')->where('action', 'status_changed')->count())->toBe(3)
        ->and(ActivityLog::query()->where('subject_type', WorkTogetherCard::class)->where('action', 'deleted')->count())->toBe(1);
});

test('work together logs ordering-only changes once per card with changed sort values', function (): void {
    $section = workTogetherSaved();
    $first = $section->cards()->create(['title' => 'First', 'sort_order' => 0]);
    $second = $section->cards()->create(['title' => 'Second', 'sort_order' => 1]);
    $count = ActivityLog::query()->count();
    $this->put(route('admin.work-together.update', $section), [
        'eyebrow' => $section->eyebrow, 'heading' => $section->heading, 'is_active' => '1', 'cards_present' => '1',
        'cards' => [['id' => $second->id, 'title' => 'Second'], ['id' => $first->id, 'title' => 'First']],
    ])->assertSessionHasNoErrors();
    $logs = ActivityLog::query()->where('subject_type', WorkTogetherCard::class)->where('action', 'updated')->get();
    expect($logs->count())->toBe(2)->and($logs->pluck('new_values')->pluck('sort_order')->sort()->values()->all())->toBe([0, 1])
        ->and(ActivityLog::query()->count())->toBe($count + 2);
});

test('work together supports more than seven cards without fixed slots', function (): void {
    $cards = array_map(fn (int $number): array => ['title' => 'Dynamic card '.$number], range(1, 9));
    $this->post(route('admin.work-together.store'), workTogetherPayload(['cards' => $cards]))->assertSessionHasNoErrors();
    expect(WorkTogetherCard::query()->count())->toBe(9);
});

test('work together keeps absent repeaters and intentionally clears marked empty submissions', function (): void {
    $section = workTogetherSaved();
    $card = $section->cards()->create(['title' => 'Keep me']);
    $main = ['eyebrow' => 'Updated', 'heading' => 'Updated', 'is_active' => '1'];
    $this->put(route('admin.work-together.update', $section), $main)->assertSessionHasNoErrors();
    expect($card->fresh())->not->toBeNull();
    $this->put(route('admin.work-together.update', $section), [...$main, 'cards_present' => '1'])->assertSessionHasNoErrors();
    expect($section->cards()->count())->toBe(0);
});

test('work together rejects missing marker null or malformed cards without deleting saved data', function (array $overrides): void {
    $section = workTogetherSaved();
    $section->cards()->create(['title' => 'Keep me']);
    $payload = array_replace(['eyebrow' => 'Updated', 'heading' => 'Updated', 'is_active' => '1'], $overrides);
    $this->put(route('admin.work-together.update', $section), $payload)->assertSessionHasErrors();
    expect($section->cards()->count())->toBe(1);
})->with([
    'missing marker' => [['cards' => []]], 'null cards' => [['cards_present' => '1', 'cards' => null]],
    'string cards' => [['cards_present' => '1', 'cards' => 'malformed']], 'invalid marker' => [['cards_present' => '0', 'cards' => []]],
]);

test('work together ignores blank new cards and validates partial cards with restored errors', function (): void {
    $this->post(route('admin.work-together.store'), workTogetherPayload(['cards' => [['title' => ' ', 'description' => '', 'is_active' => '1', 'layout_type' => 'standard']]]))->assertSessionHasNoErrors();
    $section = WorkTogetherSection::query()->sole();
    expect($section->cards()->count())->toBe(0);
    $this->from(route('admin.work-together.edit', $section))->put(route('admin.work-together.update', $section),
        workTogetherPayload(['cards' => [['description' => 'Partial saved description', 'button_text' => 'Missing title']]]))
        ->assertSessionHasErrors(['cards.0.title' => 'Each populated card needs a title.']);
    $errorBag = app('session.store')->get('errors');
    $content = view('admin.work-together._form', ['section' => $section->fresh('cards'), 'errors' => $errorBag])->render();
    expect($content)->toContain('Partial saved description', 'Each populated card needs a title.');
});

test('work together treats non-default layout or status in otherwise blank cards as partial input', function (array $card): void {
    $this->post(route('admin.work-together.store'), workTogetherPayload(['cards' => [$card]]))->assertSessionHasErrors('cards.0.title');
    expect(WorkTogetherSection::query()->count())->toBe(0);
})->with(['full width only' => [['layout_type' => 'full_width']], 'inactive only' => [['is_active' => '0']]]);

test('work together rejects existing blank cards foreign IDs and rolls back all logs and changes', function (): void {
    $section = workTogetherSaved();
    $card = $section->cards()->create(['title' => 'Original']);
    $foreign = workTogetherSaved('ur')->cards()->create(['title' => 'Foreign']);
    $this->put(route('admin.work-together.update', $section), workTogetherPayload(['cards' => [['id' => $card->id]]]))
        ->assertSessionHasErrors('cards.0.title');
    $logCount = ActivityLog::query()->count();
    $this->put(route('admin.work-together.update', $section), workTogetherPayload(['cards' => [
        ['id' => $card->id, 'title' => 'Would change'], ['id' => $foreign->id, 'title' => 'Stolen'],
    ]]))->assertSessionHasErrors('cards.1.id');
    expect($card->fresh()->title)->toBe('Original')->and($section->fresh()->heading)->toBe('Saved heading')
        ->and($foreign->fresh()->title)->toBe('Foreign')->and(ActivityLog::query()->count())->toBe($logCount);
});

test('work together rejects duplicate child IDs and unvalidated nested ownership', function (): void {
    $section = workTogetherSaved();
    $card = $section->cards()->create(['title' => 'Original']);
    $this->put(route('admin.work-together.update', $section), workTogetherPayload(['cards' => [
        ['id' => $card->id, 'title' => 'Duplicate one'], ['id' => $card->id, 'title' => 'Duplicate two'],
    ]]))->assertSessionHasErrors(['cards.0.id', 'cards.1.id']);
    $this->put(route('admin.work-together.update', $section), workTogetherPayload(['cards' => [
        ['title' => 'Injected', 'section_id' => 999],
    ]]))->assertSessionHasErrors('cards.0');
});

test('work together validates layout values and parent grid choices', function (string $field, mixed $value, string $error): void {
    $payload = str_starts_with($field, 'cards.') ? workTogetherPayload(['cards' => [['title' => 'Saved', 'layout_type' => $value]]])
        : workTogetherPayload(['cards_per_row' => $value]);
    if ($field === 'cards_per_row') {
        workTogetherSaved();
        $this->put(route('admin.work-together.homepage-layout-settings.update'), ['cards_per_row' => $value])->assertSessionHasErrors($error);
    } else {
        $this->post(route('admin.work-together.store'), $payload)->assertSessionHasErrors($error);
    }
})->with([
    'bad card layout' => ['cards.layout_type', 'wide', 'cards.0.layout_type'],
    'null card layout' => ['cards.layout_type', null, 'cards.0.layout_type'],
    'bad grid count' => ['cards_per_row', 5, 'cards_per_row'],
    'fractional grid' => ['cards_per_row', 3.5, 'cards_per_row'],
    'null grid' => ['cards_per_row', null, 'cards_per_row'],
]);

test('work together accepts safe local and HTTP CTA URLs', function (string $url): void {
    $this->post(route('admin.work-together.store'), workTogetherPayload(['bottom_button_url' => $url,
        'cards' => [['title' => 'Safe CTA', 'button_url' => $url]]]))->assertSessionHasNoErrors();
})->with(['/contact', '/courses?type=wellness', 'https://example.test/book', 'http://example.test/book']);

test('work together rejects unsafe CTA URLs in both section and cards', function (string $url): void {
    $this->post(route('admin.work-together.store'), workTogetherPayload(['bottom_button_url' => $url,
        'cards' => [['title' => 'Unsafe CTA', 'button_url' => $url]]]))->assertSessionHasErrors(['bottom_button_url', 'cards.0.button_url']);
})->with(['javascript:alert(1)', '//example.test', '/\\example.test', "https://example.test/\ninjected", 'https://', 'https://example.test/a b']);

test('work together deletion removes dependent cards with exactly one log per subject', function (): void {
    $this->post(route('admin.work-together.store'), workTogetherPayload())->assertSessionHasNoErrors();
    $section = WorkTogetherSection::query()->sole();
    $this->delete(route('admin.work-together.destroy', $section))->assertRedirect(route('admin.work-together.index'));
    expect(WorkTogetherSection::query()->count())->toBe(0)->and(WorkTogetherCard::query()->count())->toBe(0)
        ->and(ActivityLog::query()->where('module', 'work_together')->where('action', 'deleted')->count())->toBe(3);
});

test('work together protects every action and hides unauthorized menu and buttons', function (): void {
    $section = workTogetherSaved();
    $this->actingAs(workTogetherLimitedAdmin(['work-together.view']));
    $this->get(route('admin.work-together.index'))->assertSuccessful()->assertDontSee(route('admin.work-together.create'), false);
    $this->get(route('admin.work-together.show', $section))->assertSuccessful()->assertDontSee(route('admin.work-together.edit', $section), false);
    $this->get(route('admin.work-together.create'))->assertForbidden();
    $this->post(route('admin.work-together.store'), workTogetherPayload())->assertForbidden();
    $this->get(route('admin.work-together.edit', $section))->assertForbidden();
    $this->put(route('admin.work-together.update', $section), workTogetherPayload())->assertForbidden();
    $this->delete(route('admin.work-together.destroy', $section))->assertForbidden();
    $this->actingAs(workTogetherLimitedAdmin(['home-hero.view']));
    $this->get(route('admin.work-together.index'))->assertForbidden();
});

test('work together uses fixed language and forbids other-language record IDs', function (): void {
    $other = workTogetherSaved('ur');
    $this->get(route('admin.work-together.index'))->assertSuccessful()->assertDontSee('Saved eyebrow');
    foreach (['show', 'edit'] as $action) {
        $this->get(route('admin.work-together.'.$action, $other))->assertNotFound();
    }
    $this->put(route('admin.work-together.update', $other), workTogetherPayload())->assertNotFound();
    $this->delete(route('admin.work-together.destroy', $other))->assertNotFound();
    $this->post(route('admin.work-together.store'), workTogetherPayload(['language' => 'ur', 'created_by' => 999]))->assertSessionHasNoErrors();
    $section = WorkTogetherSection::query()->where('language', 'en')->sole();
    expect((int) $section->created_by)->toBe($this->workTogetherAdmin->id)->and(WorkTogetherSection::query()->count())->toBe(2);
});

test('work together safely handles either missing table across all CRUD routes', function (string $table): void {
    Schema::drop($table);
    $this->get(route('admin.work-together.index'))->assertSuccessful()->assertSee('setup is incomplete.')
        ->assertSee('aria-disabled="true"', false)->assertDontSee(route('admin.work-together.create'), false);
    foreach (['create', 'show', 'edit'] as $action) {
        $this->get(route('admin.work-together.'.$action, $action === 'create' ? [] : [123]))->assertRedirect(route('admin.work-together.index'));
    }
    $this->post(route('admin.work-together.store'), workTogetherPayload())->assertRedirect(route('admin.work-together.index'));
    $this->put(route('admin.work-together.update', 123), workTogetherPayload())->assertRedirect(route('admin.work-together.index'));
    $this->delete(route('admin.work-together.destroy', 123))->assertRedirect(route('admin.work-together.index'));
    expect(ActivityLog::query()->count())->toBe(0);
})->with(['work_together_sections', 'work_together_cards']);

test('work together does not suppress unrelated database errors', function (): void {
    Schema::table('work_together_sections', function (Blueprint $table): void {
        $table->renameColumn('heading', 'unavailable_heading');
    });
    $this->withoutExceptionHandling();
    expect(fn () => $this->post(route('admin.work-together.store'), workTogetherPayload()))->toThrow(QueryException::class);
});

test('work together view escapes saved content and does not modify records', function (): void {
    $section = workTogetherSaved();
    $card = $section->cards()->create(['title' => '<script>bad()</script>']);
    $attributes = $section->fresh()->getAttributes();
    $count = ActivityLog::query()->count();
    $this->get(route('admin.work-together.show', $section))->assertSuccessful()->assertSee('&lt;script&gt;bad()&lt;/script&gt;', false)->assertDontSee('<script>bad()</script>', false);
    expect($section->fresh()->getAttributes())->toBe($attributes)->and(ActivityLog::query()->count())->toBe($count);
});

test('work together separate layout saves only layout and preserves cards and configured language', function (int $columns): void {
    $this->post(route('admin.work-together.store'), workTogetherPayload())->assertSessionHasNoErrors();
    $section = WorkTogetherSection::query()->sole();
    $before = $section->getAttributes();
    $cards = $section->cards->toArray();
    $other = workTogetherSaved('fr');
    $this->get(route('admin.work-together.index'))->assertSee(route('admin.work-together.homepage-layout-settings'), false);
    $this->put(route('admin.work-together.homepage-layout-settings.update'), ['cards_per_row' => $columns, 'heading' => 'Do not save', 'language' => 'fr', 'cards' => []])
        ->assertRedirect(route('admin.work-together.homepage-layout-settings'))->assertSessionHasNoErrors();
    $section->refresh();
    expect($section->cards_per_row)->toBe($columns)->and($section->cards->toArray())->toBe($cards)
        ->and(array_diff_key($section->getAttributes(), array_flip(['cards_per_row', 'updated_at', 'updated_by'])))->toBe(array_diff_key($before, array_flip(['cards_per_row', 'updated_at', 'updated_by'])))
        ->and($other->fresh()->cards_per_row)->toBe(3);
    $this->get(route('admin.work-together.homepage-layout-settings'))->assertSuccessful()->assertSee('Homepage Layout Settings')
        ->assertSee('value="'.$columns.'" selected', false);
    $this->get(route('admin.work-together.edit', $section))->assertDontSee('name="cards_per_row"', false);
    $this->put(route('admin.work-together.update', $section), workTogetherPayload(['cards_per_row' => 3]))->assertSessionHasNoErrors();
    expect($section->fresh()->cards_per_row)->toBe($columns);
    if ($columns === 4) {
        expect(ActivityLog::query()->where('module', 'work_together')->where('action', 'updated')->exists())->toBeTrue();
    }
})->with([3, 4]);

test('work together layout missing section disables save and never creates content', function (): void {
    $this->get(route('admin.work-together.homepage-layout-settings'))->assertSuccessful()
        ->assertSee('Add a How We Can Work Together section')->assertSee('disabled', false);
    $this->put(route('admin.work-together.homepage-layout-settings.update'), ['cards_per_row' => 4])->assertSessionHas('warning');
    expect(WorkTogetherSection::query()->count())->toBe(0);
});

test('work together layout handles missing schema safely', function (bool $missingTable): void {
    if ($missingTable) {
        Schema::drop('work_together_cards');
        Schema::drop('work_together_sections');
    } else {
        Schema::table('work_together_sections', fn (Blueprint $table) => $table->dropColumn('cards_per_row'));
    }
    $this->get(route('admin.work-together.homepage-layout-settings'))->assertSuccessful()->assertSee('Layout setup is incomplete')->assertSee('disabled', false);
    $this->put(route('admin.work-together.homepage-layout-settings.update'), ['cards_per_row' => 4])->assertSessionHas('error');
})->with([true, false]);

test('work together layout permits viewers but restricts writes to editors', function (): void {
    $section = workTogetherSaved();
    $viewer = workTogetherLimitedAdmin(['work-together.view']);
    $this->actingAs($viewer)->get(route('admin.work-together.homepage-layout-settings'))->assertSuccessful()->assertDontSee('Save Settings');
    $this->put(route('admin.work-together.homepage-layout-settings.update'), ['cards_per_row' => 4])->assertForbidden();
    expect($section->fresh()->cards_per_row)->toBe(3);
    $this->actingAs(workTogetherLimitedAdmin([]))->get(route('admin.work-together.homepage-layout-settings'))->assertForbidden();
});
