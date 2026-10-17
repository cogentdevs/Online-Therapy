<?php

use App\Models\ThriveFrameworkSection;
use App\Models\User;
use App\Models\WorkTogetherCard;
use App\Models\WorkTogetherSection;
use App\Services\SiteVisitService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Database\QueryException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
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
        'WorkTogetherSection', 'WorkTogetherCard', 'Consultancy', 'FaqCategory', 'Faq', 'About', 'Author', 'AuthorGeneralSetting',
    ] as $modelName) {
        $class = 'App\\Models\\'.$modelName;
        frontendWorkTogetherFixtureTable(new $class);
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

function frontendWorkTogetherFixtureTable(Model $model): void
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

function frontendWorkSaved(array $overrides = []): WorkTogetherSection
{
    return WorkTogetherSection::query()->create(array_replace([
        'language' => 'en', 'eyebrow' => 'Saved ways to work', 'heading' => 'Saved Together heading',
        'description' => 'Saved section introduction', 'bottom_message' => 'Saved final message',
        'bottom_button_text' => 'Start a conversation', 'bottom_button_url' => '/contact',
    ], $overrides));
}

function frontendWorkCard(WorkTogetherSection $section, array $overrides = []): WorkTogetherCard
{
    return $section->cards()->create(array_replace([
        'title' => 'Saved service', 'description' => 'Saved card description', 'additional_details' => 'Saved additional details',
        'button_text' => 'Explore service', 'button_url' => '/consultancy',
    ], $overrides));
}

function frontendWorkXPath(string $html): DOMXPath
{
    $document = new DOMDocument;
    $document->loadHTML('<?xml encoding="UTF-8">'.$html, LIBXML_NOERROR | LIBXML_NOWARNING);

    return new DOMXPath($document);
}

test('work together frontend appears once immediately after framework with saved content', function (): void {
    ThriveFrameworkSection::query()->create(['language' => 'en', 'eyebrow' => 'Existing framework', 'heading' => 'Unchanged Framework']);
    $section = frontendWorkSaved();
    frontendWorkCard($section);
    $response = $this->get(route('frontend.home'))->assertSuccessful()->assertSee('Saved ways to work')->assertSee('Saved Together heading')
        ->assertSee('Saved section introduction')->assertSee('Saved additional details')->assertSee('Saved final message');
    $xpath = frontendWorkXPath($response->getContent());
    expect($xpath->query('//section[@class="front-thrive-framework"]/following-sibling::*[1][@class="front-work-together"]')->length)->toBe(1)
        ->and($xpath->query('//section[@class="front-work-together"]')->length)->toBe(1)
        ->and($xpath->query('//a[@class="front-work-together__link"]')->item(0)->getAttribute('href'))->toBe('/consultancy')
        ->and($xpath->query('//a[@class="front-work-together__button"]')->item(0)->getAttribute('href'))->toBe('/contact');
});

test('work together frontend hides unavailable or inactive sections', function (string $state): void {
    if ($state === 'parent missing') {
        Schema::drop('work_together_sections');
    } elseif ($state === 'cards missing') {
        Schema::drop('work_together_cards');
    } elseif ($state !== 'no record') {
        $section = frontendWorkSaved($state === 'inactive' ? ['is_active' => false] : ['language' => 'fr']);
        frontendWorkCard($section);
    }
    $this->get(route('frontend.home'))->assertSuccessful()->assertDontSee('front-work-together', false);
})->with(['no record', 'inactive', 'other language', 'parent missing', 'cards missing']);

test('work together frontend orders active cards numbers visible rows and respects full width layout', function (): void {
    $section = frontendWorkSaved();
    frontendWorkCard($section, ['title' => 'Second visible', 'sort_order' => 2, 'layout_type' => 'full_width']);
    frontendWorkCard($section, ['title' => 'First visible', 'sort_order' => 1]);
    frontendWorkCard($section, ['title' => 'Third visible', 'sort_order' => 2]);
    frontendWorkCard($section, ['title' => 'Hidden inactive', 'is_active' => false]);
    frontendWorkCard($section, ['title' => 'Deleted card'])->delete();
    $response = $this->get(route('frontend.home'))->assertSuccessful()->assertSeeInOrder(['First visible', 'Second visible', 'Third visible'])
        ->assertDontSee('Hidden inactive')->assertDontSee('Deleted card');
    $xpath = frontendWorkXPath($response->getContent());
    $numbers = $xpath->query('//span[@class="front-work-together__number"]');
    expect(array_map(fn (DOMNode $node): string => $node->textContent, iterator_to_array($numbers)))->toBe(['01', '02', '03'])
        ->and($xpath->query('//article[contains(@class,"front-work-together__card--full")]')->length)->toBe(1);
});

test('work together frontend omits optional content and incomplete CTA wrappers', function (): void {
    $section = frontendWorkSaved(['description' => null, 'bottom_message' => null, 'bottom_button_url' => null]);
    frontendWorkCard($section, ['description' => null, 'additional_details' => null, 'button_url' => null]);
    frontendWorkCard($section, ['title' => 'URL only', 'description' => null, 'additional_details' => null, 'button_text' => null]);
    $xpath = frontendWorkXPath($this->get(route('frontend.home'))->assertSuccessful()->getContent());
    foreach (['description', 'body', 'card-description', 'details', 'link', 'bottom', 'button'] as $suffix) {
        expect($xpath->query('//*[@class="front-work-together__'.$suffix.'"]')->length)->toBe(0);
    }
    $section->update(['bottom_message' => 'Message without button']);
    $this->get(route('frontend.home'))->assertSee('Message without button')->assertDontSee('class="front-work-together__button"', false);
});

test('work together frontend has no empty grid or wholly empty section and escapes text', function (): void {
    $section = frontendWorkSaved(['eyebrow' => null, 'heading' => null, 'description' => null, 'bottom_message' => null, 'bottom_button_text' => null]);
    $this->get(route('frontend.home'))->assertSuccessful()->assertDontSee('front-work-together', false);
    $section->update(['heading' => '<script>alert("unsafe")</script>']);
    $this->get(route('frontend.home'))->assertSuccessful()->assertSee(e($section->heading), false)->assertDontSee($section->heading, false)
        ->assertDontSee('class="front-work-together__grid"', false);
});

test('work together frontend uses saved grid columns with exactly the saved card count', function (int $columns, int $count): void {
    $section = frontendWorkSaved(['cards_per_row' => $columns]);
    foreach (range(1, $count) as $position) {
        frontendWorkCard($section, ['title' => 'Dynamic card '.$position, 'sort_order' => $position]);
    }
    $response = $this->get(route('frontend.home'))->assertSuccessful()->assertSee('--work-columns: '.$columns.';', false);
    expect(frontendWorkXPath($response->getContent())->query('//article[@class="front-work-together__card"]')->length)->toBe($count);
})->with([3, 4])->with([1, 3, 6, 8]);

test('work together frontend query eager loads only active configured language cards', function (): void {
    $section = frontendWorkSaved();
    frontendWorkCard($section);
    frontendWorkSaved(['language' => 'fr', 'heading' => 'French hidden heading']);
    $queries = [];
    DB::listen(function (QueryExecuted $query) use (&$queries): void {
        if (str_contains($query->sql, 'from "work_together_cards"')) {
            $queries[] = $query->sql;
        }
    });
    $response = $this->get(route('frontend.home'))->assertSuccessful()->assertDontSee('French hidden heading');
    expect($response->viewData('workTogether')->relationLoaded('cards'))->toBeTrue()->and($queries)->toHaveCount(1);
    config(['content_language.code' => 'fr']);
    $this->get(route('frontend.home'))->assertSuccessful()->assertSee('French hidden heading')->assertDontSee('Saved Together heading');
});

test('work together unrelated query errors are not swallowed', function (): void {
    frontendWorkCard(frontendWorkSaved());
    Schema::table('work_together_cards', fn (Blueprint $table) => $table->renameColumn('section_id', 'unavailable_section'));
    $this->withoutExceptionHandling();
    expect(fn () => $this->get(route('frontend.home')))->toThrow(QueryException::class);
});

test('work together browser fixture exports only isolated saved records when requested', function (): void {
    $section = frontendWorkSaved();
    foreach (range(1, 7) as $position) {
        frontendWorkCard($section, ['title' => 'Saved service '.$position, 'sort_order' => $position,
            'description' => str_repeat('Explore personalised support and practical steps for emotional wellbeing. ', $position === 2 ? 6 : 2),
            'layout_type' => $position === 7 ? 'full_width' : 'standard']);
    }
    $response = $this->get(route('frontend.home'))->assertSuccessful();
    if (getenv('WORK_TOGETHER_PREVIEW') !== '1') {
        return;
    }
    $manifest = json_decode(file_get_contents(public_path('build/manifest.json')), true, flags: JSON_THROW_ON_ERROR);
    $css = $manifest['resources/sass/front/front.scss']['file'];
    $html = '<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">'
        .'<link rel="stylesheet" href="/build/'.$css.'"></head><body class="front-body"><main>'
        .view('frontend.partials.work-together', ['workTogether' => $response->viewData('workTogether')])->render().'</main></body></html>';
    $directory = storage_path('framework/testing/work-together-ui');
    File::ensureDirectoryExists($directory);
    File::put($directory.'/work.html', $html);
});
