<?php

use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

test('public Author navigation and routes are temporarily hidden', function (): void {
    $this->get('/')
        ->assertSuccessful()
        ->assertDontSee('>Authors<', false);

    $articleView = file_get_contents(resource_path('views/frontend/mazmoon-detail.blade.php'));
    $tazaShumaraView = file_get_contents(resource_path('views/frontend/taza-shumara.blade.php'));
    $magazineDetailView = file_get_contents(resource_path('views/frontend/shumara-detail.blade.php'));

    expect($articleView)->not->toContain("route('front.mazmoon-nigaar.detail'")
        ->and($tazaShumaraView)->not->toContain("route('front.mazmoon-nigaar.detail'")
        ->and($magazineDetailView)->not->toContain("route('front.mazmoon-nigaar.detail'");

    $this->get(route('front.mazmoon-nigaar'))->assertNotFound();
    $this->get(route('front.mazmoon-nigaar.detail', ['id' => 1, 'slug' => 'author']))->assertNotFound();
});
