<?php

use App\Models\WhoIHelpSection;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

beforeEach(function (): void {
    $this->withoutVite();
});

function frontendWhoIHelpSection(array $attributes = [], array $items = []): WhoIHelpSection
{
    $section = WhoIHelpSection::query()->create(array_replace([
        'language' => 'en',
        'eyebrow' => 'WHO I HELP',
        'heading' => 'Saved Admin Heading',
        'description' => 'Saved Admin Description',
        'bottom_message' => 'Saved Admin Bottom Message',
        'is_active' => true,
    ], $attributes));

    foreach ($items as $sortOrder => $item) {
        $section->items()->create(array_replace([
            'title' => 'Saved card '.($sortOrder + 1),
            'sort_order' => $sortOrder,
            'is_active' => true,
        ], $item));
    }

    return $section->fresh('items');
}

test('homepage hides Who I Help entirely when no saved section exists', function (): void {
    $response = $this->get(route('frontend.home'))->assertSuccessful();

    $response->assertDontSee('front-who-i-help', false)
        ->assertDontSee("When Life Feels Overwhelming, It's Time to Understand Yourself.");
});

test('homepage hides Who I Help when the saved section is inactive or has no active cards', function (): void {
    frontendWhoIHelpSection(['is_active' => false], [['title' => 'Hidden by section status']]);

    $this->get(route('frontend.home'))
        ->assertSuccessful()
        ->assertDontSee('front-who-i-help', false)
        ->assertDontSee('Hidden by section status');

    WhoIHelpSection::query()->delete();
    frontendWhoIHelpSection(['is_active' => true]);

    $this->get(route('frontend.home'))
        ->assertSuccessful()
        ->assertDontSee('front-who-i-help', false);

    WhoIHelpSection::query()->delete();
    frontendWhoIHelpSection(['is_active' => true], [['title' => 'Inactive card', 'is_active' => false]]);

    $this->get(route('frontend.home'))
        ->assertSuccessful()
        ->assertDontSee('front-who-i-help', false)
        ->assertDontSee('Inactive card');
});

test('homepage renders only active saved cards in their saved order immediately before the footer', function (): void {
    frontendWhoIHelpSection([
        'eyebrow' => 'Saved eyebrow',
        'heading' => 'Saved heading',
        'description' => 'Saved description',
        'bottom_message' => 'Saved bottom message',
    ], [
        ['title' => 'Second by insertion but first by order', 'sort_order' => 20],
        ['title' => 'Inactive card must not render', 'sort_order' => 10, 'is_active' => false],
        ['title' => 'First by sort order', 'sort_order' => 5],
    ]);

    $html = $this->get(route('frontend.home'))->assertSuccessful()->getContent();

    expect($html)->toContain('front-who-i-help')
        ->and($html)->toContain('Saved eyebrow')
        ->and($html)->toContain('Saved heading')
        ->and($html)->toContain('Saved description')
        ->and($html)->toContain('Saved bottom message')
        ->and($html)->toContain('First by sort order')
        ->and($html)->toContain('Second by insertion but first by order')
        ->and($html)->not->toContain('Inactive card must not render')
        ->and(strpos($html, 'First by sort order'))->toBeLessThan(strpos($html, 'Second by insertion but first by order'))
        ->and(strpos($html, 'front-who-i-help'))->toBeLessThan(strpos($html, '<footer class="front-footer">'));
});

test('homepage reflects saved updates and hides optional empty values without frontend defaults', function (): void {
    $section = frontendWhoIHelpSection([
        'eyebrow' => '',
        'heading' => 'Initial saved heading',
        'description' => '',
        'bottom_message' => '',
    ], [['title' => 'Initial saved card']]);

    $section->update([
        'heading' => 'Updated Admin Heading',
        'description' => 'Updated Admin Description',
    ]);
    $section->items->first()->update(['title' => 'Updated Admin Card']);

    $this->get(route('frontend.home'))
        ->assertSuccessful()
        ->assertSee('Updated Admin Heading')
        ->assertSee('Updated Admin Description')
        ->assertSee('Updated Admin Card')
        ->assertDontSee('Initial saved heading')
        ->assertDontSee('Initial saved card')
        ->assertDontSee('WHO I HELP', false)
        ->assertDontSee('front-who-i-help__eyebrow', false)
        ->assertDontSee('front-who-i-help__bottom-message', false);
});

test('homepage escapes Admin plain text and hides the section after all saved cards are removed', function (): void {
    $section = frontendWhoIHelpSection([
        'heading' => '<script>alert(1)</script>',
    ], [['title' => '<img src=x onerror=alert(1)>']]);

    $response = $this->get(route('frontend.home'))->assertSuccessful();

    $response->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false)
        ->assertSee('&lt;img src=x onerror=alert(1)&gt;', false)
        ->assertDontSee('<script>alert(1)</script>', false)
        ->assertDontSee('<img src=x onerror=alert(1)>', false);

    $section->items()->delete();

    $this->get(route('frontend.home'))
        ->assertSuccessful()
        ->assertDontSee('front-who-i-help', false);
});

test('Who I Help is server rendered and does not add a duplicate asynchronous skeleton or loading delay', function (): void {
    frontendWhoIHelpSection(items: [['title' => 'Server rendered card']]);

    $html = $this->get(route('frontend.home'))->assertSuccessful()->getContent();

    expect($html)->toContain('Server rendered card')
        ->and($html)->not->toContain('front-who-i-help__skeleton')
        ->and($html)->not->toContain('front-async-skeleton')
        ->and($html)->not->toContain('data-who-i-help-url')
        ->and($html)->not->toContain('data-who-i-help-loading');
});
