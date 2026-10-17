<?php

use App\Models\GeneralSetting;
use App\Models\HomeHeroSection;
use App\Models\Slider;
use App\Models\ThriveFrameworkSection;
use App\Models\User;
use App\Models\WhoIHelpSection;
use App\Models\WorkTogetherSection;
use App\Services\SiteVisitService;
use Illuminate\Database\Eloquent\Model;
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
    config(['app.url' => 'http://localhost']);
    Http::preventStrayRequests();
    $this->mock(SiteVisitService::class)->shouldReceive('record')->zeroOrMoreTimes();
    foreach ([
        'User', 'ActivityLog', 'GeneralSetting', 'Language', 'Category', 'MetaTag', 'Ad', 'HomeCard',
        'HomeCardTitlePosition', 'HomePageSectionHeading', 'Article', 'Magazine', 'Course', 'TazaShumara',
        'Banner', 'Slider', 'HomeHeroSection', 'WhoIHelpSection', 'WhoIHelpItem', 'SiteVisit', 'SearchContent',
        'WorkTogetherSection', 'WorkTogetherCard', 'Consultancy', 'FaqCategory', 'Faq', 'About', 'Author', 'AuthorGeneralSetting',
    ] as $modelName) {
        $class = 'App\\Models\\'.$modelName;
        responsiveOverflowFixtureTable(new $class);
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

function responsiveOverflowFixtureTable(Model $model): void
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

test('responsive overflow fixtures render real homepage registration and Admin editor safely', function (): void {
    GeneralSetting::query()->create(['app_name' => 'Thrive with Naila', 'logo' => 'images/overflow-logo.svg', 'footer_logo' => 'images/overflow-logo.svg']);
    foreach (range(1, 2) as $number) {
        Slider::query()->create(['language' => 'en', 'main_heading' => 'Saved slide '.$number, 'image' => 'images/overflow-slide.svg', 'isActive' => true]);
    }
    $hero = HomeHeroSection::query()->create(['language' => 'en', 'eyebrow' => 'Saved eyebrow', 'heading_before_highlight' => 'Saved Hero heading', 'hero_image' => 'images/overflow-portrait.svg', 'is_active' => true]);
    $framework = ThriveFrameworkSection::query()->create(['language' => 'en', 'eyebrow' => 'Saved approach', 'heading' => 'Saved Framework']);
    foreach (range(1, 6) as $number) {
        $framework->stages()->create(['letter' => 'T', 'title' => 'Saved stage '.$number, 'description' => 'Saved stage description', 'sort_order' => $number]);
    }
    $section = WorkTogetherSection::query()->create(['language' => 'en', 'eyebrow' => 'Saved services', 'heading' => 'Saved Work Together']);
    foreach (range(1, 7) as $number) {
        $section->cards()->create(['title' => 'Saved service '.$number, 'description' => 'Saved card description', 'sort_order' => $number, 'layout_type' => $number === 7 ? 'full_width' : 'standard']);
    }
    $who = WhoIHelpSection::query()->create(['language' => 'en', 'eyebrow' => 'Saved Who', 'heading' => 'Saved Who I Help', 'is_active' => true]);
    $who->items()->create(['title' => 'Saved help card', 'is_active' => true]);
    $pages = ['home' => $this->get(route('frontend.home'))->assertSuccessful()->getContent(),
        'register' => $this->get(route('front.register'))->assertSuccessful()->getContent()];
    $admin = User::factory()->create(['is_active' => true]);
    $admin->assignRole(Role::findOrCreate('super-admin', 'web'));
    $this->actingAs($admin);
    $pages['admin-hero'] = $this->get(route('admin.home-hero.edit', $hero))->assertSuccessful()->getContent();
    $pages['admin-table'] = $this->get(route('admin.home-hero.index'))->assertSuccessful()->getContent();
    if (getenv('OVERFLOW_PREVIEW') !== '1') {
        return;
    }
    $manifest = json_decode(file_get_contents(public_path('build/manifest.json')), true, flags: JSON_THROW_ON_ERROR);
    $directory = storage_path('framework/testing/responsive-overflow');
    File::ensureDirectoryExists($directory);
    foreach ($pages as $name => $html) {
        $adminPage = str_starts_with($name, 'admin');
        $css = $manifest[$adminPage ? 'resources/sass/admin/admin.scss' : 'resources/sass/front/front.scss']['file'];
        $js = $manifest[$adminPage ? 'resources/js/admin/admin.js' : 'resources/js/front/front.js']['file'];
        $html = str_replace('</head>', '<link rel="stylesheet" href="/build/'.$css.'"><script type="module" src="/build/'.$js.'"></script></head>', $html);
        File::put($directory.'/'.$name.'.html', $html);
    }
});
