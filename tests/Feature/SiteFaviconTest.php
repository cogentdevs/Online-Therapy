<?php

use App\Models\GeneralSetting;
use Illuminate\Events\Dispatcher;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\View;

beforeEach(function (): void {
    View::setDispatcher(new Dispatcher(app()));
    DB::shouldReceive('connection')->never();
});

test('site favicon uses the saved image format and a file version without database access', function (string $path): void {
    $settings = new GeneralSetting(['favicon' => $path]);
    $html = Blade::render('<x-site-favicon :settings="$settings" />', compact('settings'));
    expect($html)->toContain('href="'.asset($path).'?v='.filemtime(public_path($path)).'"')
        ->not->toContain('type="image/png"');
})->with([
    'PNG upload' => 'images/backend-images/logo/Thrive-with-Naila-Wellness-Emblem-removebg-preview.png',
    'ICO fallback asset' => 'favicon.ico',
]);

test('site favicon falls back for missing settings empty or deleted uploads', function (string $state): void {
    $settings = match ($state) {
        'no settings' => null,
        'empty' => new GeneralSetting(['favicon' => '']),
        default => new GeneralSetting(['favicon' => 'images/missing-favicon-upload.png']),
    };
    expect(Blade::render('<x-site-favicon :settings="$settings" />', compact('settings')))
        ->toContain('href="'.asset('favicon.ico').'?v='.filemtime(public_path('favicon.ico')).'"');
})->with(['no settings', 'empty', 'deleted upload']);

test('all frontend Admin and authentication layouts use the saved favicon component', function (string $path): void {
    $source = file_get_contents(resource_path('views/'.$path));
    expect($source)->toContain('<x-site-favicon :settings="$generalSetting ?? null" />')
        ->not->toContain('?v=20261014');
})->with([
    'layouts/frontLayout/front-design.blade.php',
    'layouts/adminLayout/admin-design.blade.php',
    'admin/login.blade.php', 'admin/forgot-password.blade.php', 'admin/reset-password.blade.php',
]);

test('root favicon is a valid ICO with an embedded decodable square PNG', function (): void {
    $data = file_get_contents(public_path('favicon.ico'));
    expect(substr($data, 0, 6))->toBe("\x00\x00\x01\x00\x01\x00");
    $offset = unpack('V', substr($data, 18, 4))[1];
    $image = getimagesizefromstring(substr($data, $offset));
    expect($image[0])->toBe(32)->and($image[1])->toBe(32)->and($image['mime'])->toBe('image/png');
});
