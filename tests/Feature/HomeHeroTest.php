<?php

use App\Models\About;
use App\Models\ActivityLog;
use App\Models\Ad;
use App\Models\Article;
use App\Models\Author;
use App\Models\AuthorGeneralSetting;
use App\Models\Banner;
use App\Models\Category;
use App\Models\Consultancy;
use App\Models\Course;
use App\Models\Faq;
use App\Models\FaqCategory;
use App\Models\GeneralSetting;
use App\Models\HomeCard;
use App\Models\HomeCardTitlePosition;
use App\Models\HomeHeroSection;
use App\Models\HomePageSectionHeading;
use App\Models\Language;
use App\Models\Magazine;
use App\Models\MetaTag;
use App\Models\SearchContent;
use App\Models\SiteVisit;
use App\Models\Slider;
use App\Models\TazaShumara;
use App\Models\User;
use App\Models\WhoIHelpItem;
use App\Models\WhoIHelpSection;
use App\Services\SiteVisitService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
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
    $this->mock(SiteVisitService::class)->shouldReceive('record')->zeroOrMoreTimes();
    $this->heroPublicPath = storage_path('framework/testing/home-hero-'.Str::uuid());
    File::ensureDirectoryExists($this->heroPublicPath);
    $this->app->usePublicPath($this->heroPublicPath);

    Schema::create('home_hero_sections', function (Blueprint $table): void {
        $table->id();
        $table->string('language')->unique();
        $table->string('eyebrow');
        $table->text('heading_before_highlight');
        foreach (['highlighted_text', 'heading_after_highlight', 'description', 'hero_image', 'image_alt',
            'primary_button_text', 'primary_button_url', 'secondary_button_text', 'secondary_button_url'] as $field) {
            $table->text($field)->nullable();
        }
        $table->boolean('is_active')->default(true);
        $table->unsignedBigInteger('created_by')->nullable();
        $table->unsignedBigInteger('updated_by')->nullable();
        $table->timestamps();
    });

    homeHeroFixtureTable(new User, ['email_verified_at', 'remember_token', 'parent_admin_id',
        'activation_token', 'activation_token_expires_at', 'consumed_activation_token_hash']);
    homeHeroFixtureTable(new ActivityLog);

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

    foreach ([
        GeneralSetting::class, Language::class, Category::class,
        MetaTag::class, Ad::class, HomeCard::class,
        HomeCardTitlePosition::class, HomePageSectionHeading::class,
        Article::class, Magazine::class, Course::class,
        TazaShumara::class, Banner::class, Slider::class,
        WhoIHelpSection::class, WhoIHelpItem::class, SiteVisit::class,
        SearchContent::class, Consultancy::class, FaqCategory::class,
        Faq::class, About::class, Author::class,
        AuthorGeneralSetting::class,
    ] as $modelClass) {
        homeHeroFixtureTable(new $modelClass);
    }
    foreach (['article_category_maps' => 'article_id', 'magazine_category_maps' => 'magazine_id'] as $tableName => $contentKey) {
        Schema::create($tableName, function (Blueprint $table) use ($contentKey): void {
            $table->unsignedBigInteger($contentKey);
            $table->unsignedBigInteger('category_id');
        });
    }
    Schema::create('related_consultancies', function (Blueprint $table): void {
        $table->unsignedBigInteger('consultancy_id');
        $table->unsignedBigInteger('related_consultancy_id');
        $table->timestamps();
    });
    app(PermissionRegistrar::class)->forgetCachedPermissions();
    $this->heroAdmin = homeHeroAdmin(['super-admin']);
});

afterEach(function (): void {
    DB::disconnect('sqlite');
    if (isset($this->heroPublicPath)) {
        $testingRoot = realpath(storage_path('framework/testing'));
        $publicPath = realpath($this->heroPublicPath);
        if ($testingRoot !== false && $publicPath !== false
            && str_starts_with($publicPath, $testingRoot.DIRECTORY_SEPARATOR.'home-hero-')) {
            File::deleteDirectory($publicPath);
        }
    }
});

/**
 * Empty supporting tables let real page queries execute without running migrations.
 *
 * @param  array<int, string>  $extraColumns
 */
