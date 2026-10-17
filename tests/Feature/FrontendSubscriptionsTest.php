<?php

use App\Models\Currency;
use App\Models\SubscriptionProduct;
use App\Models\SubscriptionType;
use App\Models\Video;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('renders active plans and memberships with English LTR system labels while preserving saved content', function () {
    $currency = Currency::query()->create([
        'name' => 'Pakistani Rupee',
        'code' => 'PKR',
        'symbol' => 'Rs',
        'isActive' => true,
    ]);
    $magazine = SubscriptionType::query()->create(['name' => 'Magazine', 'slug' => 'magazine', 'isActive' => true]);
    $articles = SubscriptionType::query()->create(['name' => 'Articles', 'slug' => 'articles', 'isActive' => true]);
    SubscriptionType::query()->create(['name' => 'Audio', 'slug' => 'audio', 'isActive' => true]);
    $podcast = SubscriptionType::query()->create(['name' => 'Podcast', 'slug' => 'podcast', 'isActive' => true]);
    $emptyType = SubscriptionType::query()->create(['name' => 'Unused Type', 'slug' => 'empty', 'isActive' => true]);
    $inactiveType = SubscriptionType::query()->create(['name' => 'Inactive Type', 'slug' => 'inactive', 'isActive' => false]);

    $plan = SubscriptionProduct::query()->create([
        'product_for' => SubscriptionProduct::FOR_PLAN,
        'name' => 'Monthly Plan',
        'currency_id' => $currency->id,
        'price' => 300,
        'discount_type' => 'percentage',
        'discount_value' => 10,
        'duration_value' => 1,
        'duration_unit' => 'month',
        'isActive' => true,
    ]);
    $plan->subscriptionTypes()->attach([$magazine->id, $articles->id, $podcast->id]);

    $hiddenPlan = SubscriptionProduct::query()->create([
        'product_for' => SubscriptionProduct::FOR_PLAN,
        'name' => 'Inactive Plan',
        'currency_id' => $currency->id,
        'price' => 100,
        'duration_value' => 1,
        'duration_unit' => 'day',
        'isActive' => false,
    ]);
    $hiddenPlan->subscriptionTypes()->attach($inactiveType);

    $membership = SubscriptionProduct::query()->create([
        'product_for' => SubscriptionProduct::FOR_MEMBERSHIP,
        'name' => 'Gold Membership',
        'currency_id' => $currency->id,
        'price' => 750,
        'discount_type' => 'fixed',
        'discount_value' => 50,
        'duration_value' => 3,
        'duration_unit' => 'month',
        'isActive' => true,
    ]);
    $membership->subscriptionTypes()->attach([$magazine->id, $articles->id]);

    $this->get(route('front.subscriptions'))
        ->assertOk()
        ->assertViewIs('frontend.subscriptions')
        ->assertSee('Home')
        ->assertSee('Subscriptions')
        ->assertSee('Plans')
        ->assertSee('Memberships')
        ->assertSee('Monthly Plan')
        ->assertSee('1 Month of full access')
        ->assertSee('PKR 300', false)
        ->assertSee('PKR 270', false)
        ->assertSee('10% Discount')
        ->assertSee('Duration: 1 Month')
        ->assertSee('Gold Membership')
        ->assertSee('Access to selected modules for 3 Months')
        ->assertSee('PKR 750', false)
        ->assertSee('PKR 700', false)
        ->assertSee('PKR 50 Discount')
        ->assertSee('Included Modules')
        ->assertSee('Magazine Plan')
        ->assertSee('Magazine')
        ->assertSee('Podcast')
        ->assertSee('Subscribe Now')
        ->assertDontSee('plan-type-'.$emptyType->id.'-tab', false)
        ->assertDontSee('Inactive Plan')
        ->assertDontSee('Inactive Type');
});

it('renders English empty states when no active subscription products exist', function () {
    $this->get(route('front.subscriptions'))
        ->assertOk()
        ->assertSee('No subscription plans are available.')
        ->assertSee('No memberships are available.');
});

it('renders only active published videos assigned to each subscription plan', function () {
    $currency = Currency::query()->create([
        'name' => 'Pakistani Rupee',
        'code' => 'PKR',
        'symbol' => 'Rs',
        'isActive' => true,
    ]);
    $type = SubscriptionType::query()->create(['name' => 'Video', 'slug' => 'video', 'isActive' => true]);
    $plan = SubscriptionProduct::query()->create([
        'product_for' => SubscriptionProduct::FOR_PLAN,
        'name' => 'Video Plan',
        'currency_id' => $currency->id,
        'price' => 300,
        'duration_value' => 1,
        'duration_unit' => 'month',
        'isActive' => true,
    ]);
    $plan->subscriptionTypes()->attach($type);

    $activeVideo = Video::query()->create([
        'title' => 'Active Included Video',
        'video_link' => 'https://example.com/active-video',
        'short_description' => 'An active video.',
        'is_active' => true,
        'status' => Video::STATUS_PUBLISHED,
    ]);
    $inactiveVideo = Video::query()->create([
        'title' => 'Inactive Included Video',
        'video_link' => 'https://example.com/inactive-video',
        'short_description' => 'An inactive video.',
        'is_active' => false,
        'status' => Video::STATUS_PUBLISHED,
    ]);
    $draftVideo = Video::query()->create([
        'title' => 'Draft Included Video',
        'video_link' => 'https://example.com/draft-video',
        'short_description' => 'A draft video.',
        'is_active' => true,
        'status' => Video::STATUS_DRAFT,
    ]);
    $plan->videos()->attach([$activeVideo->id, $inactiveVideo->id, $draftVideo->id]);

    $this->get(route('front.subscriptions'))
        ->assertOk()
        ->assertSee('Included Videos')
        ->assertSee('Active Included Video')
        ->assertDontSee('Inactive Included Video')
        ->assertDontSee('Draft Included Video');
});
