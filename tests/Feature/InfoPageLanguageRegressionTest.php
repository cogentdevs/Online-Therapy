<?php

use App\Http\Controllers\Admin\InfoPageController;
use App\Http\Requests\Admin\UpdateInfoPageRequest;
use App\Models\InfoPage;
use App\Models\Language;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpKernel\Exception\HttpException;

beforeEach(function (): void {
    expect(app()->environment('testing'))->toBeTrue()
        ->and(config('database.default'))->toBe('sqlite')
        ->and(config('database.connections.sqlite.database'))->toBe(':memory:')
        ->and(config('database.connections.sqlite.url'))->toBeEmpty();
    config(['database.connections' => ['sqlite' => config('database.connections.sqlite')]]);
    expect(DB::connection()->getDatabaseName())->toBe(':memory:')
        ->and(DB::connection()->getPdo()->getAttribute(PDO::ATTR_DRIVER_NAME))->toBe('sqlite');
    config(['content_language.code' => 'en']);
    Schema::create('languages', function (Blueprint $table): void {
        $table->id();
        $table->string('code');
        $table->boolean('is_active')->default(true);
        $table->timestamps();
    });
    Schema::create('info_pages', function (Blueprint $table): void {
        $table->id();
        $table->string('language');
        $table->string('page');
        $table->string('title');
        $table->text('description')->nullable();
        $table->timestamps();
    });
    Language::query()->create(['code' => 'en', 'is_active' => true]);
});

afterEach(function (): void {
    DB::disconnect('sqlite');
});

test('fixed page editor resolves the active configured Language model', function (string $page): void {
    $definition = config('info_pages.pages.'.$page);
    Gate::shouldReceive('authorize')->once()->with($definition['permission'].'.view')->andReturnNull();
    $record = InfoPage::query()->create(['language' => 'en', 'page' => $definition['key'], 'title' => $definition['titles']['en'], 'description' => '<p>Saved content</p>']);
    $view = app(InfoPageController::class)->edit($page);
    expect($view->name())->toBe('admin.info-page.edit-info-page')
        ->and($view->getData()['infoPage']->id)->toBe($record->id)
        ->and($view->getData()['infoPage']->description)->toBe('<p>Saved content</p>')
        ->and($view->getData()['language'])->toBe('en');
})->with(['privacy-policy', 'terms-and-conditions', 'disclaimer']);

test('fixed page update resolves Language and changes only the requested page', function (string $page): void {
    $definition = config('info_pages.pages.'.$page);
    Gate::shouldReceive('authorize')->once()->with($definition['permission'].'.edit')->andReturnNull();
    $other = InfoPage::query()->create(['language' => 'en', 'page' => 'unrelated', 'title' => 'Preserved', 'description' => 'Unchanged']);
    $request = UpdateInfoPageRequest::create('/', 'PUT', ['language' => 'en', 'description' => '<p>Updated content</p>']);
    $request->setContainer(app());
    $request->setValidator(Validator::make($request->all(), $request->rules()));
    $response = app(InfoPageController::class)->update($request, $page);
    expect($response->getTargetUrl())->toContain('/admin/info-pages/'.$page.'/edit')
        ->and(InfoPage::query()->forPage($definition['key'], 'en')->sole()->description)->toBe('<p>Updated content</p>')
        ->and($other->fresh()->description)->toBe('Unchanged');
})->with(['privacy-policy', 'terms-and-conditions', 'disclaimer']);

test('fixed editors preserve rejection of inactive configured languages', function (string $page): void {
    $definition = config('info_pages.pages.'.$page);
    Gate::shouldReceive('authorize')->once()->with($definition['permission'].'.view')->andReturnNull();
    Language::query()->where('code', 'en')->update(['is_active' => false]);
    try {
        app(InfoPageController::class)->edit($page);
        $this->fail('Inactive languages must not open an editor.');
    } catch (HttpException $exception) {
        expect($exception->getStatusCode())->toBe(404);
    }
})->with(['privacy-policy', 'terms-and-conditions', 'disclaimer']);
