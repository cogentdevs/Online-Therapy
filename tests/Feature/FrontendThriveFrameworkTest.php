<?php

use App\Models\HomeHeroSection;
use App\Models\ThriveFrameworkSection;
use App\Models\ThriveFrameworkStage;
use App\Models\User;
use App\Models\WhoIHelpSection;
use App\Services\SiteVisitService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Database\QueryException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
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
    $this->mock(SiteVisitService::class)->shouldReceive('record')->zeroOrMoreTimes();
    foreach ([
        'User', 'ActivityLog', 'GeneralSetting', 'Language', 'Category', 'MetaTag', 'Ad', 'HomeCard',
        'HomeCardTitlePosition', 'HomePageSectionHeading', 'Article', 'Magazine', 'Course', 'TazaShumara',
        'Banner', 'Slider', 'HomeHeroSection', 'WhoIHelpSection', 'WhoIHelpItem', 'SiteVisit', 'SearchContent',
        'Consultancy', 'FaqCategory', 'Faq', 'About', 'Author', 'AuthorGeneralSetting',
    ] as $modelName) {
        $class = 'App\\Models\\'.$modelName;
        frontendFrameworkFixtureTable(new $class);
    }
    foreach (['article_category_maps' => 'article_id', 'magazine_category_maps' => 'magazine_id', 'related_consultancies' => 'consultancy_id'] as $name => $key) {
        Schema::create($name, function (Blueprint $table) use ($name, $key): void {
            $table->unsignedBigInteger($key);
            $table->unsignedBigInteger($name === 'related_consultancies' ? 'related_consultancy_id' : 'category_id');
            $table->timestamps();
        });
    }
    foreach (['roles', 'permissions'] as $name) {
        Schema::create($name, function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('guard_name');
            $table->timestamps();
        });
    }
    foreach (['roles' => 'role_id', 'permissions' => 'permission_id'] as $suffix => $key) {
        Schema::create('model_has_'.$suffix, function (Blueprint $table) use ($key): void {
            $table->unsignedBigInteger($key);
            $table->string('model_type');
            $table->unsignedBigInteger('model_id');
        });
    }
    Schema::create('role_has_permissions', function (Blueprint $table): void {
        $table->unsignedBigInteger('permission_id');
        $table->unsignedBigInteger('role_id');
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
        $table->unsignedBigInteger('created_by')->nullable();
        $table->unsignedBigInteger('updated_by')->nullable();
        $table->timestamps();
    });
    Schema::create('thrive_framework_stages', function (Blueprint $table): void {
        $table->id();
        $table->foreignId('section_id')->constrained('thrive_framework_sections')->cascadeOnDelete();
        $table->string('letter', 10);
        $table->string('title');
        $table->string('stage_label', 50)->nullable();
        $table->text('description')->nullable();
        $table->unsignedInteger('sort_order')->default(0);
        $table->boolean('is_active')->default(true);
        $table->timestamps();
    });
    app(PermissionRegistrar::class)->forgetCachedPermissions();
});

afterEach(function (): void {
    DB::disconnect('sqlite');
});

