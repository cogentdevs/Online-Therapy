@extends('layouts.frontLayout.front-design')

@section('title', 'Subscriptions')
@section('meta_description', 'Choose a subscription plan or membership that suits your needs')

@section('content')
    <main class="subscription-page" dir="ltr">
        <div class="container-fluid subscription-page__container">
            <nav class="front-taza-breadcrumb front-ui" aria-label="Breadcrumb">
                <a href="{{ route('frontend.home') }}"><i class="fa-solid fa-house" aria-hidden="true"></i> Home</a>
                <span aria-hidden="true">/</span>
                <span aria-current="page">Subscriptions</span>
            </nav>

            <header class="subscription-page__heading">
                <h1>Subscriptions</h1>
                <span aria-hidden="true"></span>
                <p>Choose a subscription plan or membership that suits your needs and unlock access to premium content.</p>
            </header>

            @if (session('warning'))
                <div class="alert alert-warning" role="alert">{{ session('warning') }}</div>
            @endif
            @if (session('success'))
                <div class="alert alert-success" role="alert">{{ session('success') }}</div>
            @endif

            <ul class="nav subscription-main-tabs" id="subscription-tabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="plans-tab" data-bs-toggle="tab" data-bs-target="#subscription-plans"
                        type="button" role="tab" aria-controls="subscription-plans" aria-selected="true">
                        <i class="fa-regular fa-clipboard" aria-hidden="true"></i> Plans
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="memberships-tab" data-bs-toggle="tab"
                        data-bs-target="#subscription-memberships" type="button" role="tab"
                        aria-controls="subscription-memberships" aria-selected="false">
                        <i class="fa-solid fa-users" aria-hidden="true"></i> Memberships
                    </button>
                </li>
            </ul>

            <div class="tab-content">
                <section class="tab-pane fade show active" id="subscription-plans" role="tabpanel"
                    aria-labelledby="plans-tab" tabindex="0">
                    @if ($planGroups->isNotEmpty())
                        <div class="subscription-type-tabs-wrap">
                            <ul class="nav subscription-type-tabs" role="tablist">
                                @foreach ($planGroups as $group)
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link @if ($loop->first) active @endif"
                                            id="plan-type-{{ $group['type']->id }}-tab" data-bs-toggle="tab"
                                            data-bs-target="#plan-type-{{ $group['type']->id }}" type="button"
                                            role="tab" aria-controls="plan-type-{{ $group['type']->id }}"
                                            aria-selected="{{ $loop->first ? 'true' : 'false' }}">
                                            <span dir="auto">{{ $group['label'] }}</span>
                                        </button>
                                    </li>
                                @endforeach
                            </ul>
                        </div>

                        <div class="tab-content subscription-plan-groups">
                            @foreach ($planGroups as $group)
                                <div class="tab-pane fade @if ($loop->first) show active @endif"
                                    id="plan-type-{{ $group['type']->id }}" role="tabpanel"
                                    aria-labelledby="plan-type-{{ $group['type']->id }}-tab" tabindex="0">
                                    <div class="row g-4 justify-content-center">
                                        @foreach ($group['plans'] as $plan)
                                            <div class="col-12 col-md-6 col-xl-4">
                                                <article class="subscription-card subscription-plan-card">
                                                    <h2 dir="auto">{{ $plan->name }}</h2>
                                                    @if ($plan->subscription_page_description)
                                                        <p class="subscription-plan-card__description">
                                                            {{ $plan->subscription_page_description }}</p>
                                                    @endif
                                                    <div class="subscription-card__icon"><i
                                                            class="fa-regular fa-calendar-check" aria-hidden="true"></i>
                                                    </div>
                                                    @if ($plan->frontend_has_discount)
                                                        <div class="subscription-card__discount">
                                                            <span
                                                                class="subscription-card__discount-badge">{{ $plan->subscription_page_discount_label }}</span>
                                                            <del dir="ltr">{{ $plan->frontend_currency }} {{ $plan->frontend_price }}</del>
                                                        </div>
                                                    @endif
                                                    <div class="subscription-card__price" dir="ltr">{{ $plan->frontend_currency }} {{ $plan->frontend_has_discount ? $plan->frontend_discounted_price : $plan->frontend_price }}</div>
                                                    <p class="subscription-card__duration">Duration: {{ $plan->subscription_page_duration }}</p>
                                                    @if ($plan->videos->isNotEmpty())
                                                        <section class="subscription-plan-card__videos" aria-label="Included Videos">
                                                            <h3>Included Videos</h3>
                                                            <ul>
                                                                @foreach ($plan->videos as $video)
                                                                    <li>
                                                                        <i class="fa-solid fa-check" aria-hidden="true"></i>
                                                                        <span dir="auto">{{ $video->title }}</span>
                                                                    </li>
                                                                @endforeach
                                                            </ul>
                                                        </section>
                                                    @endif
                                                    <div class="subscription-plan-card__divider" aria-hidden="true"></div>
                                                    @auth('web')
                                                        @if ($plan->frontend_purchase_allowed)
                                                            <a class="subscription-card__action" href="{{ route('front.subscriptions.checkout', $plan) }}">
                                                                {{ $plan->frontend_purchase_label }} <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
                                                            </a>
                                                        @else
                                                            <button class="subscription-card__action" type="button" disabled aria-disabled="true">
                                                                {{ $plan->frontend_purchase_label }} <i class="fa-solid fa-lock" aria-hidden="true"></i>
                                                            </button>
                                                        @endif
                                                        @if ($plan->frontend_purchase_hint)
                                                            <small class="subscription-card__action-hint">{{ $plan->frontend_purchase_hint }}</small>
                                                        @endif
                                                    @else
                                                        <button class="subscription-card__action" type="button" data-subscription-guest-cta
                                                            data-product-id="{{ $plan->getKey() }}" data-intent-url="{{ route('front.subscriptions.intent', $plan) }}">
                                                            Subscribe Now <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
                                                        </button>
                                                    @endauth
                                                </article>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="subscription-empty"><i class="fa-regular fa-clipboard" aria-hidden="true"></i>
                            <p>No subscription plans are available.</p>
                        </div>
                    @endif
                </section>

                <section class="tab-pane fade" id="subscription-memberships" role="tabpanel"
                    aria-labelledby="memberships-tab" tabindex="0">
                    @if ($memberships->isNotEmpty())
                        <div class="row g-4 justify-content-center subscription-membership-grid">
                            @foreach ($memberships as $membership)
                                <div class="col-12 col-md-6 col-xl-4">
                                    <article class="subscription-card subscription-membership-card">
                                        <div class="subscription-card__icon subscription-membership-icon"><i
                                                class="fa-solid fa-crown" aria-hidden="true"></i></div>
                                        <h2 dir="auto">{{ $membership->name }}</h2>
                                        @if ($membership->subscription_page_membership_description)
                                            <p class="subscription-membership-card__description">
                                                {{ $membership->subscription_page_membership_description }}</p>
                                        @endif
                                        @if ($membership->frontend_has_discount)
                                            <div class="subscription-card__discount">
                                                <span
                                                    class="subscription-card__discount-badge">{{ $membership->subscription_page_discount_label }}</span>
                                                <del dir="ltr">{{ $membership->frontend_currency }} {{ $membership->frontend_price }}</del>
                                            </div>
                                        @endif
                                        <div class="subscription-card__price" dir="ltr">{{ $membership->frontend_currency }} {{ $membership->frontend_has_discount ? $membership->frontend_discounted_price : $membership->frontend_price }}</div>
                                        <p class="subscription-card__duration">Duration: {{ $membership->subscription_page_duration }}</p>

                                        @if ($membershipTypes->isNotEmpty())
                                            <div class="subscription-membership-card__divider"></div>
                                            <h3>Included Modules</h3>
                                            <ul
                                                class="subscription-membership-card__features subscription-membership-modules">
                                                @foreach ($membershipTypes as $type)
                                                    @php($isIncluded = $membership->subscriptionTypes->contains('id', $type->id))
                                                    <li
                                                        class="subscription-membership-module-row {{ $isIncluded ? 'is-included' : 'is-excluded' }}">
                                                        <i class="subscription-membership-module-row__status fa-solid {{ $isIncluded ? 'fa-circle-check' : 'fa-circle-xmark' }}"
                                                            aria-hidden="true"></i>
                                                        <span class="subscription-membership-module-row__label" dir="auto">{{ $type->name }}</span>
                                                        <i class="subscription-membership-module-row__icon fa-solid {{ $type->frontend_membership_icon }}"
                                                            aria-hidden="true"></i>
                                                    </li>
                                                @endforeach
                                            </ul>
                                        @endif

                                        @auth('web')
                                            @if ($membership->frontend_purchase_allowed)
                                                <a class="subscription-card__action" href="{{ route('front.subscriptions.checkout', $membership) }}">
                                                    {{ $membership->frontend_purchase_label }} <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
                                                </a>
                                            @else
                                                <button class="subscription-card__action" type="button" disabled aria-disabled="true">
                                                    {{ $membership->frontend_purchase_label }} <i class="fa-solid fa-lock" aria-hidden="true"></i>
                                                </button>
                                            @endif
                                            @if ($membership->frontend_purchase_hint)
                                                <small class="subscription-card__action-hint">{{ $membership->frontend_purchase_hint }}</small>
                                            @endif
                                        @else
                                            <button class="subscription-card__action" type="button" data-subscription-guest-cta
                                                data-product-id="{{ $membership->getKey() }}" data-intent-url="{{ route('front.subscriptions.intent', $membership) }}">
                                                Subscribe Now <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
                                            </button>
                                        @endauth
                                    </article>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="subscription-empty"><i class="fa-solid fa-users" aria-hidden="true"></i>
                            <p>No memberships are available.</p>
                        </div>
                    @endif
                </section>
            </div>
        </div>

    </main>
@endsection