function homeHeroFixtureTable(Model $model, array $extraColumns = []): void
{
    $columns = array_unique([...$model->getFillable(), ...$extraColumns, 'created_by', 'updated_by', 'published_by', 'owner_admin_id']);
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

/**
 * @param  array<int, string>  $roles
 * @param  array<int, string>  $permissions
 */
function homeHeroAdmin(array $roles = [], array $permissions = []): User
{
    $admin = User::factory()->create(['is_active' => true]);
    foreach ($roles as $roleName) {
        $admin->assignRole(Role::findOrCreate($roleName, 'web'));
    }
    if ($permissions !== []) {
        $role = Role::create(['name' => 'hero-editor-'.Str::random(8), 'guard_name' => 'web']);
        foreach (['admin.access', ...$permissions] as $permissionName) {
            $role->permissions()->attach(Permission::findOrCreate($permissionName, 'web'));
        }
        $admin->assignRole($role);
    }

    return $admin;
}

/** @return array<string, mixed> */
function homeHeroPayload(array $overrides = []): array
{
    return array_replace([
        'eyebrow' => 'Saved wellness eyebrow',
        'heading_before_highlight' => 'Saved heading before',
        'is_active' => '1',
    ], $overrides);
}

function homeHeroSaved(array $overrides = []): HomeHeroSection
{
    return HomeHeroSection::query()->create([
        'language' => 'en', ...homeHeroPayload($overrides),
    ]);
}

test('Hero image forms show recommended pixels and dimension indicators', function (): void {
    $this->actingAs($this->heroAdmin)->get(route('admin.home-hero.create'))->assertSuccessful()
        ->assertSee('Recommended size: 960 × 1200 px (portrait, 4:5).')
        ->assertSee('data-selected-image-size', false);
    $hero = homeHeroSaved(['hero_image' => 'images/backend-images/home-hero/portrait.png']);
    $this->get(route('admin.home-hero.edit', $hero))->assertSuccessful()
        ->assertSee('data-current-hero-image', false)
        ->assertSee('data-current-image-size', false)
        ->assertSee('currentImage.naturalWidth', false)
        ->assertSee('URL.revokeObjectURL(objectUrl)', false);
});

test('Home Hero routes retain their action permissions and Website listing link', function (): void {
    foreach (['index' => 'view', 'show' => 'view', 'create' => 'create', 'store' => 'create',
        'edit' => 'edit', 'update' => 'edit', 'destroy' => 'delete'] as $action => $permission) {
        $route = app('router')->getRoutes()->getByName('admin.home-hero.'.$action);
        expect($route)->not->toBeNull()
            ->and($route->gatherMiddleware())->toContain('auth', 'admin-access', 'permission:home-hero.'.$permission);
    }
    expect(config('admin_modules.modules.home-hero.actions'))->toHaveKeys(['view', 'create', 'edit', 'delete']);
    $html = $this->actingAs($this->heroAdmin)->get(route('admin.home-hero.index'))->assertSuccessful()->getContent();
    expect(substr_count($html, 'href="'.route('admin.home-hero.index').'"'))->toBe(1)
        ->and($html)->toContain('id="websiteManagementMenu"');
});

test('listing enables Add New only before a configured-language record exists', function (): void {
    $this->actingAs($this->heroAdmin)->get(route('admin.home-hero.index'))
        ->assertSuccessful()->assertSee(route('admin.home-hero.create'), false)
        ->assertDontSee('Only one Home Hero is allowed for the configured language.');
    homeHeroSaved(['language' => 'ur']);
    $this->get(route('admin.home-hero.create'))->assertSuccessful()->assertSee('data-home-hero-form', false);
    homeHeroSaved(['is_active' => false]);
    expect(config('content_language.code'))->toBe('en')
        ->and(HomeHeroSection::query()->orderBy('id')->pluck('language')->all())->toBe(['ur', 'en']);
    $this->get(route('admin.home-hero.index'))->assertSuccessful()
        ->assertViewHas('heroes', fn ($heroes): bool => $heroes->count() === 1)
        ->assertSee('Only one Home Hero is allowed for the configured language.')
        ->assertSee('disabled aria-disabled="true"', false)
        ->assertDontSee(route('admin.home-hero.create'), false);
    $this->get(route('admin.home-hero.create'))->assertRedirect(route('admin.home-hero.index'));
    $this->post(route('admin.home-hero.store'), homeHeroPayload())->assertSessionHasErrors('eyebrow');
    expect(HomeHeroSection::query()->where('language', 'en')->count())->toBe(1);
});

test('missing Home Hero table shows a setup warning and disables database actions', function (): void {
    Schema::drop('home_hero_sections');

    $this->actingAs($this->heroAdmin)->get(route('admin.home-hero.index'))
        ->assertSuccessful()
        ->assertViewHas('heroTableAvailable', false)
        ->assertSee('Home Hero setup is incomplete.')
        ->assertSee('Complete setup to manage Home Hero.')
        ->assertSee('disabled aria-disabled="true"', false)
        ->assertDontSee(route('admin.home-hero.create'), false)
        ->assertDontSee('View Home Hero')
        ->assertDontSee('Edit Home Hero')
        ->assertDontSee('Delete Home Hero');

    expect(ActivityLog::query()->count())->toBe(0);
});

test('direct Home Hero actions safely redirect to setup when the table is missing', function (string $action, string $method): void {
    Schema::drop('home_hero_sections');
    $parameters = in_array($action, ['show', 'edit', 'update', 'destroy'], true) ? [123] : [];
    $payload = homeHeroPayload();

    if ($action === 'store') {
        $payload['hero_image'] = UploadedFile::fake()->image('must-not-be-stored.png');
    }

    $this->actingAs($this->heroAdmin)->{$method}(route('admin.home-hero.'.$action, $parameters), $payload)
        ->assertRedirect(route('admin.home-hero.index'));
    $this->get(route('admin.home-hero.index'))->assertSuccessful()->assertSee('Home Hero setup is incomplete.');

    expect(File::exists(public_path('images/backend-images/home-hero')))->toBeFalse()
        ->and(ActivityLog::query()->count())->toBe(0);
})->with([
    'create' => ['create', 'get'], 'store' => ['store', 'post'],
    'view' => ['show', 'get'], 'edit' => ['edit', 'get'],
    'update' => ['update', 'put'], 'delete' => ['destroy', 'delete'],
]);

test('missing Home Hero table does not bypass existing Admin permissions', function (): void {
    Schema::drop('home_hero_sections');

    $this->get(route('admin.home-hero.index'))->assertRedirect(route('admin.login'));
    $viewer = homeHeroAdmin(permissions: ['home-hero.view']);
    $this->actingAs($viewer)->get(route('admin.home-hero.index'))->assertSuccessful();
    $this->get(route('admin.home-hero.create'))->assertForbidden();
    $this->delete(route('admin.home-hero.destroy', 123))->assertForbidden();
});

test('homepage hides Home Hero safely when its table is missing', function (): void {
    Schema::drop('home_hero_sections');

    $this->get(route('frontend.home'))->assertSuccessful()
        ->assertViewHas('homeHero', null)
        ->assertDontSee('front-managed-hero', false)
        ->assertDontSee('frontHeroCarousel', false);
});

test('Home Hero setup guard does not suppress unrelated database errors', function (): void {
    Schema::table('home_hero_sections', function (Blueprint $table): void {
        $table->renameColumn('heading_before_highlight', 'unavailable_heading');
    });
    $this->withoutExceptionHandling()->actingAs($this->heroAdmin);

    expect(fn () => $this->post(route('admin.home-hero.store'), homeHeroPayload()))
        ->toThrow(QueryException::class);
});

test('only the three required fields are mandatory and optional button halves can be saved', function (): void {
    $this->actingAs($this->heroAdmin)->post(route('admin.home-hero.store'), [])
        ->assertSessionHasErrors(['eyebrow', 'heading_before_highlight', 'is_active']);
    $this->post(route('admin.home-hero.store'), homeHeroPayload([
        'description' => '   ', 'highlighted_text' => '', 'primary_button_text' => 'Incomplete button',
    ]))->assertRedirect(route('admin.home-hero.index'))->assertSessionHasNoErrors();
    $hero = HomeHeroSection::query()->sole();
    expect($hero->description)->toBeNull()->and($hero->highlighted_text)->toBeNull()
        ->and($hero->hero_image)->toBeNull()->and($hero->primary_button_url)->toBeNull()
        ->and($hero->created_by)->toEqual($this->heroAdmin->id);
});

test('Home Hero listing eye opens the saved record with an image without changing data', function (): void {
    $hero = homeHeroSaved([
        'highlighted_text' => 'Detail highlight', 'heading_after_highlight' => 'Detail ending',
        'description' => 'Detail description', 'hero_image' => 'images/backend-images/home-hero/detail.png',
        'image_alt' => 'Detail image alt', 'primary_button_text' => 'Detail primary',
        'primary_button_url' => '/courses', 'secondary_button_text' => 'Detail secondary', 'secondary_button_url' => '/taaruf',
    ]);
    $this->actingAs($this->heroAdmin);
    $document = new DOMDocument;
    $document->loadHTML($this->get(route('admin.home-hero.index'))->assertSuccessful()->getContent(), LIBXML_NOERROR | LIBXML_NOWARNING);
    $xpath = new DOMXPath($document);
    $link = $xpath->query('//a[@aria-label="View Home Hero"]')->item(0);
    expect($link)->not->toBeNull();
    $before = $hero->fresh()->getAttributes();
    $logCount = ActivityLog::query()->count();
    $response = $this->get($link->getAttribute('href'))->assertSuccessful()->assertSee('Detail description')
        ->assertSee(asset($hero->hero_image), false)->assertSee('Detail primary')->assertSee('Detail secondary')
        ->assertSee('Detail image alt')->assertSee('Detail highlight')->assertSee('Detail ending')
        ->assertSee(route('admin.home-hero.index'), false)->assertSee(route('admin.home-hero.edit', $hero), false);
    foreach (['Small Heading / Eyebrow', 'Heading Before Highlight', 'Highlighted Text', 'Heading After Highlight',
        'Description', 'Hero Image', 'Image Alt Text', 'Primary Button Text', 'Primary Button URL',
        'Secondary Button Text', 'Secondary Button URL', 'Section Status', 'Created At', 'Updated At'] as $label) {
        $response->assertSee($label);
    }
    $response->assertSee($hero->created_at->format('d M Y, h:i A'))->assertSee($hero->updated_at->format('d M Y, h:i A'));
    expect($hero->fresh()->getAttributes())->toBe($before)->and(ActivityLog::query()->count())->toBe($logCount);
});

test('Home Hero details safely display empty fields and text-only buttons with view-only authorization', function (): void {
    $hero = homeHeroSaved(['primary_button_text' => 'Label without navigation']);
    $viewer = homeHeroAdmin(permissions: ['home-hero.view']);
    $this->actingAs($viewer)->get(route('admin.home-hero.show', $hero))->assertSuccessful()
        ->assertSee('No image')->assertSee('Label without navigation')->assertSee('—')
        ->assertDontSee(route('admin.home-hero.edit', $hero), false);
    $this->get(route('admin.home-hero.show', 99999))->assertNotFound();
    $this->actingAs(homeHeroAdmin(permissions: ['home-hero.create']))
        ->get(route('admin.home-hero.show', $hero))->assertForbidden();
});

test('Admin footer uses Thrive with Naila and the current year', function (): void {
    $this->travelTo(now()->setDate(2031, 5, 1));
    expect(view('layouts.adminLayout.admin-footer')->render())
        ->toContain('&copy; 2031 Thrive with Naila. All rights reserved.')
        ->not->toContain('Digital Magazine');
});

test('CRUD preserves inactive status and records create update delete activity', function (): void {
    $this->actingAs($this->heroAdmin)->post(route('admin.home-hero.store'), homeHeroPayload())
        ->assertRedirect(route('admin.home-hero.index'));
    $hero = HomeHeroSection::query()->sole();
    $this->get(route('admin.home-hero.show', $hero))->assertSuccessful()->assertSee($hero->eyebrow);
    $this->put(route('admin.home-hero.update', $hero), homeHeroPayload(['is_active' => '0', 'description' => 'Edited description']))
        ->assertRedirect(route('admin.home-hero.index'));
    expect($hero->refresh()->is_active)->toBeFalse()->and($hero->updated_by)->toEqual($this->heroAdmin->id);
    $edit = $this->get(route('admin.home-hero.edit', $hero))->assertSuccessful()->getContent();
    expect($edit)->toMatch('/id="home-hero-status-0"[^>]*checked/');
    $this->delete(route('admin.home-hero.destroy', $hero))->assertRedirect(route('admin.home-hero.index'));
    $this->assertModelMissing($hero);
    expect(ActivityLog::query()->where('module', 'home_hero')->pluck('action')->all())
        ->toBe(['created', 'status_changed', 'deleted']);
    $this->get(route('admin.home-hero.index'))->assertSuccessful()->assertSee(route('admin.home-hero.create'), false);
});

test('guests inactive admins and unpermitted admins cannot mutate Home Hero', function (): void {
    $this->get(route('admin.home-hero.index'))->assertRedirect(route('admin.login'));
    $viewer = homeHeroAdmin(permissions: ['home-hero.view']);
    $this->actingAs($viewer)->get(route('admin.home-hero.index'))->assertSuccessful();
    $this->get(route('admin.home-hero.create'))->assertForbidden();
    $this->post(route('admin.home-hero.store'), homeHeroPayload())->assertForbidden();
    $hero = homeHeroSaved();
    $this->get(route('admin.home-hero.show', $hero))->assertSuccessful();
    $this->get(route('admin.home-hero.edit', $hero))->assertForbidden();
    $this->put(route('admin.home-hero.update', $hero), homeHeroPayload())->assertForbidden();
    $this->delete(route('admin.home-hero.destroy', $hero))->assertForbidden();
    $viewer->update(['is_active' => false]);
    $this->get(route('admin.home-hero.index'))->assertRedirect(route('admin.login'));
    expect(HomeHeroSection::query()->count())->toBe(1);
});

test('permitted custom admins can manage Hero but custom permission mode remains an allow list', function (): void {
    $editor = homeHeroAdmin(permissions: ['home-hero.view', 'home-hero.create', 'home-hero.edit', 'home-hero.delete']);
    $this->actingAs($editor)->post(route('admin.home-hero.store'), homeHeroPayload())
        ->assertRedirect(route('admin.home-hero.index'));
    $hero = HomeHeroSection::query()->sole();
    $editor->update(['permission_mode' => 'custom']);
    $this->actingAs($editor->fresh())->get(route('admin.home-hero.index'))->assertForbidden();
    $editor->permissions()->attach(Permission::findByName('home-hero.view', 'web'));
    $this->actingAs($editor->fresh())->get(route('admin.home-hero.index'))->assertSuccessful();
    $this->delete(route('admin.home-hero.destroy', $hero))->assertForbidden();
});

test('record access stays scoped to the configured language', function (): void {
    $hero = homeHeroSaved(['language' => 'ur']);
    $this->actingAs($this->heroAdmin);
    foreach (['show', 'edit'] as $action) {
        $this->get(route('admin.home-hero.'.$action, $hero))->assertNotFound();
    }
    $this->put(route('admin.home-hero.update', $hero), homeHeroPayload())->assertNotFound();
    $this->delete(route('admin.home-hero.destroy', $hero))->assertNotFound();
    expect($hero->fresh())->not->toBeNull();
});

test('upload replacement and deletion use only managed files in the isolated public directory', function (): void {
    $this->actingAs($this->heroAdmin)->post(route('admin.home-hero.store'), homeHeroPayload([
        'hero_image' => UploadedFile::fake()->image('wellness.png'),
    ]))->assertRedirect(route('admin.home-hero.index'));
    $hero = HomeHeroSection::query()->sole();
    $firstPath = public_path($hero->hero_image);
    expect($hero->hero_image)->toStartWith('images/backend-images/home-hero/')->and(is_file($firstPath))->toBeTrue();
    $this->put(route('admin.home-hero.update', $hero), homeHeroPayload())->assertSessionHasNoErrors();
    expect($hero->refresh()->hero_image)->not->toBeNull()->and(is_file($firstPath))->toBeTrue();
    $this->put(route('admin.home-hero.update', $hero), homeHeroPayload([
        'hero_image' => UploadedFile::fake()->image('wellness.png'),
    ]))->assertSessionHasNoErrors();
    $secondPath = public_path($hero->refresh()->hero_image);
    expect(is_file($firstPath))->toBeFalse()->and(is_file($secondPath))->toBeTrue();
    $this->delete(route('admin.home-hero.destroy', $hero))->assertSessionHasNoErrors();
    expect(is_file($secondPath))->toBeFalse();
});

test('unsafe button URLs and non-image uploads are rejected without creating content', function (string $url): void {
    $this->actingAs($this->heroAdmin)->post(route('admin.home-hero.store'), homeHeroPayload([
        'primary_button_url' => $url,
    ]))->assertSessionHasErrors('primary_button_url');
    expect(HomeHeroSection::query()->count())->toBe(0);
})->with(['javascript:alert(1)', '//example.com', '/\\example.com', 'https:example.com', 'https://', "/\texample.com"]);

test('invalid and oversized uploads are rejected', function (): void {
    foreach ([UploadedFile::fake()->create('bad.svg', 1, 'image/svg+xml'),
        UploadedFile::fake()->image('large.png')->size(5121)] as $upload) {
        $this->actingAs($this->heroAdmin)->post(route('admin.home-hero.store'), homeHeroPayload(['hero_image' => $upload]))
            ->assertSessionHasErrors('hero_image');
    }
    expect(HomeHeroSection::query()->count())->toBe(0);
});

test('homepage hides the managed Hero with no saved record inactive content or another language', function (): void {
    $this->get(route('frontend.home'))->assertSuccessful()->assertDontSee('front-managed-hero', false);
    $hero = homeHeroSaved(['is_active' => false]);
    $this->get(route('frontend.home'))->assertSuccessful()->assertDontSee('front-managed-hero', false)->assertDontSee($hero->eyebrow);
    $hero->update(['is_active' => true, 'language' => 'ur']);
    $this->get(route('frontend.home'))->assertSuccessful()->assertDontSee('front-managed-hero', false);
});

test('homepage Slider markup remains intact', function (): void {
    $source = file_get_contents(resource_path('views/frontend/index.blade.php'));
    $matched = preg_match('/    \{\{-- Hero slider and selected articles --\}\}.*?    <\/section>/s', $source, $matches);

    expect($matched)->toBe(1)
        ->and(hash('sha256', str_replace("\r\n", "\n", $matches[0])))
        ->toBe('b0ab18f0e47ae63d308b3665ff45b7d09bcecdcf012f48e1d0a8f382621533b1');
});

test('homepage Slider remains visible independently of the managed Home Hero', function (?bool $heroIsActive): void {
    $slider = Slider::query()->create([
        'language' => 'en', 'main_heading' => 'Navbar slider remains visible',
        'image' => 'images/backend-images/slider/legacy.png', 'isActive' => true,
    ]);
    HomeCard::query()->create([
        'title' => 'Unaffected homepage card', 'position' => 'below slider', 'is_active' => true,
    ]);

    if ($heroIsActive !== null) {
        homeHeroSaved(['is_active' => $heroIsActive]);
    }

    $response = $this->get(route('frontend.home'))->assertSuccessful()
        ->assertSee('frontHeroCarousel', false)
        ->assertSee('Navbar slider remains visible')
        ->assertSee(asset($slider->image), false)
        ->assertSee('Unaffected homepage card')
        ->assertViewHas('sliders', fn ($sliders): bool => $sliders->contains('id', $slider->id));
    $document = new DOMDocument;
    $document->loadHTML($response->getContent(), LIBXML_NOERROR | LIBXML_NOWARNING);
    $xpath = new DOMXPath($document);
    $heroes = $xpath->query('//section[contains(concat(" ", normalize-space(@class), " "), " front-managed-hero ")]');

    expect($heroes->length)->toBe($heroIsActive === true ? 1 : 0)
        ->and($xpath->query('//*[@id="frontHeroCarousel"]')->length)->toBe(1)
        ->and($xpath->query('//main/section[1]//*[@id="frontHeroCarousel"]')->length)->toBe(1)
        ->and(Slider::query()->findOrFail($slider->id)->main_heading)->toBe('Navbar slider remains visible');
})->with(['active Home Hero' => [true], 'inactive Home Hero' => [false], 'no Home Hero' => [null]]);

test('Admin Sliders CRUD remains functional', function (): void {
    Language::query()->create(['name' => 'English', 'code' => 'en', 'is_active' => true]);
    $this->actingAs($this->heroAdmin)->get(route('admin.slider.index'))->assertSuccessful();
    $this->get(route('admin.slider.create'))->assertSuccessful();
    $this->post(route('admin.slider.store'), [
        'language' => 'en', 'main_heading' => 'Saved legacy slider', 'content_position' => 'center',
        'image' => UploadedFile::fake()->image('legacy.png'),
    ])->assertRedirect(route('admin.slider.index'))->assertSessionHasNoErrors();
    $slider = Slider::query()->sole();
    $imagePath = public_path($slider->image);
    expect(is_file($imagePath))->toBeTrue();
    $this->get(route('admin.slider.edit', $slider))->assertSuccessful()->assertSee('Saved legacy slider');
    $this->post(route('admin.slider.update', $slider), [
        'language' => 'en', 'main_heading' => 'Edited legacy slider', 'content_position' => 'top',
        'isActive' => '1',
    ])->assertRedirect(route('admin.slider.index'))->assertSessionHasNoErrors();
    expect($slider->refresh()->main_heading)->toBe('Edited legacy slider');
    $this->delete(route('admin.slider.destroy', $slider))->assertRedirect(route('admin.slider.index'));
    $this->assertModelMissing($slider);
    expect(is_file($imagePath))->toBeFalse();
});

test('homepage renders saved Hero fields escaped and uses the existing Hero design and image skeleton', function (): void {
    $hero = homeHeroSaved([
        'heading_before_highlight' => '<script>bad()</script>', 'highlighted_text' => 'Saved highlight',
        'heading_after_highlight' => 'Saved after', 'description' => 'Saved description',
        'hero_image' => 'images/backend-images/home-hero/saved.png', 'image_alt' => 'Saved image alt',
        'primary_button_text' => 'Saved primary', 'primary_button_url' => '/courses',
        'secondary_button_text' => 'Saved secondary', 'secondary_button_url' => 'https://example.com/wellness',
    ]);
    $response = $this->get(route('frontend.home'))->assertSuccessful();
    foreach ([$hero->eyebrow, 'Saved highlight', 'Saved after', 'Saved description', 'Saved image alt', 'Saved primary', 'Saved secondary'] as $text) {
        $response->assertSee($text);
    }
    $response->assertSee('&lt;script&gt;bad()&lt;/script&gt;', false)->assertDontSee('<script>bad()</script>', false)
        ->assertSee('front-managed-hero__grid', false)->assertSee('front-managed-hero__frame', false)
        ->assertSee('<em class="front-managed-hero__highlight">', false)->assertSee(asset($hero->hero_image), false)
        ->assertDontSee('front-async-skeleton', false)->assertDontSee('data-home-hero-url', false);
    $hero->update(['description' => null, 'hero_image' => null, 'primary_button_url' => null]);
    $this->get(route('frontend.home'))->assertSuccessful()->assertDontSee('Saved description')->assertSee('Saved primary')
        ->assertDontSee('Saved image alt')->assertSee('Saved secondary');
});

test('Home Hero is the immediate section before Who I Help with saved links and accessible image fallback', function (): void {
    $hero = homeHeroSaved([
        'highlighted_text' => 'Saved highlight', 'heading_after_highlight' => 'Saved ending',
        'hero_image' => 'images/backend-images/home-hero/saved.png',
        'primary_button_text' => 'Explore saved programmes', 'primary_button_url' => '/courses',
        'secondary_button_text' => 'Our saved story', 'secondary_button_url' => '/taaruf',
    ]);
    $section = WhoIHelpSection::query()->create([
        'language' => 'en', 'eyebrow' => 'Saved Who I Help', 'heading' => 'Unchanged Who I Help',
        'description' => 'Saved description', 'bottom_message' => 'Saved message', 'is_active' => true,
    ]);
    $section->items()->create(['title' => 'Unchanged card', 'sort_order' => 0, 'is_active' => true]);
    $document = new DOMDocument;
    $document->loadHTML($this->get(route('frontend.home'))->assertSuccessful()->getContent(), LIBXML_NOERROR | LIBXML_NOWARNING);
    $xpath = new DOMXPath($document);

    expect($xpath->query('//section[contains(@class,"front-managed-hero")]')->length)->toBe(1)
        ->and($xpath->query('//section[contains(@class,"front-managed-hero")]/following-sibling::*[1][contains(@class,"front-who-i-help")]')->length)->toBe(1)
        ->and($xpath->query('//a[@href="/courses"]/span')->item(0)->textContent)->toBe('Explore saved programmes')
        ->and($xpath->query('//a[@href="/taaruf"]/span')->item(0)->textContent)->toBe('Our saved story')
        ->and($xpath->query('//img[@data-home-hero-image]')->item(0)->getAttribute('alt'))
        ->toBe(trim($hero->heading_before_highlight.' '.$hero->highlighted_text.' '.$hero->heading_after_highlight));
});

test('optional Hero fields leave no empty image description highlight or actions containers', function (): void {
    homeHeroSaved(['primary_button_url' => '/courses', 'secondary_button_url' => '/courses']);

    $this->get(route('frontend.home'))->assertSuccessful()
        ->assertSee('front-managed-hero__grid--text-only', false)
        ->assertDontSee('class="front-managed-hero__description"', false)
        ->assertDontSee('class="front-managed-hero__highlight"', false)
        ->assertDontSee('class="front-managed-hero__actions"', false)
        ->assertDontSee('data-home-hero-image', false)
        ->assertDontSee('Incomplete primary');
});

test('Hero text-only buttons cannot navigate and become links when URLs are saved', function (): void {
    $hero = homeHeroSaved(['primary_button_text' => 'Explore programmes', 'secondary_button_text' => 'Our story']);
    $document = new DOMDocument;
    $document->loadHTML($this->get(route('frontend.home'))->assertSuccessful()->getContent(), LIBXML_NOERROR | LIBXML_NOWARNING);
    $xpath = new DOMXPath($document);
    $scope = '//div[@class="front-managed-hero__actions"]';

    expect($xpath->query($scope.'/button[@type="button" and not(@onclick) and not(@formaction)]')->length)->toBe(2)
        ->and($xpath->query($scope.'/a')->length)->toBe(0)
        ->and($xpath->query($scope.'/button/span')->item(0)->textContent)->toBe('Explore programmes')
        ->and($xpath->query($scope.'/button/span')->item(1)->textContent)->toBe('Our story');

    $hero->update(['primary_button_url' => '/courses', 'secondary_button_url' => '/taaruf']);
    $document->loadHTML($this->get(route('frontend.home'))->assertSuccessful()->getContent(), LIBXML_NOERROR | LIBXML_NOWARNING);
    $xpath = new DOMXPath($document);

    expect($xpath->query($scope.'/button')->length)->toBe(0)
        ->and($xpath->query($scope.'/a[@href="/courses"]')->length)->toBe(1)
        ->and($xpath->query($scope.'/a[@href="/taaruf"]')->length)->toBe(1);
});

test('Admin saved Hero preview can be exported for isolated browser verification', function (): void {
    $this->actingAs($this->heroAdmin)->post(route('admin.home-hero.store'), homeHeroPayload([
        'eyebrow' => 'Hypnotherapy & Emotional Resilience Coaching',
        'heading_before_highlight' => 'Move from Surviving to', 'highlighted_text' => 'Thriving',
        'description' => 'You can be capable, achieve so much, and still feel overwhelmed inside. Thrive with Naila helps you understand yourself, navigate your emotions, and move towards greater clarity, self-trust, and emotional resilience.',
        'primary_button_text' => 'Explore Our Programmes', 'primary_button_url' => '/courses',
        'secondary_button_text' => 'Our Story', 'secondary_button_url' => '/taaruf',
        'hero_image' => UploadedFile::fake()->image('portrait.jpg', 960, 1200), 'image_alt' => 'Saved portrait alt text',
    ]))->assertRedirect(route('admin.home-hero.index'));
    $hero = HomeHeroSection::query()->sole();
    $this->get(route('frontend.home'))->assertSuccessful()->assertSee('Saved portrait alt text');

    if (getenv('HOME_HERO_PREVIEW') !== '1') {
        return;
    }

    $directory = storage_path('framework/testing/home-hero-ui');
    File::ensureDirectoryExists($directory);
    $imageData = 'data:image/jpeg;base64,'.base64_encode(file_get_contents(public_path($hero->hero_image)));
    $content = view('frontend.partials.home-hero', ['homeHero' => $hero])->render();
    $content = str_replace(asset($hero->hero_image), $imageData, $content);
    $manifest = json_decode(file_get_contents(base_path('public/build/manifest.json')), true, flags: JSON_THROW_ON_ERROR);
    $css = $manifest['resources/sass/front/front.scss']['file'];
    $js = $manifest['resources/js/front/front.js']['file'];
    $jsStyles = '';
    $styleEntries = ['resources/js/front/front.js'];
    while ($styleEntries !== []) {
        $entry = $manifest[array_pop($styleEntries)];
        foreach ($entry['css'] ?? [] as $stylesheet) {
            $jsStyles .= '<link rel="stylesheet" href="/build/'.$stylesheet.'">';
        }
        array_push($styleEntries, ...($entry['imports'] ?? []));
    }
    $html = '<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">'
        .'<link rel="stylesheet" href="/build/'.$css.'">'.$jsStyles.'</head><body class="front-body"><main>'.$content
        .'</main><script type="module" src="/build/'.$js.'"></script></body></html>';
    File::put($directory.'/hero.html', $html);
    $variants = ['portrait' => $imageData];
    foreach (['landscape' => [1200, 800], 'icon' => [48, 48], 'small' => [240, 320], 'opaque-png' => [900, 1200]] as $name => [$width, $height]) {
        $image = UploadedFile::fake()->image($name.'.png', $width, $height);
        $variants[$name] = 'data:image/png;base64,'.base64_encode(file_get_contents($image->getRealPath()));
    }
    $transparent = imagecreatetruecolor(800, 800);
    imagealphablending($transparent, false);
    imagesavealpha($transparent, true);
    imagefill($transparent, 0, 0, imagecolorallocatealpha($transparent, 0, 0, 0, 127));
    imagefilledellipse($transparent, 400, 400, 400, 400, imagecolorallocatealpha($transparent, 23, 60, 58, 0));
    imagepng($transparent, $directory.'/transparent.png');
    $variants['transparent'] = 'data:image/png;base64,'.base64_encode(file_get_contents($directory.'/transparent.png'));
    File::put($directory.'/variants.json', json_encode($variants, JSON_THROW_ON_ERROR));
});

test('consultancy courses contact and authentication pages still render without undefined variables', function (): void {
    $outputBufferLevel = ob_get_level();
    foreach (['front.consultancies.index', 'front.courses.index', 'front.contact', 'front.login', 'front.register'] as $routeName) {
        $this->get(route($routeName))->assertSuccessful();
        expect(ob_get_level())->toBe($outputBufferLevel, $routeName.' must close its view buffers');
    }
    $consultancy = Consultancy::query()->create([
        'language' => 'en', 'title' => 'Published consultancy', 'isActive' => true,
        'short_description' => 'Published consultancy summary',
        'status' => 'published', 'published_at' => now(), 'duration_type' => 'minutes',
        'duration_value' => 30, 'consultancy_medium' => 'zoom',
    ]);
    $this->get(route('front.consultancies.show', $consultancy))->assertSuccessful()->assertSee('Published consultancy');
});