function frontendFrameworkFixtureTable(Model $model): void
{
    $columns = array_unique([...$model->getFillable(), 'created_by', 'updated_by', 'published_by', 'owner_admin_id',
        ...($model instanceof User ? ['email_verified_at', 'remember_token', 'parent_admin_id', 'activation_token', 'activation_token_expires_at', 'consumed_activation_token_hash'] : [])]);
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

function frontendFrameworkSaved(array $overrides = []): ThriveFrameworkSection
{
    return ThriveFrameworkSection::query()->create(array_replace([
        'language' => 'en', 'eyebrow' => 'Saved signature approach', 'heading' => 'Saved THRIVE™ Framework',
        'description_one' => 'Saved first paragraph', 'description_two' => 'Saved second paragraph', 'is_active' => true,
    ], $overrides));
}

function frontendFrameworkStage(ThriveFrameworkSection $section, array $overrides = []): ThriveFrameworkStage
{
    return $section->stages()->create(array_replace([
        'letter' => 'T', 'title' => 'Saved stage title', 'description' => 'Saved stage description',
    ], $overrides));
}

function frontendFrameworkXPath(string $html): DOMXPath
{
    $document = new DOMDocument;
    $document->loadHTML('<?xml encoding="UTF-8">'.$html, LIBXML_NOERROR | LIBXML_NOWARNING);

    return new DOMXPath($document);
}

test('framework is placed immediately after Who I Help and after Home Hero exactly once', function (): void {
    $hero = HomeHeroSection::query()->create(['language' => 'en', 'eyebrow' => 'Unchanged Hero', 'heading_before_highlight' => 'Original Hero', 'is_active' => true]);
    $who = WhoIHelpSection::query()->create(['language' => 'en', 'eyebrow' => 'Unchanged Who', 'heading' => 'Original Who I Help', 'description' => 'Existing description', 'bottom_message' => 'Existing message', 'is_active' => true]);
    $who->items()->create(['title' => 'Original help card', 'is_active' => true]);
    $section = frontendFrameworkSaved();
    frontendFrameworkStage($section);
    $beforeHero = $hero->fresh()->getAttributes();
    $beforeWho = $who->fresh()->getAttributes();
    $response = $this->get(route('frontend.home'))->assertSuccessful()->assertSee('Unchanged Hero')->assertSee('Original help card');
    $xpath = frontendFrameworkXPath($response->getContent());
    expect($xpath->query('//section[@class="front-thrive-framework"]')->length)->toBe(1)
        ->and($xpath->query('//section[contains(@class,"front-managed-hero")]/following-sibling::*[1][@class="front-who-i-help"]/following-sibling::*[1][@class="front-thrive-framework"]')->length)->toBe(1)
        ->and($hero->fresh()->getAttributes())->toBe($beforeHero)->and($who->fresh()->getAttributes())->toBe($beforeWho);
});

test('framework hides missing inactive and other-language content', function (string $state): void {
    if ($state !== 'missing') {
        frontendFrameworkSaved($state === 'inactive' ? ['is_active' => false] : ['language' => 'ur']);
    }
    $this->get(route('frontend.home'))->assertSuccessful()->assertViewHas('thriveFramework', null)->assertDontSee('front-thrive-framework', false);
})->with(['missing', 'inactive', 'other-language']);

test('framework renders saved Unicode content and only ordered active stages using eager loading', function (): void {
    $section = frontendFrameworkSaved();
    $last = frontendFrameworkStage($section, ['title' => 'Last visible stage', 'sort_order' => 8]);
    $first = frontendFrameworkStage($section, ['letter' => 'H', 'title' => 'First visible stage', 'sort_order' => 2, 'stage_label' => 'CUSTOM STAGE']);
    $second = frontendFrameworkStage($section, ['letter' => 'R', 'title' => 'Second visible stage', 'sort_order' => 2, 'description' => null]);
    frontendFrameworkStage($section, ['title' => 'Inactive forbidden stage', 'sort_order' => 0, 'is_active' => false]);
    $deleted = frontendFrameworkStage($section, ['title' => 'Deleted forbidden stage']);
    $deleted->delete();
    $queries = [];
    DB::listen(function (QueryExecuted $query) use (&$queries): void {
        if (str_contains($query->sql, 'from "thrive_framework_')) {
            $queries[] = $query->sql;
        }
    });
    $response = $this->get(route('frontend.home'))->assertSuccessful()
        ->assertSee('Saved signature approach')->assertSee('Saved THRIVE™ Framework')
        ->assertSee('Saved first paragraph')->assertSee('Saved second paragraph')
        ->assertSeeInOrder(['First visible stage', 'Second visible stage', 'Last visible stage'])
        ->assertDontSee('Inactive forbidden stage')->assertDontSee('Deleted forbidden stage')
        ->assertViewHas('thriveFramework', fn ($framework): bool => $framework->relationLoaded('stages')
            && $framework->stages->pluck('id')->all() === [$first->id, $second->id, $last->id]);
    $xpath = frontendFrameworkXPath($response->getContent());
    $labels = $xpath->query('//span[@class="front-thrive-framework__stage-label"]');
    expect($labels->item(0)->textContent)->toBe('CUSTOM STAGE')->and($labels->item(1)->textContent)->toBe('STAGE 2')
        ->and($labels->item(2)->textContent)->toBe('STAGE 3')->and(count($queries))->toBe(2)
        ->and($xpath->query('//p[@class="front-thrive-framework__stage-description"]')->length)->toBe(2);
});

test('framework omits blank descriptions and never reserves empty paragraphs', function (?string $one, ?string $two, int $count): void {
    frontendFrameworkSaved(['description_one' => $one, 'description_two' => $two]);
    $response = $this->get(route('frontend.home'))->assertSuccessful();
    $xpath = frontendFrameworkXPath($response->getContent());
    expect($xpath->query('//p[@class="front-thrive-framework__description"]')->length)->toBe($count)
        ->and($xpath->query('//div[@class="front-thrive-framework__grid"]')->length)->toBe(0);
})->with(['neither' => [null, null, 0], 'first only' => ['One', null, 1], 'second only' => [null, 'Two', 1], 'both' => ['One', 'Two', 2]]);

test('framework handles blank legacy headings and entirely empty sections safely', function (): void {
    $section = frontendFrameworkSaved(['eyebrow' => '', 'heading' => '', 'description_one' => null, 'description_two' => null]);
    $this->get(route('frontend.home'))->assertSuccessful()->assertDontSee('front-thrive-framework', false);
    frontendFrameworkStage($section, ['description' => null]);
    $xpath = frontendFrameworkXPath($this->get(route('frontend.home'))->assertSuccessful()->getContent());
    expect($xpath->query('//section[@class="front-thrive-framework"]')->length)->toBe(1)
        ->and($xpath->query('//header[@class="front-thrive-framework__header"]')->length)->toBe(0)
        ->and($xpath->query('//*[@id="thrive-framework-heading"]')->length)->toBe(0)
        ->and($xpath->query('//p[@class="front-thrive-framework__stage-description"]')->length)->toBe(0);
});

test('framework hides the grid with no active stages', function (): void {
    $section = frontendFrameworkSaved();
    frontendFrameworkStage($section, ['is_active' => false]);
    $this->get(route('frontend.home'))->assertSuccessful()->assertSee('Saved THRIVE™ Framework')
        ->assertDontSee('front-thrive-framework__grid', false)->assertDontSee('front-thrive-framework__card', false);
});

test('framework renders any saved stage count without placeholder slots', function (int $count): void {
    $section = frontendFrameworkSaved();
    foreach (range(1, $count) as $position) {
        frontendFrameworkStage($section, ['letter' => (string) $position, 'title' => 'Stage title '.$position, 'sort_order' => $position]);
    }
    $xpath = frontendFrameworkXPath($this->get(route('frontend.home'))->assertSuccessful()->getContent());
    expect($xpath->query('//article[@class="front-thrive-framework__card"]')->length)->toBe($count);
})->with([1, 2, 3, 4, 6, 8]);

test('framework pending tables hide safely without querying missing relationships', function (string $table): void {
    Schema::drop($table);
    $this->get(route('frontend.home'))->assertSuccessful()->assertViewHas('thriveFramework', null)->assertDontSee('front-thrive-framework', false);
})->with(['thrive_framework_sections', 'thrive_framework_stages']);

test('framework unrelated database errors are not suppressed', function (): void {
    frontendFrameworkStage(frontendFrameworkSaved());
    Schema::table('thrive_framework_stages', function (Blueprint $table): void {
        $table->renameColumn('section_id', 'unavailable_section');
    });
    $this->withoutExceptionHandling();
    expect(fn () => $this->get(route('frontend.home')))->toThrow(QueryException::class);
});

test('framework escapes Admin content and does not add asynchronous placeholders', function (): void {
    $section = frontendFrameworkSaved(['heading' => '<script>untrusted()</script>']);
    frontendFrameworkStage($section, ['description' => '<img src=x onerror=bad()>']);
    $response = $this->get(route('frontend.home'))->assertSuccessful()->assertSee('&lt;script&gt;untrusted()&lt;/script&gt;', false)
        ->assertDontSee('<script>untrusted()</script>', false)->assertSee('&lt;img src=x onerror=bad()&gt;', false);
    $xpath = frontendFrameworkXPath($response->getContent());
    expect($xpath->query('//section[@class="front-thrive-framework"]//*[contains(@class,"skeleton")]')->length)->toBe(0);
});

test('framework reflects existing Admin CRUD updates reorder status changes and deletion on the homepage', function (): void {
    $admin = User::factory()->create(['is_active' => true]);
    $admin->assignRole(Role::findOrCreate('super-admin', 'web'));
    $this->actingAs($admin);
    $payload = ['eyebrow' => 'Admin saved approach', 'heading' => 'Admin THRIVE™ heading', 'is_active' => '1', 'stages_present' => '1',
        'stages' => [['letter' => 'A', 'title' => 'Admin first'], ['letter' => 'B', 'title' => 'Admin second']]];
    $this->post(route('admin.thrive-framework.store'), $payload)->assertSessionHasNoErrors();
    $section = ThriveFrameworkSection::query()->sole();
    [$first, $second] = $section->stages->all();
    $this->get(route('frontend.home'))->assertSuccessful()->assertSee('Admin saved approach')->assertSee('Admin second');
    $payload['stages'] = [
        ['id' => $second->id, 'letter' => 'B', 'title' => 'Reordered second', 'is_active' => '1'],
        ['id' => $first->id, 'letter' => 'A', 'title' => 'Disabled first', 'is_active' => '0'],
        ['letter' => 'C', 'title' => 'Newly saved third'],
    ];
    $this->put(route('admin.thrive-framework.update', $section), $payload)->assertSessionHasNoErrors();
    $this->get(route('frontend.home'))->assertSuccessful()->assertSeeInOrder(['Reordered second', 'Newly saved third'])->assertDontSee('Disabled first');
    $payload['stages'] = [['id' => $first->id, 'letter' => 'A', 'title' => 'Enabled first', 'is_active' => '1']];
    $this->put(route('admin.thrive-framework.update', $section), $payload)->assertSessionHasNoErrors();
    $this->get(route('frontend.home'))->assertSuccessful()->assertSee('Enabled first')->assertDontSee('Reordered second')->assertDontSee('Newly saved third');
    $this->put(route('admin.thrive-framework.update', $section), [...$payload, 'is_active' => '0'])->assertSessionHasNoErrors();
    $this->get(route('frontend.home'))->assertSuccessful()->assertDontSee('front-thrive-framework', false);
    $this->delete(route('admin.thrive-framework.destroy', $section))->assertRedirect(route('admin.thrive-framework.index'));
    $this->get(route('frontend.home'))->assertSuccessful()->assertDontSee('front-thrive-framework', false);
});

test('framework frontend applies a safe saved desktop layout without empty slots', function (int $columns): void {
    $section = frontendFrameworkSaved(['cards_per_row' => $columns]);
    foreach (range(1, 6) as $number) {
        frontendFrameworkStage($section, ['title' => 'Layout stage '.$number, 'sort_order' => $number]);
    }
    $xpath = frontendFrameworkXPath($this->get(route('frontend.home'))->assertSuccessful()->getContent());
    expect($xpath->query('//div[@class="front-thrive-framework__grid"]')->item(0)->getAttribute('style'))->toBe('--framework-columns: '.$columns.';')
        ->and($xpath->query('//article[@class="front-thrive-framework__card"]')->length)->toBe(6);
})->with([3, 4]);

test('framework frontend safely falls back to three for legacy missing or unsupported layouts', function (mixed $columns): void {
    $section = frontendFrameworkSaved();
    $section->setAttribute('cards_per_row', $columns);
    $section->setRelation('stages', collect([new ThriveFrameworkStage(['letter' => 'T', 'title' => 'Saved title'])]));
    expect(view('frontend.partials.thrive-framework', ['thriveFramework' => $section])->render())->toContain('--framework-columns: 3;');
})->with(['null' => [null], 'unsupported' => [12]]);

test('framework browser fixture exports only when explicitly requested', function (): void {
    $section = frontendFrameworkSaved([
        'eyebrow' => 'MY SIGNATURE APPROACH', 'heading' => 'The THRIVE™ Framework',
        'description_one' => 'Lasting change begins when you understand the patterns behind your thoughts, emotions, and behaviours.',
        'description_two' => 'The THRIVE™ Framework is my signature six-stage approach to emotional growth, helping you explore underlying patterns, reconnect with yourself, and move forward with greater self-awareness, self-trust, and purpose.',
    ]);
    foreach ([
        ['T', 'Trace the Root', 'Explore the underlying patterns, beliefs, and experiences that may influence your thoughts, emotions, and behaviours.'],
        ['H', 'Heal', 'Begin working through emotional patterns and experiences with greater awareness and self-understanding.'],
        ['R', 'Reconnect', 'Reconnect with yourself, your emotions, your needs, and what matters to you.'],
        ['I', 'Integrate', 'Bring new insights into everyday life and practise healthier, more supportive responses.'],
        ['V', 'Value Yourself', 'Strengthen self-worth, develop self-trust, and recognise the importance of your needs and boundaries.'],
        ['E', 'Evolve', 'Continue growing with greater clarity, emotional resilience, and a stronger sense of purpose.'],
    ] as $index => [$letter, $title, $description]) {
        frontendFrameworkStage($section, compact('letter', 'title', 'description') + ['sort_order' => $index]);
    }
    $response = $this->get(route('frontend.home'))->assertSuccessful();
    if (getenv('THRIVE_FRAMEWORK_PREVIEW') !== '1') {
        return;
    }
    $section = $response->viewData('thriveFramework');
    $manifest = json_decode(file_get_contents(public_path('build/manifest.json')), true, flags: JSON_THROW_ON_ERROR);
    $css = $manifest['resources/sass/front/front.scss']['file'];
    $html = '<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">'
        .'<link rel="stylesheet" href="/build/'.$css.'"></head><body class="front-body"><main>'
        .view('frontend.partials.thrive-framework', ['thriveFramework' => $section])->render().'</main></body></html>';
    $directory = storage_path('framework/testing/thrive-framework-ui');
    File::ensureDirectoryExists($directory);
    File::put($directory.'/framework.html', $html);
});
